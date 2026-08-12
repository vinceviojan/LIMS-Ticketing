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
            ['name' => 'Office of the Chief / Admin Staff', 'code' => 'OCS'],
            ['name' => 'LIMS',                              'code' => 'LIMS'],
            ['name' => 'Soil Chemistry Section',            'code' => 'SCS'],
            ['name' => 'Rapid Soil Test Section',           'code' => 'RSTS'],
            ['name' => 'Soil Microbiology Section',         'code' => 'SMS'],
            ['name' => 'Soil Physics Section',              'code' => 'SPS'],
            ['name' => 'Water Chemistry Section',           'code' => 'WCS'],
            ['name' => 'Technical Equipment Instrumentation and Maintenance', 'code' => 'TEIM'],
            ['name' => 'Customer Center',                   'code' => 'CC-LEG'],
        ];

        foreach ($sections as $sec) {
            Section::firstOrCreate(
                ['division_id' => $division->id, 'name' => $sec['name']],
                ['code' => $sec['code']]
            );
        }
    }
}
