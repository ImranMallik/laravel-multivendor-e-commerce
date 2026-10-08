<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set; no admin created.');

            return;
        }

        Admin::updateOrCreate(
            ['email' => $email],
            ['name' => config('admin.name'), 'password' => $password, 'status' => true],
        );
    }
}
