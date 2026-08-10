<?php

namespace Database\Seeders;

use App\Models\ProblemCategory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate 50 fake tickets
        Ticket::factory(50)->create();
    }
}
