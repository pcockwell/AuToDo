<?php

use Autodo\Exception\ValidationException;

class TaskController extends \BaseController {

    public function __construct()
    {
        $this->beforeFilter('auth.basic.once');
        $this->beforeFilter('authedRequest');
    }

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
            return Response::make( $user->tasks()->get(), 200 );
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
            $task = new Task(Input::all());
        }
        catch(ValidationException $v)
        {
            return Response::make( $v->get(), 500 );
        }

        if ($user->tasks()->save($task)) 
        {
            return Response::make( $task, 201 );
        } 
        else 
        {
            return Response::make( 'Failed to save task', 500 );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($user_id, $task_id)
    {
        $task = Task::find($task_id);

        if (!isset($task) || $task == false)
        {
            return Response::make( 'No task with id '.$task_id, 400 );
        }
        else if ($task->user->id != $user_id)
        {
            return Response::make( 'Task does not belong to specified user', 400 );
        }
        else   
        {
            return Response::make( $task, 200 );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($user_id, $task_id)
    {
        $task = Task::find($task_id);

        if (!isset($task) || $task == false)
        {
            return Response::make( 'No task with id '.$task_id, 400 );
        }
        else if ($task->user->id != $user_id)
        {
            return Response::make( 'Task does not belong to specified user', 400 );
        }

        $newTaskInfo = Input::all();

        if (!Task::valid($newTaskInfo))
        {
            return Response::make( 'Task details supplied are not valid.', 400 );
        }

        $task->update($newTaskInfo);
            
        return Response::make( $task, 201 );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($user_id, $task_id)
    {
        $task = Task::find($task_id);

        if (!isset($task) || $task == false)
        {
            return Response::make( 'No task with id '.$task_id, 400 );
        }
        else if ($task->user->id != $user_id)
        {
            return Response::make( 'Task does not belong to specified user', 400 );
        }
        else if ($task->delete())   
        {
            return Response::make( 'Task deleted', 200 );
        }
        else
        {
            return Response::make('Failed while attempting to delete task', 400 );
        }
    }

}