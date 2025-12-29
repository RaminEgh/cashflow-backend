<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        try {
            DB::beginTransaction();
            $this->command->info('setup started...');
            Artisan::call('key:generate');
            Storage::disk('public_uploads')->makeDirectory('/');
            Storage::disk('private_uploads')->makeDirectory('/');

            $storageLink = public_path('storage');
            if (! File::exists($storageLink)) {
                $this->call('storage:link');
                $this->command->info('Storage symlink created!');
            } else {
                $this->command->info('Storage symlink already exists, skipping...');
            }

            $this->command->info('Storage directories created!');
            if (! Schema::hasTable('cache')) {
                $this->call('migrate:fresh');
            }

            $this->call([
                AdminSeeder::class,
                PermissionSeeder::class,
                RoleSeeder::class,
                RolePermissionSeeder::class,
            ]);

            $this->command->info('Fetching data from Rahkaran...');

            Artisan::call('app:fetch-organs');
            $this->command->info('✓ Organs fetched from Rahkaran');

            Artisan::call('app:fetch-deposits');
            $this->command->info('✓ Deposits fetched from Rahkaran');

            Artisan::call('app:fetch-rahkaran-balances');
            $this->command->info('✓ Balances fetched from Rahkaran');

            $this->command->info('All Rahkaran data fetched successfully!');

            DB::commit();
            $this->command->info('setup finished...');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Error occurred: ' . $e->getMessage());
        }
    }
}
