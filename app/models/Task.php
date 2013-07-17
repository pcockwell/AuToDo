<?php

use Carbon\Carbon;

class Task extends Eloquent
{

    const TASK_MAX_PRIORITY = 3;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tasks';

	protected $fillable = array('user_id', 'name', 'priority', 'due', 'duration', 'complete');

	protected static $rules = array(
		'user_id' => array('required', 'integer', 'exists:users,id'),
		'name' => array('required', 'alpha_num', 'min:1'),
		'priority' => array('required', 'integer'),
		'due' => array('required', 'date'),
		'duration' => array('required', 'integer', 'min:1'),
		'complete' => array('integer', 'in:0,1'),
	);

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	//protected $hidden = array('password');

 	public function __construct($attributes = array(), $exists = false) 
 	{

        if (count($attributes) > 0)
        {
        self::$rules['priority'][] = 'between:0,'.self::TASK_MAX_PRIORITY;
        self::$rules['due'][] = 'after:'.Carbon::now()->toDateTimeString();

            $validator = Validator::make($attributes, self::$rules);
            if ($validator->fails()){
                throw new ValidationException($validator);
            }
        }
        parent::__construct($attributes, $exists);
    }

	public function getDates()
	{
		return array('created_at', 'updated_at', 'due');
	}

}
