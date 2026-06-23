<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [

            [
                'name' => '6º Ano',
                'code' => '6EF',
                'stage' => 'elementary_2',
                'sort_order' => 1,
            ],

            [
                'name' => '7º Ano',
                'code' => '7EF',
                'stage' => 'elementary_2',
                'sort_order' => 2,
            ],

            [
                'name' => '8º Ano',
                'code' => '8EF',
                'stage' => 'elementary_2',
                'sort_order' => 3,
            ],

            [
                'name' => '9º Ano',
                'code' => '9EF',
                'stage' => 'elementary_2',
                'sort_order' => 4,
            ],

            [
                'name' => '1º Médio',
                'code' => '1EM',
                'stage' => 'high_school',
                'sort_order' => 5,
            ],

            [
                'name' => '2º Médio',
                'code' => '2EM',
                'stage' => 'high_school',
                'sort_order' => 6,
            ],

            [
                'name' => '3º Médio',
                'code' => '3EM',
                'stage' => 'high_school',
                'sort_order' => 7,
            ],

        ];

        foreach ($grades as $grade) {
            Grade::updateOrCreate(
                ['code' => $grade['code']],
                $grade
            );
        }
    }
}