<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Production deploy yalnızca SuperAdminSeeder çalıştırır.
     * TestDataSeeder asla buraya eklenmemeli.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
    }
}
