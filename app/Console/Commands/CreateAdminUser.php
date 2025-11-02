<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:create 
                            {--name= : The full name of the admin user}
                            {--email= : The email address of the admin user}
                            {--username= : The username of the admin user}
                            {--password= : The password for the admin user}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new site administrator user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Creating Site Administrator Account');
        $this->newLine();

        // Get user input
        $name = $this->option('name') ?: $this->ask('Full Name');
        $email = $this->option('email') ?: $this->ask('Email Address');
        $username = $this->option('username') ?: $this->ask('Username');
        $password = $this->option('password') ?: $this->secret('Password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("   • $error");
            }
            return Command::FAILURE;
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            $this->error("❌ A user with email '{$email}' already exists.");
            return Command::FAILURE;
        }

        // Check if username already exists
        if (User::where('username', $username)->exists()) {
            $this->error("❌ A user with username '{$username}' already exists.");
            return Command::FAILURE;
        }

        // Confirm admin creation
        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Name', $name],
            ['Email', $email],
            ['Username', $username],
            ['Admin Privileges', '✅ Yes'],
        ]);

        if (!$this->confirm('Create this admin user?', true)) {
            $this->info('❌ Admin user creation cancelled.');
            return Command::SUCCESS;
        }

        try {
            // Create the admin user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_site_admin' => true,
            ]);

            $this->newLine();
            $this->info("✅ Admin user created successfully!");
            $this->line("   • ID: {$user->id}");
            $this->line("   • Name: {$user->name}");
            $this->line("   • Email: {$user->email}");
            $this->line("   • Username: {$user->username}");
            $this->line("   • Admin: ✅ Yes");
            $this->newLine();
            
            $this->warn('🔒 Please store the login credentials securely and consider enabling 2FA.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to create admin user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
