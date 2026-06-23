<?php

namespace Database\Seeders;

use App\Models\CompetitionCategory;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class CompetitionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoryA = CompetitionCategory::updateOrCreate(
            ['code' => 'fundamental_678'],
            [
                'name' => 'Categoria 6º, 7º e 8º',
                'description' => 'Turmas do 6º ao 8º ano.',
                'sort_order' => 1,
            ]
        );

        $categoryB = CompetitionCategory::updateOrCreate(
            ['code' => 'maior_9em'],
            [
                'name' => 'Categoria 9º e Ensino Médio',
                'description' => 'Turmas do 9º ano ao 3º médio.',
                'sort_order' => 2,
            ]
        );

        $gradeIdsA = Grade::query()
            ->where('code', '6EF')
            ->orWhere('code', '7EF')
            ->orWhere('code', '8EF')
            ->pluck('id')
            ->toArray();

        $gradeIdsB = Grade::query()
            ->where('code', '9EF')
            ->orWhere('code', '1EM')
            ->orWhere('code', '2EM')
            ->orWhere('code', '3EM')
            ->pluck('id')
            ->toArray();

        $categoryA->grades()->sync($gradeIdsA);
        $categoryB->grades()->sync($gradeIdsB);
    }
}