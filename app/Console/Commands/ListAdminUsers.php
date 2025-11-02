<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListAdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:list';

    /**
     * The console command description.
     */
    protected $description = 'List all site administrator users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Site Administrator Users');
        $this->newLine();

        $admins = User::where('is_site_admin', true)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'name', 'email', 'username', 'created_at', 'updated_at']);

        if ($admins->isEmpty()) {
            $this->warn('⚠️  No site administrators found.');
            $this->line('   Use: php artisan admin:create to create one.');
            return Command::SUCCESS;
        }

        $tableData = $admins->map(function ($admin) {
            return [
                'ID' => $admin->id,
                'Name' => $admin->name,
                'Email' => $admin->email,
                'Username' => $admin->username,
                'Created' => $admin->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table([
            'ID', 
            'Name', 
            'Email', 
            'Username', 
            'Created'
        ], $tableData);

        $this->newLine();
        $this->info("✅ Found {$admins->count()} administrator(s)");

        return Command::SUCCESS;
    }
}
