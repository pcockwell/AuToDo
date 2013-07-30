<?php

use Carbon\Carbon;

class TaskTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tasks')->truncate();
        
        $test_user_id = User::where('name', 'Test User')->first()->id;
        $pcockwell_id = User::where('name', 'Patrick Cockwell')->first()->id;
        $ochow_id = User::where('name', 'Oscar Chow')->first()->id;
        $smiclette_id = User::where('name', 'Steven Miclette')->first()->id;
        $tzhang_id = User::where('name', 'Tony Zhang')->first()->id;

        $now = Carbon::now();
        $due_soon = $now->copy()->addHours(12);
        $due_medium = $now->copy()->addDays(2);
        $due_far_away = $now->copy()->addWeek();

        $lowest_priority = 0;
        $low_priority = 1;
        $medium_priority = 2;
        $high_priority = 3;

        $short_duration = 20;
        $medium_duration = 60;
        $long_duration = 120;

        $tasks = array(
            $test_user_id =>  array(
                array(
                    'name' => 'test user task 1',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $short_duration,
                    'priority' => $low_priority
                ),
                array(
                    'name' => 'test user task 2',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'test user task 3',
                    'due' => $due_far_away->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'test user task 4',
                    'due' => $due_medium->toDateTimeString(),
                    'duration' => $medium_duration,
                    'priority' => $medium_priority
                ),
            ),
            $pcockwell_id =>  array(
                array(
                    'name' => 'pcockwell task 1',
                    'due' => $due_medium->toDateTimeString(),
                    'duration' => $short_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'pcockwell task 2',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $low_priority
                ),
            ),
            $ochow_id =>  array(
                array(
                    'name' => 'ochow task 1',
                    'due' => $due_medium->toDateTimeString(),
                    'duration' => $short_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'ochow task 2',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $low_priority
                ),
            ),
            $smiclette_id =>  array(
                array(
                    'name' => 'smiclette task 1',
                    'due' => $due_medium->toDateTimeString(),
                    'duration' => $short_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'smiclette task 2',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $low_priority
                ),
            ),
            $tzhang_id => array(
                array(
                    'name' => 'tzhang task 1',
                    'due' => $due_medium->toDateTimeString(),
                    'duration' => $short_duration,
                    'priority' => $high_priority
                ),
                array(
                    'name' => 'tzhang task 2',
                    'due' => $due_soon->toDateTimeString(),
                    'duration' => $long_duration,
                    'priority' => $low_priority
                ),
            )
        );

        foreach ($tasks as $user_id => $task_list)
        {
            $user = User::find($user_id);
            foreach ($task_list as $task)
            {
                $user->tasks()->save(new Task($task));
            }
        }
    }
}
