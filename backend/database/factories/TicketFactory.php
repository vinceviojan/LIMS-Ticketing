<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ProblemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'ticket_no' => 'TICK-' . now()->format('Ymd') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue' => $this->faker->sentence(),
            'problem_category_id' => ProblemCategory::inRandomOrder()->first()->id ?? ProblemCategory::factory(),
            'date_submitted' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['OPEN', 'ESCALATED', 'CANCEL', 'CLOSE']),
            'urgency' => $this->faker->randomElement(['LOW', 'NORMAL', 'HIGH']),
            'upload_intralab' => null,
            'upload_limsportal' => null,
            'description' => $this->faker->paragraph(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
