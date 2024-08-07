<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FailedJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FailedJob>
 */
class FailedJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = $this->faker->uuid;

        return [
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => [
                'uuid' => $uuid,
                'displayName' => 'App\Jobs\Test\TestJob',
                'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                'maxTries' => null,
                'maxExceptions' => null,
                'failOnTimeout' => false,
                'backoff' => null,
                'timeout' => null,
                'retryUntil' => null,
                'data' => [
                    'commandName' => 'App\Jobs\Test\TestJob',
                    'command' => 'O:21:"App\Jobs\Test\TestJob":3:{s:27:" App\Jobs\Test\TestJob user";O:45:"Illuminate\Contracts\Database\ModelIdentifier":5:{s:5:"class";s:15:"App\Models\User";s:2:"id";s:36:"b301b879-8427-401f-aea2-566da54b23e0";s:9:"relations";a:0:{}s:10:"connection";s:5:"pgsql";s:15:"collectionClass";N;}s:30:" App\Jobs\Test\TestJob message";s:17:"Test job message.";s:27:" App\Jobs\Test\TestJob fail";b:1;}',
                ],
            ],
            'exception' => 'Exception: TestJob failed. in /var/www/html/app/Jobs/Test/TestJob.php:50
Stack trace:
#0 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): App\Jobs\Test\TestJob->handle()
#1 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/Util.php(41): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#2 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(93): Illuminate\Container\Util::unwrapIfClosure()
#3 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()
#4 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php(662): Illuminate\Container\BoundMethod::call()
#5 /var/www/html/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(128): Illuminate\Container\Container->call()
#6 /var/www/html/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(144): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()
#7 /var/www/html/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(119): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()
#8 /var/www/html/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(132): Illuminate\Pipeline\Pipeline->then()
#9 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(124): Illuminate\Bus\Dispatcher->dispatchNow()
#10 /var/www/html/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(144): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()
#11 /var/www/html/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(119): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()
#12 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(123): Illuminate\Pipeline\Pipeline->then()
#13 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(71): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()
#14 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()
#15 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(439): Illuminate\Queue\Jobs\Job->fire()
#16 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(389): Illuminate\Queue\Worker->process()
#17 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(176): Illuminate\Queue\Worker->runJob()
#18 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(139): Illuminate\Queue\Worker->daemon()
#19 /var/www/html/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(122): Illuminate\Queue\Console\WorkCommand->runWorker()
#20 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()
#21 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/Util.php(41): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()
#22 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(93): Illuminate\Container\Util::unwrapIfClosure()
#23 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()
#24 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php(662): Illuminate\Container\BoundMethod::call()
#25 /var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php(213): Illuminate\Container\Container->call()
#26 /var/www/html/vendor/symfony/console/Command/Command.php(279): Illuminate\Console\Command->execute()
#27 /var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php(182): Symfony\Component\Console\Command\Command->run()
#28 /var/www/html/vendor/symfony/console/Application.php(1047): Illuminate\Console\Command->run()
#29 /var/www/html/vendor/symfony/console/Application.php(316): Symfony\Component\Console\Application->doRunCommand()
#30 /var/www/html/vendor/symfony/console/Application.php(167): Symfony\Component\Console\Application->doRun()
#31 /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(196): Symfony\Component\Console\Application->run()
#32 /var/www/html/artisan(35): Illuminate\Foundation\Console\Kernel->handle()
#33 {main}',
            'failed_at' => $this->faker->dateTime(),
        ];
    }
}
