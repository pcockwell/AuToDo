<?php

require_once(dirname(__FILE__) . '/Task.php');
require_once(APPPATH . 'models/basemodel.php');

class TaskModel extends BaseModel {

    protected $table_name = 'task';

    public function __construct(){
        parent::__construct();
    }

    function create() {
        $obj = new Task();
        return $obj;
    }

    function get_all_tasks(){
        debug(__FILE__, "get_all_tasks() is called for UserModel");

        return self::get();
    }

    function get_all_tasks_by_user_id($user_id, $complete = null){
        debug(__FILE__, "get_all_tasks_by_user_id() is called for UserModel");

        $where = array('user_id' => $user_id);
        if ( $complete != null ){
            $where['complete'] = $complete;
        }
        return self::sort_tasks(self::get( $where ));
    }

    function get_all_tasks_by_user_priority($user_id, $priority, $complete = null){
        debug(__FILE__, "get_all_tasks_by_user_priority() is called for UserModel");

        $where = array('user_id' => $user_id, 'priority' => $priority);
        if ( $complete != null ){
            $where['complete'] = $complete;
        }
        return self::sort_tasks(self::get( $where ));
    }

    private function sort_tasks( $task_list ){
        usort($task_list, "self::task_cmp");

        return $task_list;
    }

    private function task_cmp( Task $a, Task $b ){

        if ( strtotime($a->due) == strtotime($b->due) ){
            if ( $a->priority == $b->priority ){
                return 0;
            }

            return ($a->priority > $b->priority) ? -1 : 1;
        }

        return (strtotime($a->due) < strtotime($b->due)) ? -1 : 1;
    }
}
