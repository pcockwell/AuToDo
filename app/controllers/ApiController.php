<?php

use Carbon\Carbon;

class ApiController extends BaseController {

    private $conflicts;
    private $schedule;
    private $time_slots;
    private $task_max_priority = 3;

	public function getIndex()
	{
		return View::make('hello');
	}

    public function missingMethod($parameters){
        $user = User::getTestUser();
        return "Hello $user->name";
    }

	public function getHello(){
		return "Hello " . User::getTestUser()->name;
	}

	public function get_name($name){
		return "Hello $name";
	}

    public function get_phpinfo(){
        phpinfo();
    }

    // Make max priority accessible.
    public function getMaxPriority() {
        return $this->task_max_priority;
    }

    public function post_schedule() {
        $sch = null;
        if (Request::is('api/schedule*')) {
            if (Input::isJson()) {
                // valid json request
                $data = Input::all();
                // error checking omitted
                $tasks_obj_arr = array();
                $fixed_events_obj_arr = array();
                $prefs = null;
                if (isset($data['tasks'])) {
                    $tasks = $data['tasks'];
                    $tasks_obj = json_decode(json_encode($tasks), false);
                    foreach ($tasks_obj as $obj) {
                        $task = Task::create($obj);
                        $tasks_obj_arr[$obj->name] = $task;
                    }
                }
                if (isset($data['fixed'])) {
                    $fixed_events = $data['fixed'];
                    $fixed_events_obj = json_decode(json_encode($fixed_events), false);
                    foreach ($fixed_events_obj as $obj) {
                        $fixed = FixedEvent::create($obj);
                        $fixed_events_obj_arr[$obj->name] = $fixed;
                    }
                }

                if (isset($data['prefs'])) {
                    $prefs = $data['prefs'];
                }
                $sch = $this->createSchedule($tasks_obj_arr, $fixed_events_obj_arr, $prefs);
            } else {
                // prepare a response, unsupported POST content
                $invalid_text = 'The request could not be fulfilled.\n
                    An unsupported content type was used.';
                $response = Response::make( $invalid_text, 400 );
                return $response;
            }
        }
        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    // This is just a test function with hardcoded JSON data.
    // Expected end result would be to call the createSchedule( $tasks, $prefs ) method,
    // which would populate the private members $schedule and $conflicts
    //
//     Expected structure for $tasks: array of stdClass objects
//         members: name - string ID of task
//                  duration - task duration
//                  priority - task priority, in range[ 0, task_max_priority]
//                  due - task due time
//                * start - task required start time
//                * end - task required end time
//         Members marked with * are optional and only need to be present if the task is an
//         event with a fixed time frame. Any task must have both "start" and "end" defined
//         or neither defined. If a dsicrete time frame is defined, then priority MUST be
//         set to task_max_priority+1. This ensures that fixed events are put in the
//         schedule first, and all other tasks scheduled around it.
//     
//     Expected structure for $prefs: array
//         Currently only supporting:
//             start - after which time all tasks should be scheduled
//             break - minimum break time between tasks

    public function get_testschedule() {
        $data = json_decode( '
            {
                "tasks" : [
                    {
                        "user_id" : 1,
                        "name" : "name1",
                        "due" : "2013-12-04 12:00:00",
                        "duration" : 40,
                        "priority" : 1
                    },
                    {
                        "user_id" : 1,
                        "name" : "name2",
                        "due" : "2013-12-04 12:00:00",
                        "duration" : 60,
                        "priority" : 0
                    },
                    {
                        "user_id" : 1,
                        "name" : "name3",
                        "due" : "2013-12-04 12:00:00",
                        "duration" : 30,
                        "priority" : 3
                    },
                    {
                        "user_id" : 1,
                        "name" : "name4",
                        "due" : "2013-11-04 12:00:00",
                        "duration" : 30,
                        "priority" : 1
                    }
                ],
                "fixed" : [
                    {
                        "user_id" : 1,
                        "name" : "Sleep",
                        "start_time" : 0,
                        "end_time" : 420,
                        "start_date" : "2012-09-01 00:00:00",
                        "end_date" : "2013-09-01 00:00:00",
                        "recurrences" : "[0,1,2,3,4,5,6]"
                    },
                    {
                        "user_id" : 1,
                        "name" : "Class",
                        "start_time" : 690,
                        "end_time" : 810,
                        "start_date" : "2013-05-01 00:00:00",
                        "end_date" : "2013-09-01 00:00:00",
                        "recurrences" : "[1,3,5]"
                    },
                    {
                        "user_id" : 1,
                        "name" : "Workout",
                        "start_time" : 900,
                        "end_time" : 1020,
                        "start_date" : "2013-05-01 00:00:00",
                        "end_date" : "2013-09-01 00:00:00",
                        "recurrences" : "[0,2,4,6]"
                    }
                ],
                "prefs" : {
                    "sched_start" : "2013-07-05 10:00:00",
                    "break" : "20"
                }
            }
        ', true );

        $tasks = array();
        $fixed_events = array();
        foreach( $data[ "tasks" ] as $task ) {
            $task_obj = Task::create($task);
            $tasks[ $task_obj->name ] = $task_obj;
        }
        foreach( $data[ "fixed" ] as $fixed ) {
            $fixed_event_obj = FixedEvent::create($fixed);
            $fixed_events[ $fixed_event_obj->name ] = $fixed_event_obj;
        }
        $prefs = $data[ "prefs" ];

        $temp = self::createSchedule( $tasks, $fixed_events, $prefs );

        return "<pre>" . print_r( $temp, true ) . "</pre>";
    }

    // Populate schedule with tasks
    // Input: prioritized_tasks from self::sortTasks
    //        task reference array
    //        fixed event reference array
    //        preferences for scheduling
    // Output: no output
    //         results stored in class variables
    private function createSchedule( $tasks, $fixed_events, $prefs ) {
        $this->conflicts = array();
        $this->schedule = array();

        if( isset( $prefs[ "break" ] ) ) {
            $break = $prefs[ "break" ];
        }
        else {
            $break = 0;
        }
        
        if( isset( $prefs[ "sched_start" ] ) ) {
            $sched_start = new Carbon($prefs[ "sched_start" ]);
        }
        else {
            $sched_start = Carbon::now();
        }
        $end_of_day = $sched_start->copy()->endOfDay();

        self::fillFixedEvents($fixed_events, $sched_start->copy()->startOfDay());

        $prioritized_tasks = self::sortTasks( $tasks );
        foreach( $prioritized_tasks as $priority => $task_list ) {
            foreach( $task_list as $task_name => $task_due ) {
                if ($sched_start > $task_due){
                    continue;
                }
                $task_data = $tasks[ $task_name ];
                $remaining_task_time = $task_data->duration;
                while($sched_start < $end_of_day){
                    foreach( $this->schedule as $key => $timeslot ){
                        if ($timeslot['start'] > $sched_start ){
                            if ($timeslot['start']->diffInMinutes($sched_start) < $remaining_task_time){
                                $timeslot_length = $timeslot['start']->diffInMinutes($sched_start);
                                array_splice($this->schedule, $key, 0, 
                                    array(array( 'start' => $sched_start->copy(), 
                                            'end' => $sched_start->copy()->addMinutes($timeslot_length),
                                            'task' => $task_data )));
                                $remaining_task_time -= $timeslot_length; 

                                $sched_start = $timeslot['end']->copy();
                            }else{
                                array_splice($this->schedule, $key, 0, 
                                    array(array( 'start' => $sched_start->copy(), 
                                            'end' => $sched_start->copy()->addMinutes($remaining_task_time),
                                            'task' => $task_data )));
                                $remaining_task_time = 0;
                                $sched_start->addMinutes($remaining_task_time);
                                break;
                            }
                        }else if ($timeslot['end'] > $sched_start){
                            $sched_start = $timeslot['end']->copy();
                        }
                    }

                    if ($remaining_task_time == 0){
                        break;
                    }

                    if ($end_of_day->diffInMinutes($sched_start) > $remaining_task_time){
                        $end = $sched_start->copy()->addMinutes($task_data->duration);
                        $remaining_task_time = 0;
                    }else{
                        $remaining_task_time = $end_of_day->diffInMinutes($sched_start);
                        $end = $end_of_day;
                    }
                    array_push($this->schedule, array( 'start' => $sched_start->copy(), 
                                'end' => $end,
                                'task' => $task_data ));
                    $sched_start = $end->copy();
                    break;
                }
            }
        }

        // Return the schedule to the caller.
        return $this->schedule;
    }

    // Sort tasks based on priority, and sort by due date within each priority.
    // Since this is an API call, all tasks (even fixed ones) are treated as
    // tasks to be scheduled.
    // Input: array of unsorted tasks
    // Output: array of arrays of sorted tasks
    //         inner array has key=task->name, value=task->due
    private function sortTasks( $tasks ) {
        $prioritized_tasks = array();
        foreach( $tasks as $task ) {
            if( !isset( $prioritized_tasks[ $task->priority ] ) ) {
                $prioritized_tasks[ $task->priority ] = array();
            }
            $prioritized_tasks[ $task->priority ][ $task->name ] = $task->due;
        }

        foreach( $prioritized_tasks as &$task_list ) {
            uasort( $task_list, function ($a, $b){
                if ( $a->eq($b) ){
                    return 0;
                }
                return $a->lt($b) ? -1 : 1;
            } );
        }

        krsort( $prioritized_tasks );
        return $prioritized_tasks;
    }

    // Finds the time slot for fixed time events
    // Input: start time of event
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function fillFixedEvents( $fixed_events, $sched_date ) {
        usort( $fixed_events, function ($a, $b){
            if ( $a->start_time == $b->start_time ){
                return 0;
            }
            return $a->start_time < $b->start_time ? -1 : 1;
        } );
        foreach ( $fixed_events as $event ){
            $this->schedule[] = array( 'start' => $sched_date->copy()->addMinutes($event->start_time), 
                                        'end' => $sched_date->copy()->addMinutes($event->end_time),
                                        'task' => $event );

        }
    }
}
