<?php

require_once(dirname(__FILE__) . '/FixedEvent.php');
require_once(APPPATH . 'models/basemodel.php');

class FixedEventModel extends BaseModel {

    protected $table_name = 'fixed_event';

    public function __construct(){
        parent::__construct();
    }

    public function create() {
        $obj = new FixedEvent();
        return $obj;
    }

    public function get_all_fixed_events(){
        debug(__FILE__, "get_all_fixed_events() is called for UserModel");

        return self::get();
    }

    public function get_all_fixed_events_by_user_id($user_id){
        debug(__FILE__, "get_all_fixed_events_by_user_id() is called for UserModel");

        $where = array('user_id' => $user_id);
        return self::get( $where );
    }

    public function get_all_fixed_events_by_user_date($user_id, $date){
        debug(__FILE__, "get_all_tasks_by_user_priority() is called for UserModel");

        $where = array('user_id' => $user_id, 'start_date <=' => $date, 'end_date >' => $date);
        $events = self::get( $where );

        if ( empty($events) ){
            return $events;
        }

        $valid_events = array();
        $day_of_week = intval( date('w', strtotime($date) ) );

        foreach ( $events as $event ){
            $event->recurrences = json_decode($event->recurrences);
            if ( in_array( $day_of_week, $event->recurrences ) ){
                $valid_events[] = $event;
            }
        }

        $sorted_event_list = self::sort_events($valid_events);

        return $sorted_event_list;
    }

    private function sort_events( $event_list ){
        usort($event_list, "self::event_cmp");
        for ( $i = 1; $i < count($event_list); $i++ ){
            if ( $event_list[$i - 1]->end_time > $event_list[$i]->start_time ){
                return false;
            }
        }

        return $event_list;
    }

    private function event_cmp( FixedEvent $a, FixedEvent $b ){

        if ( $a->start_time == $b->start_time ){
            return 0;
        }

        return ($a->start_time < $b->start_time) ? -1 : 1;
    }
}
