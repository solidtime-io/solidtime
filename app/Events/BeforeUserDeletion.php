<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BeforeUserDeletion
{
    use Dispatchable;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
