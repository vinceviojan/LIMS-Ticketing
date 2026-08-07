<?php

namespace Database\Seeders;

use App\Models\ProblemCategory;
use Illuminate\Database\Seeder;

class ProblemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Hardware' => [
                'Workstation/Laptop Troubleshooting',
                'Printer/Scanner Issues',
                'Lab Instrument Connectivity',
                'Peripheral Replacement',
            ],

            'Software' => [
                'Laboratory Information Management System (LIMS)',
                'Internal Document Tracking System (IDTS)',
                'Operating System Errors',
                'Microsoft Office Suite',
            ],

            'Network' => [
                'IntraLab Network',
                'Network Connectivity',
                'Router/Switch Issues',
            ],

            'Support' => [
                'Provision of Assistance for Technical Concerns',
                'Provision of Technical Support During Events',
                'Biometric Credentials Registration',
                'Provision of Photo/Video Documentation',
            ],

            'Account & Access Management' => [
                'Password Reset / Account Unlocking',
                'System Access Requests',
                'Shared Folder Permissions',
                'Email Account Creation',
            ],

            'Data Management' => [
                'Data Backup / Restoration',
                'File Transfer',
            ],

            'Communication & Collaboration Tools' => [
                'Email/Webmail Troubleshooting',
                'Video Conferencing Support',
            ],

            'Others' => [
                'ISO/IEC 17025:2017 Forms and Manuals',
                'LSD Conference Room',
                'Non-Technical Concern',
                'Other',
            ],
        ];

        foreach ($categories as $type => $items) {
            foreach ($items as $category) {
                ProblemCategory::firstOrCreate([
                    'type' => $type,
                    'categories' => $category,
                ]);
            }
        }
    }
}
