<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TasksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        // Gerar 50 usuários fictícios
        foreach ($users as $user) {

            if ($user->id > 30) {

                Task::factory()->count(1500)->create(['user_id' => $user->id]);
            } else {

                Task::factory()->count($user->id * 30)->create(['user_id' => $user->id]);
            }
            
        }
    }
}
