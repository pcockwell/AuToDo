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
    public function show($user_id)
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
        //
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