<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScanUpSeeder extends Seeder
{
    /**
     * Seed roles and users from .env only. No hardcoded credentials.
     * Set SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD (and optionally SEED_TEACHER_*, SEED_GUARD_*)
     * in .env. Passwords are bcrypt-hashed via User model.
     */
    public function run(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Guard', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedUserIfSet(1, env('SEED_ADMIN_EMAIL'), env('SEED_ADMIN_PASSWORD'), 'System Admin');
        $this->seedUserIfSet(2, env('SEED_TEACHER_EMAIL'), env('SEED_TEACHER_PASSWORD'), 'Jane Teacher');
        $this->seedUserIfSet(3, env('SEED_GUARD_EMAIL'), env('SEED_GUARD_PASSWORD'), 'Guard Post One');
    }

    private function seedUserIfSet(int $roleId, ?string $email, ?string $password, string $name): void
    {
        if (empty($email) || $email === '' || empty($password) || $password === '') {
            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->role_id = $roleId;
        $user->password = $password;
        $user->save();
    }
}
