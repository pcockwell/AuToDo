<?php

use Autodo\Exception\ValidationException;

class UserController extends \BaseController {

    /*public function __construct()
    {
        $this->beforeFilter('auth.basic.once', 
            array('except' => 'store'));
        $this->beforeFilter(function($route)
        {
            print_r($route->getParameters());
            $user_id = $route->getParameter('user');
            if (!Auth::check() || Auth::user()->id != $user_id)
            {
                return Response::make( 'Cannot access data for user with user id ' . $user_id, 404 );
            }
        });
    }*/

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        try
        {
            if (Input::has('password'))
            {
                $password = Hash::make(Input::get('password'));
                $user = new User(Input::all());
                $user->password = $password;
                $user->save();
            }
            else
            {
                return Response::make( 'No password provided', 400 );
            }
        }
        catch(ValidationException $v)
        {
            return Response::make( $v->get(), 400 );
        }

        if (isset($user) && $user != false) 
        {
            return Response::make( $user, 201 );
        } 
        else 
        {
            return Response::make( 'Failed to save user', 500 );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$id, 400 );
        }
        else   
        {
            return Response::make( $user, 200 );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        $user = User::find($id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$id, 400 );
        }

        $newUserInfo = Input::all();

        if (!User::valid($newUserInfo))
        {
            return Response::make( 'Input supplied is valid.', 400 );
        }

        $user->update($newUserInfo);
            
        return Response::make( $user, 201 );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$id, 400 );
        }
        $user->tasks()->delete();
        $user->fixedevents()->delete();
        $user->preferences()->delete();
        $user->delete();
        return Response::make( 'User deleted', 200 );
    }

    /**
     * TODO: If an email is passed in, find that user.
     *
     */
    public function findByEmail()
    {
        $email = Auth::user()->email;
        $user = User::where('email', '=', $email)->firstOrFail();
        return Response::make( $user, 200 );
    }
}
