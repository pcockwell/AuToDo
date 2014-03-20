<?php

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->truncate();
        
        $users = array(
                array(
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => 'tu12345'
                ),
                array(
                    'name' => 'Patrick Cockwell',
                    'email' => 'pcockwell@gmail.com',
                    'password' => 'pc12345'
                ),
                array(
                    'name' => 'Oscar Chow',
                    'email' => 'oscarchow51510@gmail.com',
                    'password' => 'oc12345'
                ),
                array(
                    'name' => 'Steven Miclette',
                    'email' => 'steven.miclette@gmail.com',
                    'password' => 'sm12345'
                ),
                array(
                    'name' => 'Tony Zhang',
                    'email' => 'y346zhang@gmail.com',
                    'password' => 'tz12345'
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

