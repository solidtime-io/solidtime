-- +goose Up
-- Baseline schema for tock, generated from `pg_dump --schema-only` of a fully
-- migrated upstream solidtime database (PostgreSQL). Table and column names are
-- identical to upstream so a real upstream dump restores cleanly.
CREATE TABLE public.audits (
    id bigint NOT NULL,
    actor_type character varying(255),
    actor_id uuid,
    event character varying(255) NOT NULL,
    auditable_type character varying(255) NOT NULL,
    auditable_id uuid NOT NULL,
    old_values json,
    new_values json,
    url text,
    ip_address inet,
    user_agent character varying(1023),
    tags character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    owner_user_id uuid,
    owner_organization_id uuid
);

CREATE SEQUENCE public.audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.audits_id_seq OWNED BY public.audits.id;

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);

CREATE TABLE public.clients (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    organization_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    archived_at timestamp(0) without time zone
);

CREATE TABLE public.failed_jobs (
    uuid uuid NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id bigint NOT NULL
);

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;

CREATE TABLE public.goals (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    comparison character varying(255) NOT NULL,
    target_seconds bigint NOT NULL,
    period character varying(255) NOT NULL,
    filters jsonb NOT NULL,
    timezone character varying(255) NOT NULL,
    week_start character varying(255) NOT NULL,
    archived_at timestamp(0) without time zone,
    organization_id uuid NOT NULL,
    member_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;

CREATE TABLE public.members (
    id uuid NOT NULL,
    organization_id uuid NOT NULL,
    user_id uuid NOT NULL,
    role character varying(255),
    billable_rate integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;

CREATE TABLE public.oauth_access_tokens (
    id character varying(100) NOT NULL,
    user_id uuid,
    client_id uuid NOT NULL,
    name character varying(255),
    scopes text,
    revoked boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    reminder_sent_at timestamp(0) without time zone,
    expired_info_sent_at timestamp(0) without time zone
);

CREATE TABLE public.oauth_auth_codes (
    id character varying(100) NOT NULL,
    user_id uuid NOT NULL,
    client_id uuid NOT NULL,
    scopes text,
    revoked boolean NOT NULL,
    expires_at timestamp(0) without time zone
);

CREATE TABLE public.oauth_clients (
    id uuid NOT NULL,
    owner_id uuid,
    name character varying(255) NOT NULL,
    secret character varying(100),
    provider character varying(255),
    revoked boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    grant_types text DEFAULT '[]'::text NOT NULL,
    redirect_uris text DEFAULT '[]'::text NOT NULL,
    owner_type character varying(255)
);

CREATE TABLE public.oauth_device_codes (
    id character(80) NOT NULL,
    user_id bigint,
    client_id uuid NOT NULL,
    user_code character(8) NOT NULL,
    scopes text NOT NULL,
    revoked boolean NOT NULL,
    user_approved_at timestamp(0) without time zone,
    last_polled_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone
);

CREATE TABLE public.oauth_refresh_tokens (
    id character varying(100) NOT NULL,
    access_token_id character varying(100) NOT NULL,
    revoked boolean NOT NULL,
    expires_at timestamp(0) without time zone
);

CREATE TABLE public.organization_invitations (
    id uuid NOT NULL,
    organization_id uuid NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    accepted_at timestamp(0) without time zone
);

CREATE TABLE public.organizations (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    personal_team boolean NOT NULL,
    billable_rate integer,
    currency character varying(3) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    employees_can_see_billable_rates boolean DEFAULT false NOT NULL,
    number_format character varying(255) NOT NULL,
    currency_format character varying(255) NOT NULL,
    date_format character varying(255) NOT NULL,
    interval_format character varying(255) NOT NULL,
    time_format character varying(255) NOT NULL,
    prevent_overlapping_time_entries boolean DEFAULT false NOT NULL,
    employees_can_manage_tasks boolean DEFAULT false NOT NULL,
    breaks_enabled boolean DEFAULT false NOT NULL
);

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);

CREATE TABLE public.personal_access_tokens (
    id uuid NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

CREATE TABLE public.project_members (
    id uuid NOT NULL,
    billable_rate integer,
    project_id uuid NOT NULL,
    user_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    member_id uuid NOT NULL
);

CREATE TABLE public.projects (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    color character varying(16) NOT NULL,
    billable_rate integer,
    is_public boolean DEFAULT false NOT NULL,
    client_id uuid,
    organization_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_billable boolean NOT NULL,
    archived_at timestamp(0) without time zone,
    estimated_time integer,
    spent_time bigint DEFAULT '0'::bigint NOT NULL
);

CREATE TABLE public.reports (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_public boolean DEFAULT false NOT NULL,
    share_secret character varying(40),
    properties jsonb NOT NULL,
    public_until timestamp(0) without time zone,
    organization_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id uuid,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);

CREATE TABLE public.tags (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    organization_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

CREATE TABLE public.tasks (
    id uuid NOT NULL,
    name character varying(500) NOT NULL,
    project_id uuid NOT NULL,
    organization_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    done_at timestamp(0) without time zone,
    estimated_time integer,
    spent_time bigint DEFAULT '0'::bigint NOT NULL
);

CREATE TABLE public.telescope_entries (
    sequence bigint NOT NULL,
    uuid uuid NOT NULL,
    batch_id uuid NOT NULL,
    family_hash character varying(255),
    should_display_on_index boolean DEFAULT true NOT NULL,
    type character varying(20) NOT NULL,
    content text NOT NULL,
    created_at timestamp(0) without time zone
);

CREATE SEQUENCE public.telescope_entries_sequence_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.telescope_entries_sequence_seq OWNED BY public.telescope_entries.sequence;

CREATE TABLE public.telescope_entries_tags (
    entry_uuid uuid NOT NULL,
    tag character varying(255) NOT NULL
);

CREATE TABLE public.telescope_monitoring (
    tag character varying(255) NOT NULL
);

CREATE TABLE public.time_entries (
    id uuid NOT NULL,
    description character varying(5000) NOT NULL,
    start timestamp(0) without time zone NOT NULL,
    "end" timestamp(0) without time zone,
    billable_rate integer,
    billable boolean DEFAULT false NOT NULL,
    user_id uuid NOT NULL,
    organization_id uuid NOT NULL,
    project_id uuid,
    task_id uuid,
    tags jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    member_id uuid NOT NULL,
    client_id uuid,
    is_imported boolean DEFAULT false NOT NULL,
    still_active_email_sent_at timestamp(0) without time zone,
    type character varying(255) DEFAULT 'work'::character varying NOT NULL
);

CREATE TABLE public.users (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255),
    remember_token character varying(100),
    is_placeholder boolean DEFAULT false NOT NULL,
    current_team_id uuid,
    profile_photo_path character varying(2048),
    timezone character varying(255) NOT NULL,
    week_start character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    pending_email character varying(255),
    send_time_entry_still_running_email boolean DEFAULT true NOT NULL,
    CONSTRAINT users_week_start_check CHECK (((week_start)::text = ANY ((ARRAY['monday'::character varying, 'tuesday'::character varying, 'wednesday'::character varying, 'thursday'::character varying, 'friday'::character varying, 'saturday'::character varying, 'sunday'::character varying])::text[])))
);

ALTER TABLE ONLY public.audits ALTER COLUMN id SET DEFAULT nextval('public.audits_id_seq'::regclass);

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);

ALTER TABLE ONLY public.telescope_entries ALTER COLUMN sequence SET DEFAULT nextval('public.telescope_entries_sequence_seq'::regclass);

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);

