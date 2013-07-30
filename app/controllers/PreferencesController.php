<?php

class PreferencesControllers extends \BaseController {

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function index($user_id)
	{
        $user = User::find($user_id);
        $preferences = $user->preferences;

        if (!isset($user) || $fixed_event == false)
        {
            return Response::make( 'No user with id '.$user_id, 400 );
        }
        else if (!$preferences)
        {
            return Response::make( 'User does not have any preferences set', 400 );
        }
        else   
        {
            return Response::make( $preferences, 200 );
        }
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($user_id)
	{
        try
        {
            $user = User::find($user_id);
            $preferences = new Preference(Input::all());
        }
        catch(ValidationException $v)
        {
            return Response::make( $v->get(), 500 );
        }

        if ($user->preferences()->save($preferences)) 
        {
            return Response::make( $preferences, 201 );
        } 
        else 
        {
            return Response::make( 'Failed to save preference set', 500 );
        }
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($user_id, $preference_id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($user_id, $preference_id)
	{
        $preferences = Preference::find($preference_id);

        if (!isset($preferences) || $preferences == false)
        {
            return Response::make( 'No preference set with id '.$preference_id, 400 );
        }
        else if ($preferences->user->id != $user_id)
        {
            return Response::make( 'Preference set does not belong to specified user', 400 );
        }
        else if ($preferences->delete())   
        {
        	return Response::make( 'Preference set deleted', 200 );
        }
        else
        {
        	return Response::make('Failed while attempting to delete preference set', 400 );
        }
	}

}