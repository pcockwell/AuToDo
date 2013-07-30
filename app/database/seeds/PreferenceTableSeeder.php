<?php

class PreferenceTableSeeder extends Seeder {

    public function run()
    {
        DB::table('preferences')->delete();

        $test_user_id = User::where('name', 'Test User')->first()->id;
        $pcockwell_id = User::where('name', 'Patrick Cockwell')->first()->id;
        $ochow_id = User::where('name', 'Oscar Chow')->first()->id;
        $smiclette_id = User::where('name', 'Steven Miclette')->first()->id;
        $tzhang_id = User::where('name', 'Tony Zhang')->first()->id;

        $preferences = array(
                $test_user_id => array(
                    'break' => 20,
                    'show_fixed_events' => false,
                    'schedule_until_latest' => false
                ),
                $pcockwell_id => array(
                    'schedule_until_latest' => true
                ),
                $ochow_id => array(
                    'break' => 20,
                    'show_fixed_events' => false
                ),
        );

        foreach ($preferences as $user_id => $pref)
        {
            User::find($user_id)->preferences()->save(new Preference($pref));
        }
    }
}