export interface Client {
    id: string;
    name: string;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    organization: Organization;
}
export interface Membership {
    id: string;
    organization_id: string;
    user_id: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
}
export interface Organization {
    id: string;
    user_id: string;
    name: string;
    personal_team: boolean;
    currency: string;
    created_at: string | null;
    updated_at: string | null;
    owner: User;
    users: User[];
    team_invitations: OrganizationInvitation[];
}
export interface OrganizationInvitation {
    id: string;
    organization_id: string;
    email: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
    organization: Organization;
    team: Organization;
}
export interface Project {
    id: string;
    name: string;
    color: string;
    client_id: string | null;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    organization: Organization;
    client: Client;
    tasks: Task[];
}
export interface Task {
    id: string;
    name: string;
    project_id: string;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    project: Project;
    organization: Organization;
}
type OrganizationWithMembership = Organization & {
    membership: Membership;
};
export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    password?: string;
    remember_token?: string | null;
    current_team_id: string | null;
    profile_photo_path: string | null;
    created_at: string | null;
    updated_at: string | null;
    two_factor_secret?: string | null;
    two_factor_recovery_codes?: string | null;
    two_factor_confirmed_at: string | null;
    timezone: string;
    week_start: string;
    profile_photo_url: string;
    organizations: Organization[];
    clients: Client[];
    current_team: Organization;
    all_teams: OrganizationWithMembership[];
    owned_teams: Organization[];
    teams: Organization[];
}
export {};
