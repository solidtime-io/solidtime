import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { themeSetting, type themeOption } from '@/utils/theme';
import {
    canViewProjects,
    canViewClients,
    canViewMembers,
    canViewTags,
    canViewReport,
    canViewInvoices,
    canManageBilling,
    canUpdateOrganization,
    canCreateProjects,
    canCreateClients,
    canCreateTasks,
    canCreateTags,
    canCreateInvitations,
} from '@/utils/permissions';
import { isBillingActivated, isInvoicingActivated } from '@/utils/billing';
import { useTimeEntriesInfiniteQuery } from '@/utils/useTimeEntriesInfiniteQuery';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useMembersQuery } from '@/utils/useMembersQuery';
import {
    createNavigationCommands,
    createTimerCommands,
    createActiveTimerCommands,
    createThemeCommands,
    createCreateCommands,
    createOrganizationCommands,
    scoreEntity,
    GROUP_PRIORITIES,
    type Command,
    type CommandGroup as CommandGroupType,
} from '@/utils/commandPaletteCommands';
import { usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import { switchOrganization } from '@/utils/useOrganization';
import type {
    CommandPaletteGroup,
    EntitySearchResult,
} from '@/packages/ui/src/CommandPalette/CommandPaletteTypes';
import type { Project, Client, Task, Tag, Member } from '@/packages/api/src';
import {
    FolderIcon,
    UserCircleIcon,
    TagIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/20/solid';

// Global state (singleton pattern - shared across all useCommandPalette() calls)
const isOpen = ref(false);
const searchTerm = ref('');

// Modal states for create actions
const showCreateProjectModal = ref(false);
const showCreateClientModal = ref(false);
const showCreateTaskModal = ref(false);
const showCreateTagModal = ref(false);
const showInviteMemberModal = ref(false);
const showCreateTimeEntryModal = ref(false);

// Active timer selector states
const showProjectSelector = ref(false);
const showTaskSelector = ref(false);
const showTagsSelector = ref(false);

// Group display order and headings
const GROUP_CONFIG: { id: CommandGroupType; heading: string }[] = [
    { id: 'timer', heading: 'Timer' },
    { id: 'active-timer', heading: 'Active Timer' },
    { id: 'navigation', heading: 'Navigation' },
    { id: 'create', heading: 'Create' },
    { id: 'organization', heading: 'Organization' },
    { id: 'theme', heading: 'Theme' },
];

// Entity badge classes
const ENTITY_BADGE_CLASSES: Record<string, string> = {
    project: 'bg-violet-500/20 text-violet-500',
    client: 'bg-blue-500/20 text-blue-500',
    task: 'bg-gray-500/20 text-gray-400',
    tag: 'bg-amber-500/20 text-amber-500',
    member: 'bg-green-500/20 text-green-500',
};

// Entity icons
const ENTITY_ICONS: Record<string, typeof FolderIcon> = {
    project: FolderIcon,
    client: UserCircleIcon,
    task: ClipboardDocumentListIcon,
    tag: TagIcon,
    member: UserGroupIcon,
};

export function useCommandPalette() {
    const currentTimeEntryStore = useCurrentTimeEntryStore();
    const { currentTimeEntry, isActive } = storeToRefs(currentTimeEntryStore);
    const { setActiveState, updateTimer } = currentTimeEntryStore;

    // Data queries (consolidated here - single source of truth)
    const timeEntriesQuery = useTimeEntriesInfiniteQuery();
    const { projects } = useProjectsQuery();
    const { clients } = useClientsQuery();
    const { tasks } = useTasksQuery();
    const { tags } = useTagsQuery();
    const { members } = useMembersQuery();

    const page = usePage<{
        auth: {
            user: User & {
                all_teams: Organization[];
                current_team_id: string;
            };
        };
    }>();

    const getCurrentTeamId = () => page.props.auth.user.current_team?.id ?? '';
    const allOrganizations = computed(() => page.props.auth.user.all_teams || []);
    const currentOrgId = computed(() => page.props.auth.user.current_team_id || '');

    const lastTimeEntry = computed(() => {
        const pages = timeEntriesQuery.data.value?.pages;
        if (!pages || pages.length === 0) return null;
        const firstPage = pages[0];
        if (!firstPage?.data || firstPage.data.length === 0) return null;
        return firstPage.data[0];
    });

    const hasTimeEntries = computed(() => lastTimeEntry.value !== null);

    // Helper to close palette
    function closePaletteAfterAction() {
        isOpen.value = false;
    }

    // Navigation helper
    function navigate(routeName: string, params?: Record<string, string>) {
        closePaletteAfterAction();
        if (routeName.startsWith('/')) {
            router.visit(routeName);
        } else {
            router.visit(route(routeName, params));
        }
    }

    // Theme helper
    function setTheme(theme: themeOption) {
        themeSetting.value = theme;
        closePaletteAfterAction();
    }

    // Timer actions
    async function startTimer() {
        closePaletteAfterAction();
        await setActiveState(true);
    }

    async function stopTimer() {
        closePaletteAfterAction();
        await setActiveState(false);
    }

    function openCreateTimeEntryModal() {
        closePaletteAfterAction();
        showCreateTimeEntryModal.value = true;
    }

    async function continueLastEntry() {
        if (!lastTimeEntry.value) return;
        closePaletteAfterAction();

        currentTimeEntry.value.description = lastTimeEntry.value.description;
        currentTimeEntry.value.project_id = lastTimeEntry.value.project_id;
        currentTimeEntry.value.task_id = lastTimeEntry.value.task_id;
        currentTimeEntry.value.tags = lastTimeEntry.value.tags;
        currentTimeEntry.value.billable = lastTimeEntry.value.billable;
        currentTimeEntry.value.start = getDayJsInstance()().utc().format();
        await setActiveState(true);
    }

    // Active timer actions
    function openProjectSelector() {
        closePaletteAfterAction();
        showProjectSelector.value = true;
    }

    function openTaskSelector() {
        closePaletteAfterAction();
        showTaskSelector.value = true;
    }

    function openTagsSelector() {
        closePaletteAfterAction();
        showTagsSelector.value = true;
    }

    async function toggleBillable() {
        closePaletteAfterAction();
        currentTimeEntry.value.billable = !currentTimeEntry.value.billable;
        await updateTimer();
    }

    async function addMinutes(minutes: number) {
        closePaletteAfterAction();
        currentTimeEntry.value.start = getDayJsInstance()(currentTimeEntry.value.start)
            .subtract(minutes, 'minutes')
            .utc()
            .format();
        await updateTimer();
    }

    // Create actions
    function openCreateProjectModal() {
        closePaletteAfterAction();
        showCreateProjectModal.value = true;
    }

    function openCreateClientModal() {
        closePaletteAfterAction();
        showCreateClientModal.value = true;
    }

    function openCreateTaskModal() {
        closePaletteAfterAction();
        showCreateTaskModal.value = true;
    }

    function openCreateTagModal() {
        closePaletteAfterAction();
        showCreateTagModal.value = true;
    }

    function openInviteMemberModal() {
        closePaletteAfterAction();
        showInviteMemberModal.value = true;
    }

    // Organization switch action
    function handleSwitchOrganization(orgId: string) {
        closePaletteAfterAction();
        switchOrganization(orgId);
    }

    // Build all internal commands
    const navigationCommands = computed(() =>
        createNavigationCommands(
            navigate,
            {
                canViewProjects,
                canViewClients,
                canViewMembers,
                canViewTags,
                canViewReport,
                canViewInvoices,
                canManageBilling,
                canUpdateOrganization,
            },
            {
                isInvoicingActivated,
                isBillingActivated,
            },
            getCurrentTeamId
        )
    );

    const timerCommands = computed(() =>
        createTimerCommands(
            {
                startTimer,
                stopTimer,
                openCreateTimeEntryModal,
                continueLastEntry,
            },
            {
                isActive: () => isActive.value,
                hasTimeEntries: () => hasTimeEntries.value,
            }
        )
    );

    const activeTimerCommands = computed(() =>
        createActiveTimerCommands(
            {
                openProjectSelector,
                openTaskSelector,
                openTagsSelector,
                toggleBillable,
                addMinutes,
            },
            {
                isActive: () => isActive.value,
            }
        )
    );

    const themeCommands = computed(() => createThemeCommands(setTheme));

    const createCommands = computed(() =>
        createCreateCommands(
            {
                openProjectModal: openCreateProjectModal,
                openClientModal: openCreateClientModal,
                openTaskModal: openCreateTaskModal,
                openTagModal: openCreateTagModal,
                openInviteModal: openInviteMemberModal,
            },
            {
                canCreateProjects,
                canCreateClients,
                canCreateTasks,
                canCreateTags,
                canCreateInvitations,
            }
        )
    );

    const organizationCommands = computed(() =>
        createOrganizationCommands(
            allOrganizations.value,
            currentOrgId.value,
            handleSwitchOrganization
        )
    );

    // Internal commands grouped by type
    const commandsByGroup = computed<Record<string, Command[]>>(() => {
        const allCommands: Command[] = [
            ...timerCommands.value,
            ...activeTimerCommands.value,
            ...navigationCommands.value,
            ...createCommands.value,
            ...organizationCommands.value,
            ...themeCommands.value,
        ];

        const grouped: Record<string, Command[]> = {};
        for (const config of GROUP_CONFIG) {
            grouped[config.id] = [];
        }

        for (const cmd of allCommands) {
            if (cmd.permission && !cmd.permission()) continue;
            if (cmd.condition && !cmd.condition()) continue;

            if (grouped[cmd.group] !== undefined) {
                grouped[cmd.group]!.push(cmd);
            }
        }

        return grouped;
    });

    // Map internal commands to UI-friendly CommandPaletteGroup[]
    const groups = computed<CommandPaletteGroup[]>(() =>
        GROUP_CONFIG.map((config) => ({
            id: config.id,
            heading: config.heading,
            commands: (commandsByGroup.value[config.id] ?? []).map((cmd) => ({
                id: cmd.id,
                label: cmd.label,
                icon: cmd.icon,
                keywords: cmd.keywords,
                action: cmd.action,
                shortcut: cmd.shortcut,
            })),
        }))
    );

    // Entity search results (moved from old CommandPalette.vue)
    const entityResults = computed<EntitySearchResult[]>(() => {
        const query = searchTerm.value.toLowerCase().trim();
        if (!query || query.length < 2) return [];

        const results: EntitySearchResult[] = [];
        const maxPerType = 5;

        if (canViewProjects()) {
            const matching = projects.value
                .filter((p: Project) => p.name.toLowerCase().includes(query))
                .slice(0, maxPerType)
                .map(
                    (p: Project): EntitySearchResult => ({
                        id: `entity-project-${p.id}`,
                        label: p.name,
                        icon: ENTITY_ICONS.project,
                        keywords: ['project'],
                        action: () => {
                            closePaletteAfterAction();
                            router.visit(route('projects.show', { project: p.id }));
                        },
                        entityType: 'project',
                        color: p.color,
                        badgeClass: ENTITY_BADGE_CLASSES.project,
                    })
                );
            results.push(...matching);
        }

        if (canViewClients()) {
            const matching = clients.value
                .filter((c: Client) => c.name.toLowerCase().includes(query))
                .slice(0, maxPerType)
                .map(
                    (c: Client): EntitySearchResult => ({
                        id: `entity-client-${c.id}`,
                        label: c.name,
                        icon: ENTITY_ICONS.client,
                        keywords: ['client'],
                        action: () => {
                            closePaletteAfterAction();
                            router.visit(route('clients'));
                        },
                        entityType: 'client',
                        badgeClass: ENTITY_BADGE_CLASSES.client,
                    })
                );
            results.push(...matching);
        }

        if (canViewProjects()) {
            const matching = tasks.value
                .filter((t: Task) => t.name.toLowerCase().includes(query))
                .slice(0, maxPerType)
                .map(
                    (t: Task): EntitySearchResult => ({
                        id: `entity-task-${t.id}`,
                        label: t.name,
                        icon: ENTITY_ICONS.task,
                        keywords: ['task'],
                        action: () => {
                            closePaletteAfterAction();
                            if (t.project_id) {
                                router.visit(route('projects.show', { project: t.project_id }));
                            }
                        },
                        entityType: 'task',
                        badgeClass: ENTITY_BADGE_CLASSES.task,
                    })
                );
            results.push(...matching);
        }

        if (canViewTags()) {
            const matching = tags.value
                .filter((t: Tag) => t.name.toLowerCase().includes(query))
                .slice(0, maxPerType)
                .map(
                    (t: Tag): EntitySearchResult => ({
                        id: `entity-tag-${t.id}`,
                        label: t.name,
                        icon: ENTITY_ICONS.tag,
                        keywords: ['tag'],
                        action: () => {
                            closePaletteAfterAction();
                            router.visit(route('tags'));
                        },
                        entityType: 'tag',
                        badgeClass: ENTITY_BADGE_CLASSES.tag,
                    })
                );
            results.push(...matching);
        }

        if (canViewMembers()) {
            const matching = members.value
                .filter((m: Member) => m.name.toLowerCase().includes(query))
                .slice(0, maxPerType)
                .map(
                    (m: Member): EntitySearchResult => ({
                        id: `entity-member-${m.id}`,
                        label: m.name,
                        icon: ENTITY_ICONS.member,
                        keywords: ['member'],
                        action: () => {
                            closePaletteAfterAction();
                            router.visit(route('members'));
                        },
                        entityType: 'member',
                        badgeClass: ENTITY_BADGE_CLASSES.member,
                    })
                );
            results.push(...matching);
        }

        return results.sort(
            (a, b) =>
                scoreEntity(b.label, query, GROUP_PRIORITIES.entity) -
                scoreEntity(a.label, query, GROUP_PRIORITIES.entity)
        );
    });

    // Open/close
    function openPalette() {
        isOpen.value = true;
    }

    function closePalette() {
        isOpen.value = false;
    }

    function togglePalette() {
        if (isOpen.value) {
            closePalette();
        } else {
            openPalette();
        }
    }

    return {
        // State
        isOpen,
        searchTerm,

        // Modal states
        showCreateProjectModal,
        showCreateClientModal,
        showCreateTaskModal,
        showCreateTagModal,
        showInviteMemberModal,
        showCreateTimeEntryModal,
        showProjectSelector,
        showTaskSelector,
        showTagsSelector,

        // UI data (for CommandPalette component props)
        groups,
        entityResults,

        // Query data (for Provider modals)
        projects,
        clients,
        tasks,
        tags,

        // Computed
        isActive,
        currentTimeEntry,

        // Actions
        openPalette,
        closePalette,
        togglePalette,
        updateTimer,
    };
}
