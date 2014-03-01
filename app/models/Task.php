<?php

use Carbon\Carbon;
use Autodo\Exception\ValidationException;

class Task extends Eloquent
{

    const TASK_MAX_PRIORITY = 3;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tasks';

	protected $fillable = array('name', 'priority', 'due', 'duration', 'complete');

	protected static $rules = array(
		'name' => array('required', 'alpha_num_space', 'min:1'),
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

            $validator = Validator::make($attributes, self::$rules);
            if ($validator->fails()){
                throw new ValidationException($validator);
            }
        }
        parent::__construct($attributes, $exists);
    }

    public function user()
    {
        $this->belongsTo('User');
    }

	public function getDates()
	{
		return array('created_at', 'updated_at', 'due');
	}

}
