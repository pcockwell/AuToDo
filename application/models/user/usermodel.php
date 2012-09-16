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

    function get_user_by_email($email){

        debug(__FILE__, "get_user_by_email() is called for UserModel");

        $users = self::get( array( "email" => $email ) );
        if ( empty($users) ){
            return null;
        }
        return $users[0];
    }

    function get_or_create_user($email, $name){
        $existing_user = self::get_user_by_email($email);
        if ( !is_null($existing_user) ){
            return $existing_user;
        }

        $new_user = self::create();
        $new_user->email = $email;
        $new_user->name = $name;
        if ( $this->save($new_user) ){
            $new_user->id = $this->last_insert_id();
        }else{
            printr("ERROR SAVING USER");
        }

        return $new_user;

    }
}
