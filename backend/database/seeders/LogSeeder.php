<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $ticket = Ticket::first();

        if ($user && $ticket) {
            Log::create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'action' => 'Ticket Created',
                'address' => '127.0.0.1',
            ]);
        }
    }
}
