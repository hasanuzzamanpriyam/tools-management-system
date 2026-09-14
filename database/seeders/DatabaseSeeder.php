<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@tools.com'], [
            'name' => 'Super Admin',
            'password' => 'password',
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $this->call(ToolSeeder::class);

        $buyers = collect([
            ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            ['name' => 'Alan Turing', 'email' => 'alan@example.com'],
            ['name' => 'Grace Hopper', 'email' => 'grace@example.com'],
        ])->map(fn (array $data) => User::firstOrCreate(['email' => $data['email']], [
            'name' => $data['name'],
            'password' => 'password',
            'role' => 'user',
            'email_verified_at' => now(),
        ]));

        $timeSync = Tool::query()->where('slug', 'timesync')->first();

        Purchase::factory()->create([
            'user_id' => $buyers[0]->id,
            'tool_id' => $timeSync?->id ?? Tool::factory(),
            'amount' => 19.99,
            'created_at' => now()->subHours(2),
        ]);

        Purchase::factory()->create([
            'user_id' => $buyers[1]->id,
            'tool_id' => $timeSync?->id ?? Tool::factory(),
            'amount' => 19.99,
            'created_at' => now()->subDay(),
        ]);

        Purchase::factory()->create([
            'user_id' => $buyers[2]->id,
            'tool_id' => $timeSync?->id ?? Tool::factory(),
            'amount' => 29.99,
            'status' => Purchase::STATUS_PAYMENT_FAILED,
            'created_at' => now()->subDays(2),
        ]);
    }
}