<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Section;
use Illuminate\Database\Seeder;

class DivisionOfficeSectionSeeder extends Seeder
{
    public function run(): void
    {
        $division = Division::firstOrCreate(
            ['name' => 'Laboratory Services Division'],
            ['code' => 'LSD', 'description' => 'Laboratory Services Division']
        );

        $sections = [
            'Soil Chemistry',
            'Soil Physics',
            'Water Chemistry',
            'Rapid Soil Test',
            'TEIM',
            'Customer Center',
        ];

        foreach ($sections as $index => $secName) {
            Section::firstOrCreate(
                [
                    'division_id' => $division->id,
                    'name' => $secName,
                ],
                [
                    'code' => 'SEC-' . ($index + 1),
                ]
            );
        }
    }
}