ALTER TABLE ONLY public.goals
    ADD CONSTRAINT goals_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.oauth_access_tokens
    ADD CONSTRAINT oauth_access_tokens_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.oauth_auth_codes
    ADD CONSTRAINT oauth_auth_codes_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.oauth_clients
    ADD CONSTRAINT oauth_clients_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.oauth_device_codes
    ADD CONSTRAINT oauth_device_codes_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.oauth_device_codes
    ADD CONSTRAINT oauth_device_codes_user_code_unique UNIQUE (user_code);

ALTER TABLE ONLY public.oauth_refresh_tokens
    ADD CONSTRAINT oauth_refresh_tokens_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_organization_id_email_unique UNIQUE (organization_id, email);

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.members
    ADD CONSTRAINT organization_user_organization_id_user_id_unique UNIQUE (organization_id, user_id);

ALTER TABLE ONLY public.members
    ADD CONSTRAINT organization_user_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_user_id_unique UNIQUE (project_id, user_id);

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_share_secret_unique UNIQUE (share_secret);

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_pkey PRIMARY KEY (sequence);

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_pkey PRIMARY KEY (entry_uuid, tag);

ALTER TABLE ONLY public.telescope_entries
    ADD CONSTRAINT telescope_entries_uuid_unique UNIQUE (uuid);

