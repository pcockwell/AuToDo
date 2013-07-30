<?php

use Autodo\Exception\ValidationException;

class FixedEventController extends \BaseController {

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index($user_id)
    {
        $user = User::find($user_id);

        if (!isset($user) || $user == false)
        {
            return Response::make( 'No user with id '.$id, 400 );
        }
        else   
        {
            return Response::make( $user->fixedevents()->get(), 200 );
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
            $fixed_event = new FixedEvent(Input::all());
        }
        catch(ValidationException $v)
        {
            return Response::make( $v->get(), 500 );
        }

        if ($user->fixedevents()->save($fixed_event)) 
        {
            return Response::make( $fixed_event, 201 );
        } 
        else 
        {
            return Response::make( 'Failed to save fixed event', 500 );
        }
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($user_id, $fixedevent_id)
	{
        $fixed_event = FixedEvent::find($fixedevent_id);

        if (!isset($fixed_event) || $fixed_event == false)
        {
            return Response::make( 'No fixed event with id '.$fixedevent_id, 400 );
        }
        else if ($fixed_event->user->id != $user_id)
        {
            return Response::make( 'Fixed event does not belong to specified user', 400 );
        }
        else   
        {
            return Response::make( $fixed_event, 200 );
        }
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($user_id, $fixedevent_id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($user_id, $fixedevent_id)
	{
        $fixed_event = FixedEvent::find($fixedevent_id);

        if (!isset($fixed_event) || $fixed_event == false)
        {
            return Response::make( 'No fixed event with id '.$fixedevent_id, 400 );
        }
        else if ($fixed_event->user->id != $user_id)
        {
            return Response::make( 'Fixed event does not belong to specified user', 400 );
        }
        else if ($fixed_event->delete())   
        {
        	return Response::make( 'Fixed event deleted', 200 );
        }
        else
        {
        	return Response::make('Failed while attempting to delete fixed event', 400 );
        }
	}

}