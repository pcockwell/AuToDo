<?php

require_once(dirname(__FILE__) . '/User.php');
require_once(APPPATH . 'models/basemodel.php');

class UserModel extends BaseModel {

    protected $table_name = 'user';

    public function __construct(){
        parent::__construct();
    }

    function create() {
        $obj = new User();
        return $obj;
    }

    function get_all_users(){
        debug(__FILE__, "get_all_users() is called for UserModel");

        return self::get();
    }
}
