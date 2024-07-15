<?php

declare(strict_types=1);

namespace App\Jobs\Test;

use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private User $user;

    private string $message;

    private bool $fail;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $message, bool $fail = false)
    {
        $this->user = $user;
        $this->message = $message;
        $this->fail = $fail;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        Log::debug('TestJob: '.$this->message, [
            'user' => $this->user->getKey(),
        ]);
        if ($this->fail) {
            throw new Exception('TestJob failed.');
        }
    }
}
