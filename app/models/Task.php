<?php

class Task extends Eloquent
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tasks';

	protected $fillable = array('user_id', 'name', 'priority', 'due', 'duration', 'complete');

    const TASK_MAX_PRIORITY = 3;

	public function getDates()
	{
		return array('created_at', 'updated_at', 'due');
	}

}
