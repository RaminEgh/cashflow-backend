<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunClearCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear caches, configs and routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running commands...');

        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        $this->call('optimize');

        $this->info('All commands executed successfully!');
    }
}
