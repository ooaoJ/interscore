<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [

            'Futsal',
            'Vôlei',
            'Basquete',
            'Handebol',
            'Queimada',
            'Xadrez',
            'Tênis de Mesa',
            'Atletismo',

        ];

        foreach ($sports as $sport) {

            Sport::updateOrCreate(
                [
                    'slug' => Str::slug($sport)
                ],
                [
                    'name' => $sport,
                    'status' => 'active'
                ]
            );

        }
    }
}