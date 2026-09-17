<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        // login pakai password
        User::updateOrCreate(
          ['email' => 'yanzewty@gmail.com'], 
            [
                'name' => 'Admin',
                'password' => bcrypt('password123'),
            ]
        );

        
        $this->call(ProfileSeeder::class);
    }
}