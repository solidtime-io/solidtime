<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\Test;

use App\Jobs\Test\TestJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCaseWithDatabase;
use TiMacDonald\Log\LogEntry;

class TestJobTest extends TestCaseWithDatabase
{
    public function test_logs_debug_message(): void
    {
        // Arrange
        $user = User::factory()->create();
        $message = 'Test message';
        $job = new TestJob($user, $message);

        // Act
        $job->handle();

        // Assert
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'TestJob: '.$message
            && $log->context['user'] === $user->getKey(),
            1
        );
    }

    public function test_can_fail_if_parameter_fail_is_true(): void
    {
        // Arrange
        $user = User::factory()->create();
        $message = 'Test message';
        $job = new TestJob($user, $message, true);

        // Act
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Assert
            $this->assertEquals('TestJob failed.', $e->getMessage());

            return;
        }
        $this->fail('Expected exception not thrown');
    }
}
