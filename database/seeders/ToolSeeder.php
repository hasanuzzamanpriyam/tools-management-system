<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    /**
     * Seed sample tools for local development.
     */
    public function run(): void
    {
        Tool::firstOrCreate(['slug' => 'timesync'], [
            'name' => 'TimeSync',
            'description' => 'Browser extension that syncs your watch, clock and calendar straight from your wrist.',
            'type' => Tool::TYPE_EXTENSION,
            'pricing_model' => Tool::PRICING_ONE_TIME,
            'price' => 19.99,
            'device_limit' => 1,
        ]);
    }
}