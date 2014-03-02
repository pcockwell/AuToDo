<?php

use Autodo\Exception\ValidationException;

class UserController extends \BaseController {

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        try
        {
            $user = User::create(Input::all());
        }
        catch(ValidationException $v)
        {
            return Response::make( $v->get(), 500 );
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

        $changeMade = false;
        foreach (Input::all() as $key => $value)
        {
            if (property_exists($user, $key) && $user->{$key} != $value)
            {
                $user->{$key} = $value;
                $changeMade = true;
            }
        }

        if ($changeMade)
        {
            $user->save();
        }
            
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
        $user_model->user();
        return Response::make( 'User deleted', 200 );
    }
}