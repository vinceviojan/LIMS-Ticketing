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
            [
                'type' => 'Hardware',
                'categories' => 'Printer issue, Monitor fault, Scanner error, PC performance',
            ],
            [
                'type' => 'Software',
                'categories' => 'LIMS Portal access, Intralab upload issue, OS error, Office suite',
            ],
            [
                'type' => 'Network',
                'categories' => 'LAN connectivity, Internet slow, VPN access, Server timeout',
            ],
            [
                'type' => 'Account / Access',
                'categories' => 'Password reset, Account locked, Permission request',
            ],
        ];

        foreach ($categories as $cat) {
            ProblemCategory::firstOrCreate(
                ['type' => $cat['type']],
                $cat
            );
        }
    }
}
