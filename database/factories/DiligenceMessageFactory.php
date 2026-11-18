<?php

namespace Database\Factories;

use App\Enums\DiligenceDirection;
use App\Models\DiligenceMessage;
use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiligenceMessageFactory extends Factory
{
    protected $model = DiligenceMessage::class;

    public function definition(): array
    {
        return [
            'diligenceable_type' => 'monitoring',
            'diligenceable_id' => Monitoring::factory(),
            'direction' => DiligenceDirection::OUTBOUND,
            'from_email' => $this->faker->safeEmail(),
            'to_email' => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'imap_message_id' => sprintf('<%s@efomento>', $this->faker->unique()->uuid()),
            'in_reply_to' => null,
            'sent_at' => now(),
            'read_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function inbound(): static
    {
        return $this->state([
            'direction' => DiligenceDirection::INBOUND,
            'created_by' => null,
        ]);
    }
}
