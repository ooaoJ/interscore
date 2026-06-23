<?php

namespace Database\Seeders;

use App\Models\PenaltyType;
use Illuminate\Database\Seeder;

class PenaltyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $penalties = [

            [
                'name' => 'Atraso para partida',
                'default_points' => -1,
                'severity' => 'low',
            ],

            [
                'name' => 'Conduta antidesportiva',
                'default_points' => -3,
                'severity' => 'medium',
            ],

            [
                'name' => 'W.O.',
                'default_points' => -5,
                'severity' => 'high',
            ],

            [
                'name' => 'Briga ou agressão',
                'default_points' => -10,
                'severity' => 'critical',
            ],

        ];

        foreach ($penalties as $penalty) {

            PenaltyType::updateOrCreate(
                [
                    'name' => $penalty['name']
                ],
                [
                    'description' => $penalty['name'],
                    'default_points' => $penalty['default_points'],
                    'severity' => $penalty['severity'],
                    'active' => true,
                ]
            );

        }
    }
}