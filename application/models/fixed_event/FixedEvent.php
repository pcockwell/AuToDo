<?php

require_once(APPPATH . 'models/baseentity.php');

class FixedEvent extends BaseEntity {

    public static $_db_fields = array(
        "id" 	    	=> array("int", "none", false),
        "user_id"		=> array("int", "none", false),
        "name" 	    	=> array("string", "none", false),
        "start_time"	=> array("int", "none", false),
        "end_time" 	    => array("int", "none", false),
        "start_date"	=> array("int", "none", false),
        "end_date"      => array("int", "none", false),
        "recurrences" 	=> array("string", "none", false)
    );

    public $id;
    public $user_id;
    public $name;
    public $start_time;
    public $end_time;
    public $start_date;
    public $end_date;
    public $recurrences;
}