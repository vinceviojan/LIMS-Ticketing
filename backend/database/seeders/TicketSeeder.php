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
        $user = User::where('role', 'USER')->first() ?? User::first();
        $category = ProblemCategory::first();

        if ($user && $category) {
            Ticket::create([
                'user_id' => $user->id,
                'ticket_no' => 'TICK-20260804-001',
                'issue' => 'Cannot upload test results to Intralab',
                'problem_category_id' => $category->id,
                'date_submitted' => now(),
                'status' => 'OPEN',
                'urgency' => 'HIGH',
                'upload_intralab' => 'uploads/intralab_sample_log.pdf',
                'upload_limsportal' => null,
                'description' => 'Encountered timeout error while attempting to push daily sample verification records.',
            ]);
        }
    }
}
