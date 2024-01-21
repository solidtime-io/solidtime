const Ziggy = {
    'url': 'http://localhost',
    'port': null,
    'defaults': {},
    'routes': {
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
        'dashboard': { 'uri': 'dashboard', 'methods': ['GET', 'HEAD'] },
    },
};

if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}

export { Ziggy };
