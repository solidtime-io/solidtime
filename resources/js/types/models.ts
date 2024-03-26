export interface Client {
    // columns
    id: string;
    name: string;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    // relations
    organization: Organization;
}

export interface Membership {
    // columns
    id: string;
    organization_id: string;
    user_id: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface Organization {
    // columns
    id: string;
    user_id: string;
    name: string;
    personal_team: boolean;
    created_at: string | null;
    updated_at: string | null;
    // relations
    owner: User;
    users: User[];
    team_invitations: OrganizationInvitation[];
}

export interface OrganizationInvitation {
    // columns
    id: string;
    organization_id: string;
    email: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    organization: Organization;
    team: Organization;
}

export interface Project {
    // columns
    id: string;
    name: string;
    color: string;
    client_id: string | null;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    // relations
    organization: Organization;
    client: Client;
    tasks: Task[];
}

export interface Task {
    // columns
    id: string;
    name: string;
    project_id: string;
    organization_id: string;
    created_at: string | null;
    updated_at: string | null;
    // relations
    project: Project;
    organization: Organization;
}

export interface User {
    // columns
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
    // mutators
    profile_photo_url: string;
    // relations
    organizations: Organization[];
    clients: Client[];
    current_team: Organization;
    owned_teams: Organization[];
    teams: Organization[];
}
