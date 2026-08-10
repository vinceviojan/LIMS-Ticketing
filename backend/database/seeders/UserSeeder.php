<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds 3 fixed accounts (admin / staff / user) then generates
     * 20 additional realistic dummy users via the UserFactory.
     */
    public function run(): void
    {
        // ── Fixed / known accounts ────────────────────────────────────
        $fixed = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'email' => 'admin@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'ICT Division',
                'sections' => 'System Administration',
                'status' => 'ACTIVE',
                'role' => 'ADMIN',
                'position' => 'IT Officer III',
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'name' => 'Maria Santos',
                'email' => 'admin2@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'ICT Division',
                'sections' => 'Network Operations',
                'status' => 'ACTIVE',
                'role' => 'ADMIN',
                'position' => 'Chief Information Officer',
            ],
            [
                'first_name' => 'Carlo',
                'last_name' => 'Reyes',
                'name' => 'Carlo Reyes',
                'email' => 'staff@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Technical Support Division',
                'sections' => 'Helpdesk',
                'status' => 'ACTIVE',
                'role' => 'STAFF',
                'position' => 'Computer Maintenance Technologist',
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'name' => 'Ana Cruz',
                'email' => 'staff2@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Technical Support Division',
                'sections' => 'Field Support',
                'status' => 'ACTIVE',
                'role' => 'STAFF',
                'position' => 'IT Support Specialist',
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Dela Cruz',
                'name' => 'John Dela Cruz',
                'email' => 'user@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Laboratory Division',
                'sections' => 'Microbiology',
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Laboratory Analyst',
            ],
            [
                'first_name' => 'Liza',
                'last_name' => 'Fernandez',
                'name' => 'Liza Fernandez',
                'email' => 'liza.fernandez@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Laboratory Division',
                'sections' => 'Chemistry',
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Chemist III',
            ],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Garcia',
                'name' => 'Ryan Garcia',
                'email' => 'ryan.garcia@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Quality Assurance Division',
                'sections' => 'Audit',
                'status' => 'INACTIVE',
                'role' => 'USER',
                'position' => 'Science Research Specialist',
            ],
            [
                'first_name' => 'Patricia',
                'last_name' => 'Villanueva',
                'name' => 'Patricia Villanueva',
                'email' => 'patricia.villanueva@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Environmental Monitoring Division',
                'sections' => 'Water Quality',
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Microbiologist II',
            ],
            [
                'first_name' => 'Jose',
                'last_name' => 'Mendoza',
                'name' => 'Jose Mendoza',
                'email' => 'jose.mendoza@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Food Safety Division',
                'sections' => 'Microbiological Testing',
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Laboratory Technician II',
            ],
            [
                'first_name' => 'Grace',
                'last_name' => 'Abad',
                'name' => 'Grace Abad',
                'email' => 'grace.abad@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Administrative Division',
                'sections' => 'Human Resources',
                'status' => 'SUSPENDED',
                'role' => 'USER',
                'position' => 'Administrative Officer III',
            ],
        ];

        foreach ($fixed as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // ── Factory-generated dummy users ─────────────────────────────
        // Only create if we haven't already exceeded a target total,
        // so re-running the seeder is safe and idempotent.
        $targetTotal = 30;
        $current = User::count();

        if ($current < $targetTotal) {
            User::factory()
                ->count($targetTotal - $current)
                ->create();
        }

        $this->command->info('✅  UserSeeder complete — ' . User::count() . ' users in database.');
    }
}
