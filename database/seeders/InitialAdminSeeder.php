<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialAdminSeeder extends Seeder
{
    /**
     * Create the first administrator user when no users exist.
     * Uses a random password printed once to console (no known default in code).
     */
    public function run(): void
    {
        if (User::count() > 0) {
            $this->command->info('Users already exist. Skipping initial admin creation.');
            $this->command->table(
                ['Email'],
                User::query()->pluck('email')->map(fn ($email) => [$email])->toArray()
            );
            return;
        }

        $password = Str::password(24, letters: true, numbers: true, symbols: true);

        User::create([
            'name' => 'System Admin',
            'email' => 'admin@kalfa.me',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->command->info('Initial admin user created.');
        $this->command->warn('Login: admin@kalfa.me');
        $this->command->warn('Password (change immediately after first login): ' . $password);
    }
}
