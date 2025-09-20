<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AiModel;
use App\Models\SystemPrompt;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Mehedi',
            'email' => 'mehedihasansagor.cse@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $this->call(AiModelSeeder::class);
        $this->call(SystemPromptSeeder::class);
    }
}
