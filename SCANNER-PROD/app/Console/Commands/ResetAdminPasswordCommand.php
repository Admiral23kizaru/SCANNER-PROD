<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scanup:reset-admin-password
                            {--email= : User email (overrides ADMIN_EMAIL)}
                            {--password= : New password (overrides ADMIN_PASSWORD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user password from env ADMIN_EMAIL and ADMIN_PASSWORD (or --email/--password). No hardcoded credentials.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?: env('ADMIN_EMAIL');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD');

        if (empty($email) || $email === '') {
            $this->error('Email is required. Set ADMIN_EMAIL in .env or pass --email=');
            return self::FAILURE;
        }

        if (empty($password) || $password === '') {
            $this->error('Password is required. Set ADMIN_PASSWORD in .env or pass --password=');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found in database.");
            return self::FAILURE;
        }

        $user->password = $password;
        $user->save();

        $this->info("Password updated for user [{$email}] (bcrypt hashed).");
        return self::SUCCESS;
    }
}
