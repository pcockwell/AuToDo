<?php

use Carbon\Carbon;
use Autodo\Exception\ValidationException;

class FixedEvent extends Eloquent 
{

    const MINUTES_IN_DAY = 1440; //24 * 60

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'fixed_events';

	protected $fillable = array('name', 'start_time', 'end_time', 'start_date', 'end_date', 'recurrences');

    protected static $rules = array(
        'name' => array('required', 'alpha_num_space', 'min:1'),
        'start_time' => array('required', 'integer'),
        'end_time' => array('required', 'integer'),
        'start_date' => array('required', 'date'),
        'end_date' => array('required', 'date'),
        'recurrences' => array('required', 'validate_recurrence'),
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
            self::$rules['start_time'][] = 'between:0,'.self::MINUTES_IN_DAY;
            self::$rules['end_time'][] = 'between:0,'.self::MINUTES_IN_DAY;

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
		return array('created_at', 'updated_at', 'start_date', 'end_date');
	}

    public function getRecurrences()
    {
        return json_decode( $this->recurrences );
    }
}