ALTER TABLE ONLY public.telescope_monitoring
    ADD CONSTRAINT telescope_monitoring_pkey PRIMARY KEY (tag);

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_pkey PRIMARY KEY (id);

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

CREATE INDEX audits_actor_id_actor_type_index ON public.audits USING btree (actor_id, actor_type);

CREATE INDEX audits_auditable_type_auditable_id_index ON public.audits USING btree (auditable_type, auditable_id);

CREATE INDEX audits_created_at_index ON public.audits USING btree (created_at DESC);

CREATE INDEX audits_owner_organization_id_index ON public.audits USING btree (owner_organization_id);

CREATE INDEX audits_owner_user_id_index ON public.audits USING btree (owner_user_id);

CREATE INDEX clients_organization_created_id_index ON public.clients USING btree (organization_id, created_at DESC, id);

CREATE INDEX goals_organization_id_member_id_index ON public.goals USING btree (organization_id, member_id);

CREATE INDEX goals_organization_id_type_index ON public.goals USING btree (organization_id, type);

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);

CREATE INDEX members_organization_created_id_index ON public.members USING btree (organization_id, created_at DESC, id);

CREATE INDEX members_user_id_index ON public.members USING btree (user_id);

CREATE INDEX oauth_access_tokens_client_id_index ON public.oauth_access_tokens USING btree (client_id);

CREATE INDEX oauth_access_tokens_user_id_index ON public.oauth_access_tokens USING btree (user_id);

CREATE INDEX oauth_auth_codes_client_id_index ON public.oauth_auth_codes USING btree (client_id);

CREATE INDEX oauth_auth_codes_user_id_index ON public.oauth_auth_codes USING btree (user_id);

CREATE INDEX oauth_clients_owner_id_owner_type_index ON public.oauth_clients USING btree (owner_id, owner_type);

CREATE INDEX oauth_device_codes_client_id_index ON public.oauth_device_codes USING btree (client_id);

CREATE INDEX oauth_device_codes_user_id_index ON public.oauth_device_codes USING btree (user_id);

CREATE INDEX oauth_refresh_tokens_access_token_id_index ON public.oauth_refresh_tokens USING btree (access_token_id);

CREATE INDEX organizations_user_id_index ON public.organizations USING btree (user_id);

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);

CREATE INDEX project_members_member_id_index ON public.project_members USING btree (member_id);

CREATE INDEX project_members_user_id_index ON public.project_members USING btree (user_id);

CREATE INDEX projects_client_id_index ON public.projects USING btree (client_id);

CREATE INDEX projects_organization_created_id_index ON public.projects USING btree (organization_id, created_at DESC, id);

CREATE INDEX reports_organization_created_id_index ON public.reports USING btree (organization_id, created_at DESC, id);

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);

CREATE INDEX tags_created_at_index ON public.tags USING btree (created_at);

CREATE INDEX tags_organization_created_id_index ON public.tags USING btree (organization_id, created_at DESC, id);

CREATE INDEX tasks_organization_created_id_index ON public.tasks USING btree (organization_id, created_at DESC, id);

CREATE INDEX tasks_project_id_index ON public.tasks USING btree (project_id);

CREATE INDEX telescope_entries_batch_id_index ON public.telescope_entries USING btree (batch_id);

CREATE INDEX telescope_entries_created_at_index ON public.telescope_entries USING btree (created_at);

CREATE INDEX telescope_entries_family_hash_index ON public.telescope_entries USING btree (family_hash);

CREATE INDEX telescope_entries_tags_tag_index ON public.telescope_entries_tags USING btree (tag);

CREATE INDEX telescope_entries_type_should_display_on_index_index ON public.telescope_entries USING btree (type, should_display_on_index);

CREATE INDEX time_entries_active_member_index ON public.time_entries USING btree (member_id) WHERE ("end" IS NULL);

CREATE INDEX time_entries_billable_index ON public.time_entries USING btree (billable);

CREATE INDEX time_entries_client_id_index ON public.time_entries USING btree (client_id);

CREATE INDEX time_entries_created_at_index ON public.time_entries USING btree (created_at DESC);

CREATE INDEX time_entries_end_index ON public.time_entries USING btree ("end");

CREATE INDEX time_entries_member_start_index ON public.time_entries USING btree (member_id, start);

