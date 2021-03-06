<?php

use Carbon\Carbon;
use Autodo\Exception\ValidationException;

class Preference extends Eloquent 
{

    const DEFAULT_BREAK = 15;
    const DEFAULT_SHOW_FIXED_EVENTS = true;
    const DEFAULT_SCHEDULE_UNTIL_LATEST = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'preferences';

    protected $fillable = array('show_fixed_events', 'schedule_until_latest');

    protected static $rules = array(
        'show_fixed_events' => array('boolean'),
        'schedule_until_latest' => array('boolean')
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
            $validator = Validator::make($attributes, self::$rules);
            if ($validator->fails()){
                throw new ValidationException($validator);
            }
        }
        parent::__construct($attributes, $exists);
        
        if (!array_key_exists('show_fixed_events', $attributes))
        {
            $this->show_fixed_events = self::DEFAULT_SHOW_FIXED_EVENTS;
        }

        if (!array_key_exists('schedule_until_latest', $attributes))
        {
            $this->schedule_until_latest = self::DEFAULT_SCHEDULE_UNTIL_LATEST;
        }
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
}
