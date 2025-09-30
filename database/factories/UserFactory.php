<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $hashedPassword = app('hash')->make('Abcd123#');
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $hashedPassword,
            'role' => 'user', // Padrão é user
            'email_verified_at' => date('Y-m-d H:i:s'),
            'remember_token' => Str::random(10)
        ];
    }

    /**
     * Create admin user state
     */
    public function admin()
    {
        return $this->state([
            'role' => 'admin'
        ]);
    }
}
