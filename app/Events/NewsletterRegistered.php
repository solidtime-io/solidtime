<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NewsletterRegistered
{
    use Dispatchable;

    public string $name;

    public string $email;

    public string $id;

    /**
     * Create a new event instance.
     */
    public function __construct(string $name, string $email, string $id)
    {
        $this->name = $name;
        $this->email = $email;
        $this->id = $id;
    }
}
