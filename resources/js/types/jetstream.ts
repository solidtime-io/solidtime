import type { User } from '@/types/models';

export interface Permissions {
    canDeleteTeam: boolean;
    canUpdateTeam: boolean;
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

export type JetstreamUser = User & {
    two_factor_enabled: boolean;
};
