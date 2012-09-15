<?php

require_once(dirname(__FILE__) . '/FixedEvent.php');
require_once(APPPATH . 'models/basemodel.php');

class FixedEventModel extends BaseModel {

    protected $table_name = 'fixed_event';

    public function __construct(){
        parent::__construct();
    }

    function create() {
        $obj = new FixedEvent();
        return $obj;
    }

    function get_all_fixed_events(){
        debug(__FILE__, "get_all_fixed_events() is called for UserModel");

        return self::get();
    }

    function get_all_fixed_events_by_user_id($user_id){
        debug(__FILE__, "get_all_fixed_events_by_user_id() is called for UserModel");

        $where = array('user_id' => $user_id);
        return self::get( $where );
    }

    function get_all_tasks_by_user_date($user_id, $date){
        debug(__FILE__, "get_all_tasks_by_user_priority() is called for UserModel");

        $where = array('user_id' => $user_id, 'start_date <=' => $date, 'end_date >' => $date);
        $events = self::get( $where );

        $valid_events = array();
        $day_of_week = intval( date('w', $date) );

        foreach ( $events as $event ){
            $event->recurrences = json_decode($event->recurrences);
            if ( in_array( $day_of_week, $event->recurrences ) ){
                $valid_events[] = $event;
            }
        }

        return $valid_events;
    }
}
