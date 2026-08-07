<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'System Admin',
                'email' => 'admin@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'ICT Division',
                'sections' => 'System Administration',
                'status' => 'ACTIVE',
                'role' => 'ADMIN',
                'position' => 'IT Officer III',
            ],
            [
                'first_name' => 'Staff',
                'last_name' => 'Member',
                'name' => 'Support Staff',
                'email' => 'staff@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Technical Support',
                'sections' => 'Helpdesk',
                'status' => 'ACTIVE',
                'role' => 'STAFF',
                'position' => 'Computer Maintenance Technologist',
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'name' => 'John Doe',
                'email' => 'user@lims.gov.ph',
                'password' => Hash::make('password'),
                'division' => 'Laboratory Division',
                'sections' => 'Microbiology',
                'status' => 'ACTIVE',
                'role' => 'USER',
                'position' => 'Laboratory Analyst',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
