<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DocenteFactory extends Factory
{
    public function definition(): array
    {
        return ['nombres' => fake()->firstName(), 'apellidos' => fake()->lastName(), 'identificacion' => fake()->unique()->numerify('##########'), 'correo' => fake()->safeEmail(), 'telefono' => fake()->numerify('09########'), 'estado' => 'activo'];
    }
}
