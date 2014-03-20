<?php

class RemindersController extends BaseController
{
    public function request()
    {
        $credentials = array('email' => Input::get('email'));
        return Password::remind($credentials);
    }

    public function reset($token)
    {
        return View::make('password.reset')->with('token', $token);
    }

    public function update()
    {
        $credentials = array('email' => Input::get('email'));

        return Password::reset($credentials, function($user, $password)
        {
            $user->password = Hash::make($password);
            $user->save();
            return Response::make("Password changed successfully!", 200);
        });
    }
}
