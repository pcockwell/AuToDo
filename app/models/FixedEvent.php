<?php

class FixedEvent extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'fixed_events';

	protected $fillable = array('user_id', 'name', 'start_time', 'end_time', 'start_date', 'end_date', 'recurrences');

	public function getDates(){
		return array('created_at', 'updated_at', 'start_date', 'end_date');
	}
}
