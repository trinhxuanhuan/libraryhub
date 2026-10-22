<?php

namespace App\Console\Commands;

use App\Services\Crypto\LearningBcrypt;
use Illuminate\Console\Command;

class StudyBcryptCommand extends Command
{
    protected $signature = 'bcrypt:study {--cost=4 : Educational cost from 4 to 12}';

    protected $description = 'Run and compare the pure-PHP educational bcrypt algorithm';

    public function handle(LearningBcrypt $bcrypt): int
    {
        $cost = filter_var($this->option('cost'), FILTER_VALIDATE_INT);

        if ($cost === false || $cost < 4 || $cost > 12) {
            $this->error('Use a cost between 4 and 12. Begin with --cost=4.');

            return self::FAILURE;
        }

        // Fixed demonstration value only; never use actual account passwords here.
        $password = 'DemoPassword123!';

        $start = microtime(true);
        $hash = $bcrypt->hash($password, $cost);
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->line('Educational bcrypt hash: '.$hash);
        $this->line('Hash length: '.strlen($hash).' characters');
        $this->line('Custom bcrypt verify: '.($bcrypt->verify($password, $hash) ? 'PASS' : 'FAIL'));
        $this->line('PHP interoperability: '.(password_verify($password, $hash) ? 'PASS' : 'FAIL'));
        $this->line('Elapsed for one hash: '.$elapsed.' ms');

        return self::SUCCESS;
    }
}
