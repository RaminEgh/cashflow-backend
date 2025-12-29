<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SetupAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run migration, seeders and initial';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $this->info('setup started...');
            Storage::disk('public_uploads')->makeDirectory('/');
            Storage::disk('private_uploads')->makeDirectory('/');

            $storageLink = public_path('storage');
            if (! File::exists($storageLink)) {
                $this->call('storage:link');
                $this->info('Storage symlink created!');
            } else {
                $this->info('Storage symlink already exists, skipping...');
            }

            $this->info('Storage directories created!');
            if (! Schema::hasTable('cache')) {
                $this->call('migrate:fresh');
                $this->call('db:seed', ['--class' => 'AdminSeeder']);
                $this->call('app:fetch-organs');
                $this->call('app:fetch-deposits');
                $this->call('app:fetch-rahkaran-balances');
                $this->call('db:seed');
            }
            DB::commit();
            $this->info('setup finished...');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error occurred: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
