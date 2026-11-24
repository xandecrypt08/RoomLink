<?php

namespace App\Console\Commands;

use App\Actions\EndForgottenSessions as EndForgottenSessionsAction;
use Illuminate\Console\Command;

class EndForgottenSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomlink:end-forgotten-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End classroom sessions that were left running after their class and release their rooms';

    /**
     * Execute the console command.
     */
    public function handle(EndForgottenSessionsAction $endForgottenSessions): int
    {
        $this->info("Ended {$endForgottenSessions->handle()} forgotten session(s).");

        return self::SUCCESS;
    }
}
