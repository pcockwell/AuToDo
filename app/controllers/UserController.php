<?php

class UserController extends BaseController
{

    /**
     *  Post arguments: name, email
     *  Create a user with name 'name', and email 'email'
     */
    public function postCreate()
    {
        if (Request::is('user/create*'))
        {
            if (!Input::has('name')) {
                // Return missing name parameter
                return Response::make( 'Missing name parameter', 400 );
            }
            if (!Input::has('email')) {
                // Return missing email parameter
                return Response::make( 'Missing email parameter', 400 );
            }
            $user_name = Input::get('name');
            $user_email = Input::get('email');

            $user = User::create(array('name' => $user_name,
                                       'email' => $user_email));

            if (isset($user) && $user != false) {
                return Response::make( '', 201 );
            } else {
                return Response::make( '', 500 );
            }
        }
    }


    /**
     *  Post arguments: name
     *  Delete the user with name 'name' if they exist.
     */
    public function postDelete()
    {
        if (Request::is('user/delete*'))
        {
            if (!Input::has('name'))
            {
                // Return missing name parameter
                return Response::make( 'Missing name parameter', 400 );
            }
            $user_name = Input::get('name');

            $user_model = User::where('name', '=', $user_name)->first();

            if (!isset($user_model) || $user_model == false)
            {
                return Response::make( 'No user with name '.$user_name, 400 );
            }
            $user_model->delete();
            return Response::make( 'User deleted', 200 );
        }
    }



}
