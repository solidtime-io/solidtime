<?php

declare(strict_types=1);

namespace App\Console\Commands\SelfHost;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;

class SelfHostGenerateKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'self-host:generate-keys
                { --length=4096 : The length of the passport private key }
                { --multi-line : Whether to output the keys in multiple lines }
                { --format=env : The format of the output (env, yaml) }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate random keys for the env variables.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $key = RSA::createKey((int) $this->option('length'));
        $multiLine = (bool) $this->option('multi-line');

        $publicKey = (string) $key->getPublicKey();
        $privateKey = (string) $key;
        $appKey = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));

        if ($format === 'env') {
            $this->line('APP_KEY="'.$appKey.'"');
            if ($multiLine) {
                $this->line('PASSPORT_PRIVATE_KEY="'.Str::replace("\r\n", "\n", $privateKey).'"');
                $this->line('PASSPORT_PUBLIC_KEY="'.Str::replace("\r\n", "\n", $publicKey).'"');
            } else {
                $this->line('PASSPORT_PRIVATE_KEY="'.Str::replace("\r\n", '\n', $privateKey).'"');
                $this->line('PASSPORT_PUBLIC_KEY="'.Str::replace("\r\n", '\n', $publicKey).'"');
            }
        } elseif ($format === 'yaml') {
            $this->line('APP_KEY: "'.$appKey.'"');
            $this->line("PASSPORT_PRIVATE_KEY: |\n  ".Str::replace("\r\n", "\n  ", $privateKey));
            $this->line("PASSPORT_PUBLIC_KEY: |\n  ".Str::replace("\r\n", "\n  ", $publicKey));
        } else {
            $this->error('Invalid format');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
