const Ziggy = {
    'url': 'http://solidtime.test',
    'port': null,
    'defaults': {},
    'routes': {
        'scramble.docs.index': {
            'uri': 'docs/api.json',
            'methods': ['GET', 'HEAD'],
        },
        'scramble.docs.api': { 'uri': 'docs/api', 'methods': ['GET', 'HEAD'] },
        'filament.exports.download': {
            'uri': 'filament/exports/{export}/download',
            'methods': ['GET', 'HEAD'],
            'parameters': ['export'],
            'bindings': { 'export': 'id' },
        },
        'filament.imports.failed-rows.download': {
            'uri': 'filament/imports/{import}/failed-rows/download',
            'methods': ['GET', 'HEAD'],
            'parameters': ['import'],
            'bindings': { 'import': 'id' },
        },
        'filament.admin.auth.logout': {
            'uri': 'admin/logout',
            'methods': ['POST'],
        },
        'filament.admin.pages.dashboard': {
            'uri': 'admin',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.clients.index': {
            'uri': 'admin/clients',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.clients.create': {
            'uri': 'admin/clients/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.clients.edit': {
            'uri': 'admin/clients/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.organizations.index': {
            'uri': 'admin/organizations',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.organizations.create': {
            'uri': 'admin/organizations/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.organizations.edit': {
            'uri': 'admin/organizations/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.projects.index': {
            'uri': 'admin/projects',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.projects.create': {
            'uri': 'admin/projects/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.projects.edit': {
            'uri': 'admin/projects/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.tags.index': {
            'uri': 'admin/tags',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.tags.create': {
            'uri': 'admin/tags/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.tags.edit': {
            'uri': 'admin/tags/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.tasks.index': {
            'uri': 'admin/tasks',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.tasks.create': {
            'uri': 'admin/tasks/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.tasks.edit': {
            'uri': 'admin/tasks/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.time-entries.index': {
            'uri': 'admin/time-entries',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.time-entries.create': {
            'uri': 'admin/time-entries/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.time-entries.edit': {
            'uri': 'admin/time-entries/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'filament.admin.resources.users.index': {
            'uri': 'admin/users',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.users.create': {
            'uri': 'admin/users/create',
            'methods': ['GET', 'HEAD'],
        },
        'filament.admin.resources.users.edit': {
            'uri': 'admin/users/{record}/edit',
            'methods': ['GET', 'HEAD'],
            'parameters': ['record'],
        },
        'login': { 'uri': 'login', 'methods': ['GET', 'HEAD'] },
        'logout': { 'uri': 'logout', 'methods': ['POST'] },
        'password.request': {
            'uri': 'forgot-password',
            'methods': ['GET', 'HEAD'],
        },
        'password.reset': {
            'uri': 'reset-password/{token}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['token'],
        },
        'password.email': { 'uri': 'forgot-password', 'methods': ['POST'] },
        'password.update': { 'uri': 'reset-password', 'methods': ['POST'] },
        'register': { 'uri': 'register', 'methods': ['GET', 'HEAD'] },
        'verification.notice': {
            'uri': 'email/verify',
            'methods': ['GET', 'HEAD'],
        },
        'verification.verify': {
            'uri': 'email/verify/{id}/{hash}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['id', 'hash'],
        },
        'verification.send': {
            'uri': 'email/verification-notification',
            'methods': ['POST'],
        },
        'user-profile-information.update': {
            'uri': 'user/profile-information',
            'methods': ['PUT'],
        },
        'user-password.update': { 'uri': 'user/password', 'methods': ['PUT'] },
        'password.confirmation': {
            'uri': 'user/confirmed-password-status',
            'methods': ['GET', 'HEAD'],
        },
        'password.confirm': {
            'uri': 'user/confirm-password',
            'methods': ['POST'],
        },
        'two-factor.login': {
            'uri': 'two-factor-challenge',
            'methods': ['GET', 'HEAD'],
        },
        'two-factor.enable': {
            'uri': 'user/two-factor-authentication',
            'methods': ['POST'],
        },
        'two-factor.confirm': {
            'uri': 'user/confirmed-two-factor-authentication',
            'methods': ['POST'],
        },
        'two-factor.disable': {
            'uri': 'user/two-factor-authentication',
            'methods': ['DELETE'],
        },
        'two-factor.qr-code': {
            'uri': 'user/two-factor-qr-code',
            'methods': ['GET', 'HEAD'],
        },
        'two-factor.secret-key': {
            'uri': 'user/two-factor-secret-key',
            'methods': ['GET', 'HEAD'],
        },
        'two-factor.recovery-codes': {
            'uri': 'user/two-factor-recovery-codes',
            'methods': ['GET', 'HEAD'],
        },
        'profile.show': { 'uri': 'user/profile', 'methods': ['GET', 'HEAD'] },
        'other-browser-sessions.destroy': {
            'uri': 'user/other-browser-sessions',
            'methods': ['DELETE'],
        },
        'current-user-photo.destroy': {
            'uri': 'user/profile-photo',
            'methods': ['DELETE'],
        },
        'current-user.destroy': { 'uri': 'user', 'methods': ['DELETE'] },
        'teams.create': { 'uri': 'teams/create', 'methods': ['GET', 'HEAD'] },
        'teams.store': { 'uri': 'teams', 'methods': ['POST'] },
        'teams.show': {
            'uri': 'teams/{team}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['team'],
        },
        'teams.update': {
            'uri': 'teams/{team}',
            'methods': ['PUT'],
            'parameters': ['team'],
        },
        'teams.destroy': {
            'uri': 'teams/{team}',
            'methods': ['DELETE'],
            'parameters': ['team'],
        },
        'current-team.update': { 'uri': 'current-team', 'methods': ['PUT'] },
        'team-members.store': {
            'uri': 'teams/{team}/members',
            'methods': ['POST'],
            'parameters': ['team'],
        },
        'team-members.update': {
            'uri': 'teams/{team}/members/{user}',
            'methods': ['PUT'],
            'parameters': ['team', 'user'],
        },
        'team-members.destroy': {
            'uri': 'teams/{team}/members/{user}',
            'methods': ['DELETE'],
            'parameters': ['team', 'user'],
        },
        'team-invitations.accept': {
            'uri': 'team-invitations/{invitation}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['invitation'],
        },
        'team-invitations.destroy': {
            'uri': 'team-invitations/{invitation}',
            'methods': ['DELETE'],
            'parameters': ['invitation'],
        },
        'passport.token': { 'uri': 'oauth/token', 'methods': ['POST'] },
        'passport.authorizations.authorize': {
            'uri': 'oauth/authorize',
            'methods': ['GET', 'HEAD'],
        },
        'passport.token.refresh': {
            'uri': 'oauth/token/refresh',
            'methods': ['POST'],
        },
        'passport.authorizations.approve': {
            'uri': 'oauth/authorize',
            'methods': ['POST'],
        },
        'passport.authorizations.deny': {
            'uri': 'oauth/authorize',
            'methods': ['DELETE'],
        },
        'passport.tokens.index': {
            'uri': 'oauth/tokens',
            'methods': ['GET', 'HEAD'],
        },
        'passport.tokens.destroy': {
            'uri': 'oauth/tokens/{token_id}',
            'methods': ['DELETE'],
            'parameters': ['token_id'],
        },
        'passport.clients.index': {
            'uri': 'oauth/clients',
            'methods': ['GET', 'HEAD'],
        },
        'passport.clients.store': {
            'uri': 'oauth/clients',
            'methods': ['POST'],
        },
        'passport.clients.update': {
            'uri': 'oauth/clients/{client_id}',
            'methods': ['PUT'],
            'parameters': ['client_id'],
        },
        'passport.clients.destroy': {
            'uri': 'oauth/clients/{client_id}',
            'methods': ['DELETE'],
            'parameters': ['client_id'],
        },
        'passport.scopes.index': {
            'uri': 'oauth/scopes',
            'methods': ['GET', 'HEAD'],
        },
        'passport.personal.tokens.index': {
            'uri': 'oauth/personal-access-tokens',
            'methods': ['GET', 'HEAD'],
        },
        'passport.personal.tokens.store': {
            'uri': 'oauth/personal-access-tokens',
            'methods': ['POST'],
        },
        'passport.personal.tokens.destroy': {
            'uri': 'oauth/personal-access-tokens/{token_id}',
            'methods': ['DELETE'],
            'parameters': ['token_id'],
        },
        'livewire.update': { 'uri': 'livewire/update', 'methods': ['POST'] },
        'livewire.upload-file': {
            'uri': 'livewire/upload-file',
            'methods': ['POST'],
        },
        'livewire.preview-file': {
            'uri': 'livewire/preview-file/{filename}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['filename'],
        },
        'ignition.healthCheck': {
            'uri': '_ignition/health-check',
            'methods': ['GET', 'HEAD'],
        },
        'ignition.executeSolution': {
            'uri': '_ignition/execute-solution',
            'methods': ['POST'],
        },
        'ignition.updateConfig': {
            'uri': '_ignition/update-config',
            'methods': ['POST'],
        },
        'api.v1.organizations.show': {
            'uri': 'api/v1/organizations/{organization}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.organizations.update': {
            'uri': 'api/v1/organizations/{organization}',
            'methods': ['PUT'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.users.index': {
            'uri': 'api/v1/organizations/{organization}/members',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.users.invite-placeholder': {
            'uri': 'api/v1/organizations/{organization}/members/{user}/invite-placeholder',
            'methods': ['POST'],
            'parameters': ['organization', 'user'],
            'bindings': { 'organization': 'id', 'user': 'id' },
        },
        'api.v1.projects.index': {
            'uri': 'api/v1/organizations/{organization}/projects',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.projects.show': {
            'uri': 'api/v1/organizations/{organization}/projects/{project}',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization', 'project'],
            'bindings': { 'organization': 'id', 'project': 'id' },
        },
        'api.v1.projects.store': {
            'uri': 'api/v1/organizations/{organization}/projects',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.projects.update': {
            'uri': 'api/v1/organizations/{organization}/projects/{project}',
            'methods': ['PUT'],
            'parameters': ['organization', 'project'],
            'bindings': { 'organization': 'id', 'project': 'id' },
        },
        'api.v1.projects.destroy': {
            'uri': 'api/v1/organizations/{organization}/projects/{project}',
            'methods': ['DELETE'],
            'parameters': ['organization', 'project'],
            'bindings': { 'organization': 'id', 'project': 'id' },
        },
        'api.v1.time-entries.index': {
            'uri': 'api/v1/organizations/{organization}/time-entries',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.time-entries.store': {
            'uri': 'api/v1/organizations/{organization}/time-entries',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.time-entries.update': {
            'uri': 'api/v1/organizations/{organization}/time-entries/{timeEntry}',
            'methods': ['PUT'],
            'parameters': ['organization', 'timeEntry'],
            'bindings': { 'organization': 'id', 'timeEntry': 'id' },
        },
        'api.v1.time-entries.destroy': {
            'uri': 'api/v1/organizations/{organization}/time-entries/{timeEntry}',
            'methods': ['DELETE'],
            'parameters': ['organization', 'timeEntry'],
            'bindings': { 'organization': 'id', 'timeEntry': 'id' },
        },
        'api.v1.tags.index': {
            'uri': 'api/v1/organizations/{organization}/tags',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.tags.store': {
            'uri': 'api/v1/organizations/{organization}/tags',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.tags.update': {
            'uri': 'api/v1/organizations/{organization}/tags/{tag}',
            'methods': ['PUT'],
            'parameters': ['organization', 'tag'],
            'bindings': { 'organization': 'id', 'tag': 'id' },
        },
        'api.v1.tags.destroy': {
            'uri': 'api/v1/organizations/{organization}/tags/{tag}',
            'methods': ['DELETE'],
            'parameters': ['organization', 'tag'],
            'bindings': { 'organization': 'id', 'tag': 'id' },
        },
        'api.v1.clients.index': {
            'uri': 'api/v1/organizations/{organization}/clients',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.clients.store': {
            'uri': 'api/v1/organizations/{organization}/clients',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.clients.update': {
            'uri': 'api/v1/organizations/{organization}/clients/{client}',
            'methods': ['PUT'],
            'parameters': ['organization', 'client'],
            'bindings': { 'organization': 'id', 'client': 'id' },
        },
        'api.v1.clients.destroy': {
            'uri': 'api/v1/organizations/{organization}/clients/{client}',
            'methods': ['DELETE'],
            'parameters': ['organization', 'client'],
            'bindings': { 'organization': 'id', 'client': 'id' },
        },
        'api.v1.tasks.index': {
            'uri': 'api/v1/organizations/{organization}/tasks',
            'methods': ['GET', 'HEAD'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.tasks.store': {
            'uri': 'api/v1/organizations/{organization}/tasks',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'api.v1.tasks.update': {
            'uri': 'api/v1/organizations/{organization}/tasks/{task}',
            'methods': ['PUT'],
            'parameters': ['organization', 'task'],
            'bindings': { 'organization': 'id', 'task': 'id' },
        },
        'api.v1.tasks.destroy': {
            'uri': 'api/v1/organizations/{organization}/tasks/{task}',
            'methods': ['DELETE'],
            'parameters': ['organization', 'task'],
            'bindings': { 'organization': 'id', 'task': 'id' },
        },
        'api.v1.import.import': {
            'uri': 'api/v1/organizations/{organization}/import',
            'methods': ['POST'],
            'parameters': ['organization'],
            'bindings': { 'organization': 'id' },
        },
        'dashboard': { 'uri': 'dashboard', 'methods': ['GET', 'HEAD'] },
        'telescope': {
            'uri': 'telescope/{view?}',
            'methods': ['GET', 'HEAD'],
            'wheres': { 'view': '(.*)' },
            'parameters': ['view'],
        },
        'api.': {
            'uri': 'api/{fallbackPlaceholder}',
            'methods': ['GET', 'HEAD'],
            'wheres': { 'fallbackPlaceholder': '.*' },
            'parameters': ['fallbackPlaceholder'],
        },
    },
};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
