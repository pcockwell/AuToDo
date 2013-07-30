<?php

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->truncate();
        
        $users = array(
                array(
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ),
                array(
                    'name' => 'Patrick Cockwell',
                    'email' => 'pcockwell@gmail.com'
                ),
                array(
                    'name' => 'Oscar Chow',
                    'email' => 'oscarchow51510@gmail.com'
                ),
                array(
                    'name' => 'Steven Miclette',
                    'email' => 'steven.miclette@gmail.com'
                ),
                array(
                    'name' => 'Tony Zhang',
                    'email' => 'y346zhang@gmail.com'
                ),
        );

        foreach ($users as $user)
        {
            User::create($user);
        }
    }

}

