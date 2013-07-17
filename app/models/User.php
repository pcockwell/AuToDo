<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	protected $fillable = array('name', 'email');

	protected static $rules = array(
		'name' => array('required', 'alpha', 'min:5'),
		'email' => array('required', 'email')
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
    }

	/**
	 * Get the unique identifier for the user.
	 *
	 * @return mixed
	 */
	public function getAuthIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->password;
	}

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return $this->email;
	}

	public static function getTestUser()
	{
		return User::find(1);	
	}

}