CREATE INDEX time_entries_organization_start_id_index ON public.time_entries USING btree (organization_id, start DESC, id);

CREATE INDEX time_entries_project_id_index ON public.time_entries USING btree (project_id);

CREATE INDEX time_entries_start_index ON public.time_entries USING btree (start);

CREATE INDEX time_entries_tags_gin_index ON public.time_entries USING gin (tags);

CREATE INDEX time_entries_task_id_index ON public.time_entries USING btree (task_id);

CREATE INDEX time_entries_user_organization_start_index ON public.time_entries USING btree (user_id, organization_id, start);

CREATE INDEX users_current_team_id_index ON public.users USING btree (current_team_id);

CREATE UNIQUE INDEX users_email_unique ON public.users USING btree (email) WHERE (is_placeholder = false);

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_owner_organization_id_foreign FOREIGN KEY (owner_organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_owner_user_id_foreign FOREIGN KEY (owner_user_id) REFERENCES public.users(id) ON DELETE CASCADE;

ALTER TABLE ONLY public.clients
    ADD CONSTRAINT clients_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.goals
    ADD CONSTRAINT goals_member_id_foreign FOREIGN KEY (member_id) REFERENCES public.members(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.goals
    ADD CONSTRAINT goals_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.members
    ADD CONSTRAINT members_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.members
    ADD CONSTRAINT members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.oauth_access_tokens
    ADD CONSTRAINT oauth_access_tokens_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.oauth_clients(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.oauth_access_tokens
    ADD CONSTRAINT oauth_access_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.oauth_auth_codes
    ADD CONSTRAINT oauth_auth_codes_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.oauth_clients(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.oauth_auth_codes
    ADD CONSTRAINT oauth_auth_codes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.users
    ADD CONSTRAINT organizations_current_organization_id_foreign FOREIGN KEY (current_team_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_member_id_foreign FOREIGN KEY (member_id) REFERENCES public.members(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.project_members
    ADD CONSTRAINT project_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.tasks
    ADD CONSTRAINT tasks_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.telescope_entries_tags
    ADD CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid) REFERENCES public.telescope_entries(uuid) ON DELETE CASCADE;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.clients(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_member_id_foreign FOREIGN KEY (member_id) REFERENCES public.members(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_task_id_foreign FOREIGN KEY (task_id) REFERENCES public.tasks(id) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- +goose Down
DROP TABLE IF EXISTS public.audits CASCADE;
DROP TABLE IF EXISTS public.cache CASCADE;
DROP TABLE IF EXISTS public.cache_locks CASCADE;
DROP TABLE IF EXISTS public.clients CASCADE;
DROP TABLE IF EXISTS public.failed_jobs CASCADE;
DROP TABLE IF EXISTS public.goals CASCADE;
DROP TABLE IF EXISTS public.jobs CASCADE;
DROP TABLE IF EXISTS public.members CASCADE;
DROP TABLE IF EXISTS public.migrations CASCADE;
DROP TABLE IF EXISTS public.oauth_access_tokens CASCADE;
DROP TABLE IF EXISTS public.oauth_auth_codes CASCADE;
DROP TABLE IF EXISTS public.oauth_clients CASCADE;
DROP TABLE IF EXISTS public.oauth_device_codes CASCADE;
DROP TABLE IF EXISTS public.oauth_refresh_tokens CASCADE;
DROP TABLE IF EXISTS public.organization_invitations CASCADE;
DROP TABLE IF EXISTS public.organizations CASCADE;
DROP TABLE IF EXISTS public.password_reset_tokens CASCADE;
DROP TABLE IF EXISTS public.personal_access_tokens CASCADE;
DROP TABLE IF EXISTS public.project_members CASCADE;
DROP TABLE IF EXISTS public.projects CASCADE;
DROP TABLE IF EXISTS public.reports CASCADE;
DROP TABLE IF EXISTS public.sessions CASCADE;
DROP TABLE IF EXISTS public.tags CASCADE;
DROP TABLE IF EXISTS public.tasks CASCADE;
DROP TABLE IF EXISTS public.telescope_entries CASCADE;
DROP TABLE IF EXISTS public.telescope_entries_tags CASCADE;
DROP TABLE IF EXISTS public.telescope_monitoring CASCADE;
DROP TABLE IF EXISTS public.time_entries CASCADE;
DROP TABLE IF EXISTS public.users CASCADE;
