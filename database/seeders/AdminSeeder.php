<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Dinonaktifkan demi keamanan. Admin pertama harus dibuat via command: php artisan users:reset atau php artisan admin:create
    }
}
