// Use `object` instead of Vue's `Component` to avoid type incompatibility
// between root and UI package Vue runtime-core copies in the monorepo.
// Vue's `<component :is="...">` accepts any object at runtime.
export interface CommandPaletteCommand {
    id: string;
    label: string;
    icon?: object;
    keywords: string[];
    action: () => void | Promise<void>;
    shortcut?: string;
}

export interface CommandPaletteGroup {
    id: string;
    heading: string;
    commands: CommandPaletteCommand[];
}

export interface EntitySearchResult extends CommandPaletteCommand {
    entityType: string;
    color?: string;
    badgeClass?: string;
}
