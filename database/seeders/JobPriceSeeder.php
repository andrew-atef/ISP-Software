<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            [
                'task_type' => \App\Enums\TaskType::NewInstall,
                'company_price' => 350.00,
                'tech_price' => 110.00,
            ],
            [
                'task_type' => \App\Enums\TaskType::DropBury,
                'company_price' => 100.00,
                'tech_price' => 35.00,
            ],
            [
                'task_type' => \App\Enums\TaskType::ServiceCall,
                'company_price' => 0.00,
                'tech_price' => 0.00,
            ],
            [
                'task_type' => \App\Enums\TaskType::ServiceChange,
                'company_price' => 0.00,
                'tech_price' => 0.00,
            ],
        ];

        foreach ($prices as $price) {
            \App\Models\JobPrice::updateOrCreate(
                ['task_type' => $price['task_type']],
                [
                    'company_price' => $price['company_price'],
                    'tech_price' => $price['tech_price'],
                ]
            );
        }
    }
}
