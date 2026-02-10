import type { Component } from 'vue';
import {
    HomeIcon,
    ClockIcon,
    CalendarIcon,
    ChartBarIcon,
    FolderIcon,
    UserCircleIcon,
    UserGroupIcon,
    TagIcon,
    DocumentTextIcon,
    CreditCardIcon,
    ArrowsRightLeftIcon,
    Cog6ToothIcon,
    UserIcon,
    PlayIcon,
    StopIcon,
    PlusIcon,
    ArrowPathIcon,
    SunIcon,
    MoonIcon,
    ComputerDesktopIcon,
    ClipboardDocumentListIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/20/solid';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import type { Organization } from '@/types/models';

export type CommandGroup =
    | 'timer'
    | 'active-timer'
    | 'navigation'
    | 'create'
    | 'theme'
    | 'organization'
    | 'entity';

export interface Command {
    id: string;
    label: string;
    icon?: Component;
    keywords: string[];
    group: CommandGroup;
    action: () => void | Promise<void>;
    shortcut?: string;
    permission?: () => boolean;
    condition?: () => boolean;
    priority: number;
}

export const GROUP_PRIORITIES: Record<CommandGroup, number> = {
    timer: 1000,
    'active-timer': 900,
    navigation: 500,
    create: 400,
    organization: 300,
    theme: 200,
    entity: 100,
};

export function createNavigationCommands(
    navigate: (route: string, params?: Record<string, string>) => void,
    permissions: {
        canViewProjects: () => boolean;
        canViewClients: () => boolean;
        canViewMembers: () => boolean;
        canViewTags: () => boolean;
        canViewReport: () => boolean;
        canViewInvoices: () => boolean;
        canManageBilling: () => boolean;
        canUpdateOrganization: () => boolean;
    },
    features: {
        isInvoicingActivated: () => boolean;
        isBillingActivated: () => boolean;
    },
    currentTeamId: () => string
): Command[] {
    return [
        {
            id: 'nav-dashboard',
            label: 'Go to Dashboard',
            icon: HomeIcon,
            keywords: ['home', 'overview', 'dashboard'],
            group: 'navigation',
            action: () => navigate('dashboard'),
            priority: GROUP_PRIORITIES.navigation + 10,
        },
        {
            id: 'nav-time',
            label: 'Go to Time',
            icon: ClockIcon,
            keywords: ['time', 'tracking', 'entries', 'timesheet'],
            group: 'navigation',
            action: () => navigate('time'),
            priority: GROUP_PRIORITIES.navigation + 9,
        },
        {
            id: 'nav-calendar',
            label: 'Go to Calendar',
            icon: CalendarIcon,
            keywords: ['calendar', 'week', 'schedule'],
            group: 'navigation',
            action: () => navigate('calendar'),
            priority: GROUP_PRIORITIES.navigation + 8,
        },
        {
            id: 'nav-reporting',
            label: 'Go to Reporting Overview',
            icon: ChartBarIcon,
            keywords: ['reports', 'analytics', 'overview', 'statistics'],
            group: 'navigation',
            action: () => navigate('reporting'),
            priority: GROUP_PRIORITIES.navigation + 7,
        },
        {
            id: 'nav-reporting-detailed',
            label: 'Go to Reporting Detailed',
            icon: ChartBarIcon,
            keywords: ['detailed', 'reports', 'breakdown'],
            group: 'navigation',
            action: () => navigate('reporting.detailed'),
            priority: GROUP_PRIORITIES.navigation + 6,
        },
        {
            id: 'nav-reporting-shared',
            label: 'Go to Shared Reports',
            icon: ChartBarIcon,
            keywords: ['shared', 'public', 'reports'],
            group: 'navigation',
            action: () => navigate('reporting.shared'),
            permission: permissions.canViewReport,
            priority: GROUP_PRIORITIES.navigation + 5,
        },
        {
            id: 'nav-projects',
            label: 'Go to Projects',
            icon: FolderIcon,
            keywords: ['projects', 'work'],
            group: 'navigation',
            action: () => navigate('projects'),
            permission: permissions.canViewProjects,
            priority: GROUP_PRIORITIES.navigation + 4,
        },
        {
            id: 'nav-clients',
            label: 'Go to Clients',
            icon: UserCircleIcon,
            keywords: ['clients', 'customers'],
            group: 'navigation',
            action: () => navigate('clients'),
            permission: permissions.canViewClients,
            priority: GROUP_PRIORITIES.navigation + 3,
        },
        {
            id: 'nav-members',
            label: 'Go to Members',
            icon: UserGroupIcon,
            keywords: ['members', 'team', 'users', 'employees'],
            group: 'navigation',
            action: () => navigate('members'),
            permission: permissions.canViewMembers,
            priority: GROUP_PRIORITIES.navigation + 2,
        },
        {
            id: 'nav-tags',
            label: 'Go to Tags',
            icon: TagIcon,
            keywords: ['tags', 'labels', 'categories'],
            group: 'navigation',
            action: () => navigate('tags'),
            permission: permissions.canViewTags,
            priority: GROUP_PRIORITIES.navigation + 1,
        },
        {
            id: 'nav-invoices',
            label: 'Go to Invoices',
            icon: DocumentTextIcon,
            keywords: ['invoices', 'billing', 'payments'],
            group: 'navigation',
            action: () => navigate('/invoices', {}),
            permission: permissions.canViewInvoices,
            condition: features.isInvoicingActivated,
            priority: GROUP_PRIORITIES.navigation,
        },
        {
            id: 'nav-billing',
            label: 'Go to Billing',
            icon: CreditCardIcon,
            keywords: ['billing', 'subscription', 'plan'],
            group: 'navigation',
            action: () => navigate('/billing', {}),
            permission: permissions.canManageBilling,
            condition: features.isBillingActivated,
            priority: GROUP_PRIORITIES.navigation - 1,
        },
        {
            id: 'nav-import',
            label: 'Go to Import / Export',
            icon: ArrowsRightLeftIcon,
            keywords: ['import', 'export', 'data', 'backup'],
            group: 'navigation',
            action: () => navigate('import'),
            permission: permissions.canUpdateOrganization,
            priority: GROUP_PRIORITIES.navigation - 2,
        },
        {
            id: 'nav-settings',
            label: 'Go to Settings',
            icon: Cog6ToothIcon,
            keywords: ['settings', 'organization', 'configuration'],
            group: 'navigation',
            action: () => navigate('teams.show', { team: currentTeamId() }),
            permission: permissions.canUpdateOrganization,
            priority: GROUP_PRIORITIES.navigation - 3,
        },
        {
            id: 'nav-profile',
            label: 'Go to Profile',
            icon: UserIcon,
            keywords: ['profile', 'account', 'user', 'personal'],
            group: 'navigation',
            action: () => navigate('profile.show'),
            priority: GROUP_PRIORITIES.navigation - 4,
        },
    ];
}

export function createTimerCommands(
    timerActions: {
        startTimer: () => Promise<void>;
        stopTimer: () => Promise<void>;
        openCreateTimeEntryModal: () => void;
        continueLastEntry: () => Promise<void>;
    },
    conditions: {
        isActive: () => boolean;
        hasTimeEntries: () => boolean;
    }
): Command[] {
    return [
        {
            id: 'timer-start',
            label: 'Start Timer',
            icon: PlayIcon,
            keywords: ['start', 'begin', 'track', 'timer'],
            group: 'timer',
            action: timerActions.startTimer,
            condition: () => !conditions.isActive(),
            priority: GROUP_PRIORITIES.timer + 10,
        },
        {
            id: 'timer-stop',
            label: 'Stop Timer',
            icon: StopIcon,
            keywords: ['stop', 'end', 'finish', 'timer'],
            group: 'timer',
            action: timerActions.stopTimer,
            condition: conditions.isActive,
            priority: GROUP_PRIORITIES.timer + 10,
        },
        {
            id: 'timer-create',
            label: 'Create Time Entry',
            icon: PlusIcon,
            keywords: ['create', 'manual', 'log', 'time', 'entry', 'new'],
            group: 'timer',
            action: timerActions.openCreateTimeEntryModal,
            priority: GROUP_PRIORITIES.timer + 5,
        },
        {
            id: 'timer-continue',
            label: 'Continue Last Time Entry',
            icon: ArrowPathIcon,
            keywords: ['continue', 'repeat', 'restart', 'last', 'previous'],
            group: 'timer',
            action: timerActions.continueLastEntry,
            condition: () => !conditions.isActive() && conditions.hasTimeEntries(),
            priority: GROUP_PRIORITIES.timer + 4,
        },
    ];
}

export function createActiveTimerCommands(
    activeTimerActions: {
        openProjectSelector: () => void;
        openTaskSelector: () => void;
        openTagsSelector: () => void;
        toggleBillable: () => void;
        addMinutes: (minutes: number) => void;
    },
    conditions: {
        isActive: () => boolean;
    }
): Command[] {
    const minuteOptions = [5, 10, 15, 20, 25, 30, 45, 60];

    const addMinutesCommands: Command[] = minuteOptions.map((minutes) => ({
        id: `timer-add-${minutes}`,
        label: `Add ${minutes} minutes to timer`,
        icon: ClockIcon,
        keywords: [`+${minutes}`, `add ${minutes}`, minutes === 60 ? 'add hour' : ''],
        group: 'active-timer' as CommandGroup,
        action: () => activeTimerActions.addMinutes(minutes),
        condition: conditions.isActive,
        priority: GROUP_PRIORITIES['active-timer'] - minutes,
    }));

    return [
        {
            id: 'timer-set-project',
            label: 'Set Project',
            icon: FolderIcon,
            keywords: ['project', 'change project', 'select project'],
            group: 'active-timer',
            action: activeTimerActions.openProjectSelector,
            condition: conditions.isActive,
            priority: GROUP_PRIORITIES['active-timer'] + 10,
        },
        {
            id: 'timer-set-task',
            label: 'Set Task',
            icon: ClipboardDocumentListIcon,
            keywords: ['task', 'change task', 'select task'],
            group: 'active-timer',
            action: activeTimerActions.openTaskSelector,
            condition: conditions.isActive,
            priority: GROUP_PRIORITIES['active-timer'] + 9,
        },
        {
            id: 'timer-set-tags',
            label: 'Set Tags',
            icon: TagIcon,
            keywords: ['tags', 'add tags', 'labels'],
            group: 'active-timer',
            action: activeTimerActions.openTagsSelector,
            condition: conditions.isActive,
            priority: GROUP_PRIORITIES['active-timer'] + 8,
        },
        {
            id: 'timer-toggle-billable',
            label: 'Toggle Billable',
            icon: BillableIcon,
            keywords: ['billable', 'non-billable', 'money'],
            group: 'active-timer',
            action: activeTimerActions.toggleBillable,
            condition: conditions.isActive,
            priority: GROUP_PRIORITIES['active-timer'] + 7,
        },
        ...addMinutesCommands,
    ];
}

export function createThemeCommands(
    setTheme: (theme: 'light' | 'dark' | 'system') => void
): Command[] {
    return [
        {
            id: 'theme-light',
            label: 'Switch to Light Theme',
            icon: SunIcon,
            keywords: ['light', 'bright', 'day', 'theme'],
            group: 'theme',
            action: () => setTheme('light'),
            priority: GROUP_PRIORITIES.theme + 3,
        },
        {
            id: 'theme-dark',
            label: 'Switch to Dark Theme',
            icon: MoonIcon,
            keywords: ['dark', 'night', 'theme'],
            group: 'theme',
            action: () => setTheme('dark'),
            priority: GROUP_PRIORITIES.theme + 2,
        },
        {
            id: 'theme-system',
            label: 'Switch to System Theme',
            icon: ComputerDesktopIcon,
            keywords: ['system', 'auto', 'default', 'theme'],
            group: 'theme',
            action: () => setTheme('system'),
            priority: GROUP_PRIORITIES.theme + 1,
        },
    ];
}

export function createCreateCommands(
    createActions: {
        openProjectModal: () => void;
        openClientModal: () => void;
        openTaskModal: () => void;
        openTagModal: () => void;
        openInviteModal: () => void;
    },
    permissions: {
        canCreateProjects: () => boolean;
        canCreateClients: () => boolean;
        canCreateTasks: () => boolean;
        canCreateTags: () => boolean;
        canCreateInvitations: () => boolean;
    }
): Command[] {
    return [
        {
            id: 'create-project',
            label: 'Create Project',
            icon: FolderIcon,
            keywords: ['new project', 'add project', 'create'],
            group: 'create',
            action: createActions.openProjectModal,
            permission: permissions.canCreateProjects,
            priority: GROUP_PRIORITIES.create + 5,
        },
        {
            id: 'create-client',
            label: 'Create Client',
            icon: UserCircleIcon,
            keywords: ['new client', 'add client', 'create'],
            group: 'create',
            action: createActions.openClientModal,
            permission: permissions.canCreateClients,
            priority: GROUP_PRIORITIES.create + 4,
        },
        {
            id: 'create-task',
            label: 'Create Task',
            icon: ClipboardDocumentListIcon,
            keywords: ['new task', 'add task', 'create'],
            group: 'create',
            action: createActions.openTaskModal,
            permission: permissions.canCreateTasks,
            priority: GROUP_PRIORITIES.create + 3,
        },
        {
            id: 'create-tag',
            label: 'Create Tag',
            icon: TagIcon,
            keywords: ['new tag', 'add tag', 'create'],
            group: 'create',
            action: createActions.openTagModal,
            permission: permissions.canCreateTags,
            priority: GROUP_PRIORITIES.create + 2,
        },
        {
            id: 'create-invite',
            label: 'Invite Member',
            icon: UserGroupIcon,
            keywords: ['invite', 'add member', 'team'],
            group: 'create',
            action: createActions.openInviteModal,
            permission: permissions.canCreateInvitations,
            priority: GROUP_PRIORITIES.create + 1,
        },
    ];
}

export function createOrganizationCommands(
    organizations: Organization[],
    currentOrgId: string,
    switchOrganization: (orgId: string) => void
): Command[] {
    if (organizations.length <= 1) return [];

    return organizations
        .filter((org) => org.id !== currentOrgId)
        .map((org) => ({
            id: `org-switch-${org.id}`,
            label: `Switch to ${org.name}`,
            icon: BuildingOfficeIcon,
            keywords: ['switch', 'organization', 'workspace', org.name.toLowerCase()],
            group: 'organization' as CommandGroup,
            action: () => switchOrganization(org.id),
            priority: GROUP_PRIORITIES.organization + 1,
        }));
}

export function scoreEntity(name: string, query: string, baseScore: number): number {
    const normalizedName = name.toLowerCase();
    const normalizedQuery = query.toLowerCase().trim();

    if (normalizedName === normalizedQuery) return baseScore + 50;
    if (normalizedName.startsWith(normalizedQuery)) return baseScore + 30;
    if (normalizedName.includes(normalizedQuery)) return baseScore + 10;
    return baseScore;
}
