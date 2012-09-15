<?php

require_once(APPPATH . 'models/baseentity.php');

class Task extends BaseEntity {

    public static $_db_fields = array(
        "id" 	    => array("int", "none", false),
        "user_id"	=> array("int", "none", false),
        "name" 	    => array("string", "none", false),
        "priority"	=> array("int", "none", false),
        "due" 	    => array("int", "none", false),
        "duration"	=> array("int", "none", false),
        "complete" 	=> array("int", "none", false)
    );

    public $id;
    public $user_id;
    public $name;
    public $priority;
    public $due;
    public $duration;
    public $complete;
}