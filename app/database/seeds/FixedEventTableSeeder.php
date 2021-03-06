<?php

use Carbon\Carbon;

class FixedEventTableSeeder extends Seeder {

    public function run()
    {
        DB::table('fixed_events')->truncate();
        
        $test_user_id = User::where('name', 'Test User')->first()->id;
        $pcockwell_id = User::where('name', 'Patrick Cockwell')->first()->id;
        $ochow_id = User::where('name', 'Oscar Chow')->first()->id;
        $smiclette_id = User::where('name', 'Steven Miclette')->first()->id;
        $tzhang_id = User::where('name', 'Tony Zhang')->first()->id;

        $now = Carbon::now();
        $next_year = $now->copy()->addYear();

        $monday_recurrence = "[1]";
        $friday_recurrence = "[5]";
        $daily_recurrence = "[0,1,2,3,4,5,6]";
        $weekday_recurrence = "[1,2,3,4,5]";
        $weekend_recurrence = "[0,6]";

        $fixed_events = array(
            $test_user_id => array(
                array(
                    'name' => 'test user fixed 1',
                    'start_time' => 0,
                    'end_time' => 420,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $daily_recurrence
                ),
                array(
                    'name' => 'test user fixed 2',
                    'start_time' => 690,
                    'end_time' => 810,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekday_recurrence
                ),
                array(
                    'name' => 'test user fixed 3',
                    'start_time' => 900,
                    'end_time' => 1020,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekend_recurrence
                ),
                array(
                    'name' => 'test user fixed 4',
                    'start_time' => 1380,
                    'end_time' => 1440,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $friday_recurrence
                ),
                array(
                    'name' => 'test user fixed 5',
                    'start_time' => 1080,
                    'end_time' => 1230,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $daily_recurrence
                ),
                array(
                    'name' => 'test user fixed 6',
                    'start_time' => 1080,
                    'end_time' => 1230,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $monday_recurrence
                ),
            ),
            $pcockwell_id => array(
                array(
                    'name' => 'pcockwell fixed 1',
                    'start_time' => 1380,
                    'end_time' => 1440,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $friday_recurrence
                ),
                array(
                    'name' => 'pcockwell fixed 2',
                    'start_time' => 690,
                    'end_time' => 810,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekday_recurrence
                ),
            ),
            $ochow_id => array(
                array(
                    'name' => 'ochow fixed 1',
                    'start_time' => 0,
                    'end_time' => 420,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $daily_recurrence
                ),
                array(
                    'name' => 'ochow fixed 2',
                    'start_time' => 690,
                    'end_time' => 810,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekday_recurrence
                ),
            ),
            $smiclette_id => array(
                array(
                    'name' => 'smiclette fixed 1',
                    'start_time' => 1380,
                    'end_time' => 1440,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $friday_recurrence
                ),
                array(
                    'name' => 'smiclette fixed 2',
                    'start_time' => 900,
                    'end_time' => 1020,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekend_recurrence
                ),
            ),
            $tzhang_id => array(
                array(
                    'name' => 'tzhang fixed 1',
                    'start_time' => 900,
                    'end_time' => 1020,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $weekend_recurrence
                ),
                array(
                    'name' => 'tzhang fixed 2',
                    'start_time' => 0,
                    'end_time' => 420,
                    'start_date' => $now->toDateTimeString(),
                    'end_date' => $next_year->toDateTimeString(),
                    'recurrences' => $daily_recurrence
                ),
            ),
        );

        foreach ($fixed_events as $user_id => $fixed_event_list)
        {
            $user = User::find($user_id);
            foreach ($fixed_event_list as $event)
            {
                $user->fixedevents()->save(new FixedEvent($event));
            }
        }
    }
}
