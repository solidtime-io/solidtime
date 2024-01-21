export interface Membership {
    // columns
    id: string;
    team_id: string;
    user_id: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface Team {
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
    team_invitations: TeamInvitation[];
}

export interface TeamInvitation {
    // columns
    id: string;
    team_id: string;
    email: string;
    role: string | null;
    created_at: string | null;
    updated_at: string | null;
    // relations
    team: Team;
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
    two_factor_enabled: boolean;
    // mutators
    profile_photo_url: string;
    // relations
    membership: Membership[];
    current_team: Team;
    owned_teams: Team[];
    teams: Team[];
}
