<?php

require_once(APPPATH . 'models/baseentity.php');

class User extends BaseEntity {

    public static $_db_fields = array(
        "id" 	    => array("int", "none", false),
        "email"	    => array("string", "none", false),
        "name"	    => array("string", "none", false)
    );

    public $id;
    public $email;
    public $name;
}