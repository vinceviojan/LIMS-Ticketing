<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Division;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    private static array $positionsByRole = [
        'ADMIN' => [
            'IT Officer III',
            'IT Officer II',
            'Systems Administrator',
            'Chief Information Officer',
        ],
        'STAFF' => [
            'Computer Maintenance Technologist',
            'IT Support Specialist',
            'Laboratory Technician III',
            'Science Research Analyst',
            'Administrative Assistant III',
        ],
        'USER' => [
            'Laboratory Analyst',
            'Laboratory Technician I',
            'Laboratory Technician II',
            'Science Research Specialist',
            'Chemist III',
            'Microbiologist II',
            'Administrative Officer III',
            'Records Officer',
            'Procurement Specialist',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = fake()->randomElement(['USER', 'USER', 'USER', 'STAFF', 'ADMIN']); // weighted toward USER
        $position = fake()->randomElement(self::$positionsByRole[$role]);
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        $division = Division::inRandomOrder()->first();
        $section = $division ? Section::where('division_id', $division->id)->inRandomOrder()->first() : null;

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'division_id' => $division?->id,
            'section_id' => $section?->id,
            'status' => fake()->randomElement(['ACTIVE', 'ACTIVE', 'ACTIVE', 'INACTIVE', 'SUSPENDED']),
            'role' => $role,
            'position' => $position,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Convenience state — force ADMIN role */
    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'ADMIN',
            'position' => fake()->randomElement(self::$positionsByRole['ADMIN']),
            'status' => 'ACTIVE',
        ]);
    }

    /** Convenience state — force STAFF role */
    public function staff(): static
    {
        return $this->state(fn() => [
            'role' => 'STAFF',
            'position' => fake()->randomElement(self::$positionsByRole['STAFF']),
            'status' => 'ACTIVE',
        ]);
    }

    /** Convenience state — force USER role */
    public function user(): static
    {
        return $this->state(fn() => [
            'role' => 'USER',
            'position' => fake()->randomElement(self::$positionsByRole['USER']),
        ]);
    }
}
