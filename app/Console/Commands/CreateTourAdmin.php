<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTourAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:tour-admin {name} {email} {password} {--username=} {--mobile=} {--address=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tour admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $username = $this->option('username') ?: strtolower(str_replace(' ', '.', $name));
        $mobile = $this->option('mobile') ?: null;
        $address = $this->option('address') ?: null;

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($password),
                'mobile' => $mobile,
                'address' => $address,
            ]);
            
            $user->assignRole('tour admin');
            
            $this->info("Tour admin created successfully!");
            $this->info("Username: {$username}");
            $this->info("Email: {$email}");
            
        } catch (\Exception $e) {
            $this->error("Failed to create tour admin: {$e->getMessage()}");
        }
    }
}
