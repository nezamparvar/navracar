<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PipelineStageSeeder::class,
            LossReasonSeeder::class,
            MessageTemplateSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
