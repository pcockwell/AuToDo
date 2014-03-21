<?php

use Autodo\Exception\ValidationException;

class PreferencesController extends \BaseController {

    public function __construct()
    {
        $this->beforeFilter('auth.basic.once');
        $this->beforeFilter(function($route = null, $request = null, $value = null)
            {
                $user_id = $route->getParameter('preferences');
                if (!Auth::check() || Auth::user()->id != $user_id)
                {
                    return Response::make( 'Cannot access data for user with user id ' . $user_id, 404 );
                }
            }
        );
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($user_id)
    {
        $user = User::find($user_id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$user_id, 400 );
        }
        
        $preferences = $user->preferences;
        if (!isset($preferences) || $preferences == false)
        {
            return Response::make( 'User does not have any preferences set', 400 );
        }
        else   
        {
            return Response::make( $preferences, 200 );
        }
    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($user_id)
	{
        $user = User::find($user_id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$user_id, 400 );
        }
        
        $preferences = $user->preferences;
        if (!isset($preferences) || $preferences == false)
        {
            return Response::make( 'User does not have any preferences set', 400 );
        }

        $newPrefs = Input::all();

        if (!Preference::valid($newPrefs))
        {
            return Response::make( 'Preferences supplied are not valid.', 400 );
        }

        $preferences->update($newPrefs);
            
        return Response::make( $preferences, 201 );
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($user_id)
	{
        $user = User::find($user_id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$user_id, 400 );
        }
        
        $preferences = $user->preferences;

        if (!isset($preferences) || $preferences == false)
        {
            return Response::make( 'No preference set with for user '.$user_id, 400 );
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