<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
  public function run(): void
  {
    if (!User::where('email', 'admin@example.com')->exists()) {
      User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
      ]);
    }
  }
}