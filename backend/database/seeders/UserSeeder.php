<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $division = Division::where('name', 'Laboratory Services Division')->first();
        $sections = Section::all();

        $getSection = function (string $name) use ($sections) {
            return $sections->firstWhere('name', $name) ?? $sections->first();
        };

        $secChem = $getSection('Soil Chemistry');
        $secPhys = $getSection('Soil Physics');
        $secWater = $getSection('Water Chemistry');
        $secRst = $getSection('Rapid Soil Test');
        $secTeim = $getSection('TEIM');
        $secCust = $getSection('Customer Center');

        $fixed = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'email' => 'admin@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secChem?->id,
                'status' => 'ACTIVE',
                'role' => 'ADMIN',
                'position' => 'IT Officer III',
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'name' => 'Maria Santos',
                'email' => 'admin2@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secTeim?->id,
                'status' => 'ACTIVE',
                'role' => 'ADMIN',
                'position' => 'Chief Information Officer',
            ],
            [
                'first_name' => 'Carlo',
                'last_name' => 'Reyes',
                'name' => 'Carlo Reyes',
                'email' => 'staff@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secCust?->id,
                'status' => 'ACTIVE',
                'role' => 'STAFF',
                'position' => 'Computer Maintenance Technologist',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'name' => 'Ana Cruz',
                'email' => 'staff2@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secRst?->id,
                'status' => 'ACTIVE',
                'role' => 'STAFF',
                'position' => 'IT Support Specialist',
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Dela Cruz',
                'name' => 'John Dela Cruz',
                'email' => 'user@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secChem?->id,
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Laboratory Analyst',
            ],
            [
                'first_name' => 'Liza',
                'last_name' => 'Fernandez',
                'name' => 'Liza Fernandez',
                'email' => 'liza.fernandez@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secPhys?->id,
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Chemist III',
            ],
            [
                'first_name' => 'Patricia',
                'last_name' => 'Villanueva',
                'name' => 'Patricia Villanueva',
                'email' => 'patricia.villanueva@lims.gov.ph',
                'password' => 'password',
                'division_id' => $division?->id,
                'section_id' => $secWater?->id,
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Microbiologist II',
            ],
        ];

        foreach ($fixed as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $targetTotal = 20;
        $current = User::count();

        if ($current < $targetTotal) {
            User::factory()
                ->count($targetTotal - $current)
                ->create();
        }
    }
}
