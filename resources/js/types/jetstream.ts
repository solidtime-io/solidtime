export interface Permissions {
    canAddTeamMembers: boolean;
    canDeleteTeam: boolean;
    canRemoveTeamMembers: boolean;
    canUpdateTeam: boolean;
    canUpdateTeamMembers: boolean;
}

export interface Session {
    agent: {
        platform: string;
        browser: string;

        is_desktop: boolean;
    };
    ip_address: string;
    is_current_device: string;
    last_active: boolean;
}

export interface Membership {
    role: string;
}

export interface Role {
    key: string;
    name: string;
    description: string;
}

export interface Token {
    name: string;
    token: string;
    abilities: string[];
    id: string;
    last_used_ago: string;
}
