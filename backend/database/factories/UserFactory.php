<?php

namespace Database\Factories;

use App\Models\User;
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

    // LIMS-specific lookup data
    private static array $divisions = [
        'Laboratory Division',
        'ICT Division',
        'Technical Support Division',
        'Quality Assurance Division',
        'Administrative Division',
        'Research & Development Division',
        'Environmental Monitoring Division',
        'Food Safety Division',
    ];

    private static array $sectionsByDivision = [
        'Laboratory Division' => ['Microbiology', 'Chemistry', 'Molecular Biology', 'Parasitology'],
        'ICT Division' => ['System Administration', 'Network Operations', 'Software Development', 'Data Management'],
        'Technical Support Division' => ['Helpdesk', 'Field Support', 'Equipment Maintenance'],
        'Quality Assurance Division' => ['Documentation', 'Audit', 'Compliance'],
        'Administrative Division' => ['Human Resources', 'Finance', 'Procurement', 'Records Management'],
        'Research & Development Division' => ['Applied Research', 'Method Development', 'Innovation'],
        'Environmental Monitoring Division' => ['Air Quality', 'Water Quality', 'Soil Analysis'],
        'Food Safety Division' => ['Microbiological Testing', 'Chemical Testing', 'Allergen Testing'],
    ];

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
        $division = fake()->randomElement(self::$divisions);
        $sections = fake()->randomElement(self::$sectionsByDivision[$division]);
        $position = fake()->randomElement(self::$positionsByRole[$role]);
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'division' => $division,
            'sections' => $sections,
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
