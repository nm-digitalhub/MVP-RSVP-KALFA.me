<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['organization', 'individual']),
            'name' => $this->faker->company(),
            'owner_user_id' => null,
            'sumit_customer_id' => null,
            'credit_balance_agorot' => 0,
        ];
    }
}
