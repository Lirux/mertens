<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use MongoDB\BSON\UTCDateTime;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastMaintenance = fake()->dateTimeBetween('-18 months', '-1 month');
        $nextMaintenance = (clone $lastMaintenance)->modify('+12 months');

        return [
            'asset_number' => 'AST-'.fake()->unique()->numerify('#####'),
            'name' => fake()->randomElement([
                'CNC Fräsmaschine',
                'Kompressor',
                'Gabelstapler',
                'Schweissanlage',
                'Messgerät',
            ]),
            'category' => fake()->randomElement(['production', 'logistics', 'measurement']),
            'status' => fake()->randomElement(Asset::STATUSES),
            'serial_number' => 'SN-'.fake()->unique()->bothify('????-#####'),
            'location' => [
                'site' => 'Hauptsitz',
                'building' => fake()->randomElement(['A', 'B', 'C']),
                'room' => fake()->numerify('###'),
            ],
            'supplier' => [
                'external_id' => 'SUP-'.fake()->numerify('####'),
                'name' => fake()->company(),
            ],
            'maintenance' => [
                'last_completed_at' => new UTCDateTime($lastMaintenance),
                'next_due_at' => new UTCDateTime($nextMaintenance),
                'interval_days' => 365,
            ],
            'acquired_at' => fake()->dateTimeBetween('-10 years', '-1 year'),
            'warranty_until' => fake()->dateTimeBetween('+1 month', '+3 years'),
        ];
    }
}
