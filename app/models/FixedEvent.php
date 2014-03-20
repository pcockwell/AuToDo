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
    protected $softDelete = true;

	protected $fillable = array('name', 'start_time', 'end_time', 
        'start_date', 'end_date', 'recurrences',
        'break_before', 'break_after');

    protected static $rules = array(
        'name' => array('required', 'alpha_num_space', 'min:1'),
        'start_time' => array('required', 'integer'),
        'end_time' => array('required', 'integer'),
        'start_date' => array('required', 'date'),
        'end_date' => array('required', 'date'),
        'recurrences' => array('validate_recurrence'),
		'break_before' => array('integer', 'min:0'),
		'break_after' => array('integer', 'min:0'),
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

    public static function valid($attributes = array(), $checkRequired = false)
    {
        $newRules = self::$rules;

        if (!$checkRequired)
        {
            foreach ($newRules as $rule)
            {
                $rule = array_diff($rule, array('required'));
            }
        }

        $validator = Validator::make($attributes, $newRules);
        return $validator->fails() == false;
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
