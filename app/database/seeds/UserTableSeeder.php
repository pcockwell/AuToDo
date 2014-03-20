<?php

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->truncate();
        
        $users = array(
                array(
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => 'tu123'
                ),
                array(
                    'name' => 'Patrick Cockwell',
                    'email' => 'pcockwell@gmail.com',
                    'password' => 'pc123'
                ),
                array(
                    'name' => 'Oscar Chow',
                    'email' => 'oscarchow51510@gmail.com',
                    'password' => 'oc123'
                ),
                array(
                    'name' => 'Steven Miclette',
                    'email' => 'steven.miclette@gmail.com',
                    'password' => 'sm123'
                ),
                array(
                    'name' => 'Tony Zhang',
                    'email' => 'y346zhang@gmail.com',
                    'password' => 'tz123'
                ),
        );

        foreach ($users as $user)
        {
            $new_user = new User($user);
            $new_user->password = Hash::make($user['password']);
            $new_user->save();
        }
    }

}

