<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * User provider that only resolves non-placeholder users.
 *
 * Placeholder users are created by imports and when members are removed from an
 * organization. They can share an email address with a real user, so resolving a user by
 * email can return a placeholder instead of the real account. The login flow filters them
 * out explicitly, but the password broker and the guard credential checks (for example the
 * password confirmation) resolve users through the configured user provider.
 *
 * Registered as the "eloquent" provider driver in the AuthServiceProvider, so it replaces the
 * built-in one for every provider in config/auth.php.
 */
class ActiveUserProvider extends EloquentUserProvider
{
    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    #[\Override]
    protected function newModelQuery($model = null): Builder
    {
        $query = parent::newModelQuery($model);
        $query->getQuery()->where('is_placeholder', '=', false);

        return $query;
    }
}
