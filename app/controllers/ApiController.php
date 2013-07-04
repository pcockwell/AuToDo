<?php

class ApiController extends BaseController {

    private $conflicts;
    private $schedule;
    private $empty_slots;
    private $task_max_priority = 3;

	public function getIndex()
	{
		return View::make('hello');
	}

	public function getHello($name){
		return "Hello $name";
	}

	public function get_name($name){
		return "Hello $name";
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

    // Make max priority accessible.
    public function getMaxPriority() {
        return $this->task_max_priority;
    }

    public function post_schedule() {
        if (Request::is('api/schedule*')) {
            if (Input::isJson()) {
                // valid json request
                $data = Input::all();
                // error checking omitted
                $tasks_obj_arr = null;
                $prefs = null;
                if (isset($data['tasks'])) {
                    $tasks = $data['tasks'];
                    $tasks_obj = json_decode(json_encode($tasks), false);
                    foreach ($tasks_obj as $obj) {
                        $tasks_obj_arr[$obj->name] = $obj;
                    }
                } else if (isset($data['prefs'])) {
                    $prefs = $data['prefs'];
                }
                $sch = $this->createSchedule($tasks_obj_arr, $prefs);
                var_dump($sch);
            } else {
                // prepare a response, unsupported POST content
            }
        }
        // prepare a 200 OK response
        return;
    }

	public function missingMethod($parameters){
		$user = User::getTestUser();
		return "Hello $user->name";
	}

    // Populate schedule with tasks
    // Input: prioritized_tasks from self::sortTasks
    //        task reference array
    //        preferences for scheduling
    // Output: no output
    //         results stored in class variables
    private function createSchedule( $tasks, $prefs ) {
        $this->conflicts = array();
        $this->schedule = array();
        if( isset( $prefs[ "start" ] ) ) {
            $now = $prefs[ "start" ];
        }
        else {
            $now = 0;
        }
        if( isset( $prefs[ "break" ] ) ) {
            $break = $prefs[ "break" ];
        }
        else {
            $break = 0;
        }

        $this->empty_slots = array();
        $this->empty_slots[ $now ] = -1;

        $prioritized_tasks = self::sortTasks( $tasks );

        foreach( $prioritized_tasks as $priority => $task_list ) {

            // If priority corresponds to fixed events/tasks
            if( $priority == $this->task_max_priority+1 ) {
                foreach( $task_list as $task_name => $task_due ) {
                    $task_data = $tasks[ $task_name ];
                    $slot_data = self::fixedTimeSlot( $task_data->start, $task_data->end );
                    if( $slot_data[ "start" ] >= $now ) {
                        $this->schedule[ $task_data->start ] = $task_data;
                        if( $task_data->start-$slot_data[ "start" ] > 0 ) {
                            $this->empty_slots[ $slot_data[ "start" ] ] = $task_data->start - $slot_data[ "start" ];
                        }
                        else {
                            unset( $this->empty_slots[ $slot_data[ "start" ] ] );
                        }
                        if( $slot_data[ "duration" ] == -1 ) {
                            $this->empty_slots[ $task_data->end ] = -1;
                        }
                        elseif( ($slot_data[ "start" ]+$slot_data[ "duration" ])-$task_data->end > 0 ) {
                            $this->empty_slots[ $task_data->end ] = ($slot_data[ "start" ]+$slot_data[ "duration" ]) - $task_data->end;
                        }
                    }
                    else {
                        $this->conflicts[ $task_name ] = $task_data;
                    }
                }
            }

            // For all other tasks
            else {
                foreach( $task_list as $task_name => $task_due ) {
                    $task_data = $tasks[ $task_name ];
                    $slot_data = self::nextTimeSlot( $task_data->duration+$break, $task_due );
                    if( $slot_data[ "start" ] >= $now ) {
                        $this->schedule[ $slot_data[ "start" ] ] = $task_data;
                        unset( $this->empty_slots[ $slot_data[ "start" ] ] );
                        if( $slot_data[ "duration" ] == -1 ) {
                            $this->empty_slots[ $slot_data[ "start" ]+$task_data->duration+$break ] = -1;
                        }
                        elseif( $slot_data[ "duration" ]-($task_data->duration+$break) ) {
                            $this->empty_slots[ $slot_data[ "start" ]+$task_data->duration+$break ] = $slot_data[ "duration" ]-($task_data->duration+$break);
                        }
                    }
                    else {
                        $this->conflicts[ $task_name ] = $task_data;
                    }
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

        foreach( $prioritized_tasks as $task_list ) {
            asort( $task_list );
        }

        krsort( $prioritized_tasks );
        return $prioritized_tasks;
    }

    // Finds the time slot for fixed time events
    // Input: start time of event
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function fixedTimeSlot( $task_start, $task_end ) {
        ksort( $this->empty_slots );

        foreach( $this->empty_slot as $start => $duration ) {
            if( $task_start >= $start ) {
                if( $duration == -1 || $task_end <= $start+$duration ) {
                    return array( "start" => $start,
                                  "duration" => $duration );
                }
            }
            if( $start > $task_start ) {
                break;
            }
        }

        return array( "start" => -1,
                      "duration" => -1 );
    }

    // Finds an appropriate time slot for non-fixed time events
    // Input: target task duration
    //        target task due time
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function nextTimeSlot( $task_duration, $task_due ) {
        ksort( $this->empty_slots );
        foreach( $this->empty_slots as $start => $duration ) {
            if( $start+$task_duration <= $task_due ) {
                if( $duration == -1 || $duration >= $task_duration ) {
                    return array( "start" => $start,
                                  "duration" => $duration );
                }
            }
            if( $start > $task_due ) {
                break;
            }
        }

        return array( "start" => -1,
                      "duration" => -1 );
    }
}
