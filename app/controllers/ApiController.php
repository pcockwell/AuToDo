<?php

use Carbon\Carbon;

class ApiController extends BaseController
{

    const DAYS_IN_WEEK = 7;
    const MIN_TASK_CHUNK = 30; // in minutes

    private $task_conflicts;
    private $event_conflicts;
    private $schedule;
    private $empty_slots;

    private $schedule_start;
    private $task_break;

    public function getIndex()
    {
        return View::make('hello');
    }

    public function missingMethod($parameters)
    {
        return "ApiController@missingMethod";
    }

    public function getPhpinfo()
    {
        phpinfo();
    }

    // Make max priority accessible.
    private static function maxPriority()
    {
        return Task::TASK_MAX_PRIORITY;
    }

    public function userSchedule($user_id)
    {
        $sch = null; 
        $user = User::find($user_id);
        // error checking omitted
        $tasks = array();
        $fixed_events = array();
        $prefs = $user->preferences ? : new Preference;

        foreach ($user->tasks()->get() as $task)
        {
            $tasks[$task->name] = $task;
        }

        foreach ($user->fixedevents()->get() as $fixed_event)
        {
            $fixed_events[$fixed_event->name] = $fixed_event;
        }

        $sch = $this->createSchedule($tasks, $fixed_events, $prefs);
        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    public function postSchedule()
    {
        $sch = null; 
        // valid json request
        $data = Input::all();
        // error checking omitted
        $tasks = array();
        $fixed_events = array();
        $prefs = new Preference;
        $sched_start = null;

        if (isset($data['Task']))
        {
            foreach ($data['Task'] as $task)
            {
                $tasks[$task->name] = $task;
            }
        }
        if (isset($data['FixedEvent']))
        {
            foreach ($data['FixedEvent'] as $fixed_event)
            {
                $fixed_events[$fixed_event->name] = $fixed_event;
            }
        }

        if (isset($data['Preference']))
        {
            $prefs = $data['Preference'];
        }

        if (isset($data['schedule_start']))
        {
            $sched_start = $data['schedule_start'];
        }
        $sch = $this->createSchedule($tasks, $fixed_events, $prefs, $sched_start);
        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    private function createSchedule( $tasks, $fixed_events, $prefs, $sched_start = null )
    {
        // Reset arrays for the start of the current schedule request
        $this->task_conflicts = array();
        $this->event_conflicts = array();
        $this->schedule = array();
        $this->empty_slots = array();

        // populate variables with caller preferences
        if( !is_null( $sched_start ) )
        {
            $this->schedule_start = new Carbon( $sched_start );
            //$this->schedule_start = $schedule_start->gt( Carbon::now() ) ?
            //                        $schedule_start : Carbon::now();
        }
        else
        {
            $this->schedule_start = Carbon::now();
        }

        $this->task_break = $prefs->break;
        $show_fixed_events = $prefs->show_fixed_events;
        $schedule_until_latest = $prefs->schedule_until_latest;

        // begin scheduling with a single infinite time frame
        $this->empty_slots[] = array( 'start' => $this->schedule_start,
                                      'end' => null );

        $prioritized_tasks = self::sortTasks( $tasks );

        // Find last due date
        $last_due_time = null;
        foreach( $prioritized_tasks as $priority => $task_list )
        {
            $curr_last_due_time = end( $task_list );
            if( is_null( $last_due_time ) || $curr_last_due_time->gt( $last_due_time ) )
            {
                $last_due_time = $curr_last_due_time->copy();
            }
        }

        // Partition empty slots
        self::fillFixedEvents( $fixed_events, $last_due_time, false, true );

        foreach( $prioritized_tasks as $priority => $task_list )
        {
            foreach( $task_list as $task_name => $task )
            {
                if( !self::addTask( $tasks[ $task_name ] ) )
                {
                    $this->task_conflicts[] = $tasks[ $task_name ];
                }
            }
        }

        // Add fixed events to schedule
        if( $show_fixed_events )
        {
            if( $schedule_until_latest )
            {
                $last_due_time = null;
            }
            else
            {
                if (!empty($this->schedule))
                {
                    $last_due_time = end( $this->schedule );
                    $last_due_time = $last_due_time[ 'end' ]->copy();
                }
                else
                {
                    $last_due_time = Carbon::now();
                }
            }
            self::fillFixedEvents( $fixed_events, $last_due_time, true, false );
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
    private function sortTasks( $tasks )
    {
        // An array of priorities each mapping to an array of tasks
        $prioritized_tasks = array();

        // Put each task into its priority array
        foreach( $tasks as $task )
        {
            if( !isset( $prioritized_tasks[ $task->priority ] ) )
            {
                $prioritized_tasks[ $task->priority ] = array();
            }
            $prioritized_tasks[ $task->priority ][ $task->name ] = $task->due;
        }

        foreach( $prioritized_tasks as &$task_list )
        {
            uasort( $task_list, 
                function ($a, $b)
                {
                    if ( $a->eq($b) )
                    {
                        return 0;
                    }
                    return $a->lt($b) ? -1 : 1;
                }
            );
        }

        krsort( $prioritized_tasks );
        return $prioritized_tasks;
    }

    private function fillFixedEvents( $fixed_events, $last_due_time,
                                      $fill_schedule, $fill_slots )
    {
        // TODO: Will fixed events have collisions?  Do they need to be sorted?
        // Sorted would mean earlier start time+date => implied higher priority
        foreach( $fixed_events as $event )
        {
            if( !self::addEvent( $event, $last_due_time, $fill_schedule, $fill_slots ) )
            {
                $this->event_conflicts[] = $event->toArray();
            }
        }
    }

    private function addEvent( $event, $last_due_time, $fill_schedule, $fill_slots )
    {
        $event_start_date = $event->start_date->gt( $this->schedule_start ) ?
                            $event->start_date->copy() : $this->schedule_start->copy();
        if( 60*$event_start_date->hour + $event_start_date->minute > $event->start_time )
        {
            $event_start_date->addDay();
        }
        $event_start_date->startOfDay();

        if( is_null( $last_due_time ) )
        {
            $event_end_date = $event->end_date->copy();
        }
        else
        {
            $event_end_date = $event->end_date->lt( $last_due_time ) ?
                              $event->end_date->copy() : $last_due_time;
        }
        $event_end_date->endOfDay();

        // Schedule each recurrence of this event
        $event_recurrences = $event->getRecurrences();
        foreach( $event_recurrences as $day_of_week )
        {
            $current_date = $event_start_date->copy();

            $diff = $day_of_week - $current_date->dayOfWeek;
            if( $diff < 0 )
            {
                $diff += self::DAYS_IN_WEEK;
            }

            $current_date->addDays( $diff );

            while( $current_date->lte( $event_end_date ) )
            {

//                 $empty_slot_id = self::findFixedSlot(
//                     $current_date->copy()->addMinutes( $event->start_time ),
//                     $current_date->copy()->addMinutes( $event->end_time ) );

//                 if( is_null($empty_slot_id) )
//                 {
//                     return false;
//                 }

                // Add event to schedule
                if( $fill_schedule )
                {
                    $current_event_start = $current_date->copy()->addMinutes( $event->start_time );
                    $current_event_end = $current_date->copy()->addMinutes( $event->end_time );
                    self::insertSchedule(
                        $current_event_start,
                        $current_event_end,
                        $event );
                }

                // Update empty time slots
                if( $fill_slots )
                {
                    self::markFixedSlots(
                        $current_date->copy()->addMinutes( $event->start_time ),
                        $current_date->copy()->addMinutes( $event->end_time ) );
                }

//     This part commented out just in case we ever want to do conflict checking for
//     fixed events.
//                 $time_slot = $this->empty_slots[ $empty_slot_id ];
//                 array_splice( $this->empty_slots, $empty_slot_id, 1, array(
//                         array( 'start' => $time_slot[ 'start' ]->copy(),
//                                'end' => $current_event_start->copy() ),
//                         array( 'start' => $current_event_end->copy(),
//                                'end' => is_null($time_slot[ 'end' ]) ?
//                                         null : $time_slot[ 'end' ]->copy() )
//                     )
//                 );

                $current_date->addDays( self::DAYS_IN_WEEK );
            }
        }

        return true;
    }

    private function markFixedSlots( $start_time, $end_time )
    {
        $low = 0;
        $high = count( $this->empty_slots )-1;
        while( true )
        {
            assert( $low <= $high );
            $mid = floor( ( $low+$high )/2 );
            $time_slot = $this->empty_slots[ $mid ];

            if( $start_time->gte( $time_slot[ 'start' ] )
             && ( is_null( $time_slot[ 'end' ] )
               || $start_time->lte( $time_slot[ 'end' ] ) ) )
            {
                break;
            }
            elseif( !is_null( $time_slot[ 'end' ] )
                 && $start_time->gt( $time_slot[ 'end' ] ) )
            {
                $low = $mid+1;
            }
            else
            {
                $high = $mid-1;
            }
        }

        assert( isset( $mid ) );
        while( is_null( $this->empty_slots[ $mid ][ 'end' ] )
            || $start_time->lte( $this->empty_slots[ $mid ][ 'end' ] ) )
        {
            $time_slot = $this->empty_slots[ $mid ];
            $in_start = $start_time->gte( $time_slot[ 'start' ] );
            $in_end = is_null( $time_slot[ 'end' ] ) ?
                      true : $end_time->lte( $time_slot[ 'end' ] );

            if( $in_start && $in_end )
            {
                array_splice( $this->empty_slots, $mid, 1, array(
                        array( 'start' => $time_slot[ 'start' ]->copy(),
                               'end' => $start_time->copy() ),
                        array( 'start' => $end_time->copy(),
                               'end' => is_null( $time_slot[ 'end' ] ) ?
                                        null : $time_slot[ 'end' ]->copy() )
                    )
                );
                break;
            }
            elseif( $in_start && !$in_end )
            {
                array_splice( $this->empty_slots, $mid, 1, array(
                        array( 'start' => $time_slot[ 'start' ]->copy(),
                               'end' => $start_time->copy() )
                    )
                );
                $mid += 1;
            }
            elseif( !$in_start && $in_end )
            {
                array_splice( $this->empty_slots, $mid, 1, array(
                        array( 'start' => $end_time->copy(),
                               'end' => is_null( $time_slot[ 'end' ] ) ?
                                        null : $time_slot[ 'end' ]->copy() )
                    )
                );
                break;
            }
            else
            {
                array_splice( $this->empty_slots, $mid, 1 );
            }
        }
    }

//     This function commented out just in case we ever want to do conflict checking for
//     fixed events.
//     private function findFixedSlot( $event_start, $event_end )
//     {
//         foreach( $this->empty_slots as $slot_id => $empty_slot )
//         {
//             if( $empty_slot[ 'start' ]->gt( $event_start ) )
//             {
//                 break;
//             }
// 
//             if( $event_start->gte( $empty_slot[ 'start' ] )
//              && ( is_null($empty_slot[ 'end' ]) || $event_end->lte( $empty_slot[ 'end' ] ) ) )
//             {
//                 return $slot_id;
//             }
//         }
// 
//         return null;
//     }

    private function addTask( $task )
    {
        $task_duration = $task->duration;
        $task_min_split = is_null($task->min_split) ? self::MIN_TASK_CHUNK : $task->min_split;

        while( $task_duration > 0 )
        {
            $empty_slot_id = self::findTimeSlot( $task_duration,
                                                 $task->due,
                                                 $task_min_split );

            // TODO: half scheduled tasks
            if( is_null($empty_slot_id) )
            {
                return false;
            }

            $time_slot = $this->empty_slots[ $empty_slot_id ];
            if( is_null($time_slot[ 'end' ]) )
            {
                $time_slot_duration = null;
            }
            else
            {
                $time_slot_duration = $time_slot[ 'start' ]->diffInMinutes( $time_slot[ 'end' ] );
            }

            if( is_null($time_slot_duration) || $time_slot_duration >= $task_duration + $this->task_break )
            {
                // Add event to schedule
                self::insertSchedule(
                    $time_slot[ 'start' ],
                    $time_slot[ 'start' ]->copy()->addMinutes( $task_duration ),
                    $task );

                // Update empty time slots
                if( is_null($time_slot_duration)
                 || $time_slot_duration - ( $task_duration + $this->task_break ) > 0 )
                {
                    array_splice( $this->empty_slots, $empty_slot_id, 1, array(
                            array( 'start' => $time_slot[ 'start' ]->copy()->addMinutes( $task_duration + $this->task_break ),
                                   'end' => is_null($time_slot[ 'end' ]) ?
                                            null : $time_slot[ 'end' ]->copy() )
                        )
                    );
                }
                else
                {
                    array_splice( $this->empty_slots, $empty_slot_id, 1 );
                }

                $task_duration = 0;
            }
            else
            {
                if( $task_duration - ( $time_slot_duration + $this->task_break ) >= $task_min_split )
                {
                    self::insertSchedule(
                        $time_slot[ 'start' ],
                        $time_slot[ 'end' ],
                        $task );

                    // Update empty time slots
                    array_splice( $this->empty_slots, $empty_slot_id, 1 );

                    $task_duration -= $time_slot_duration;
                }
                else
                {
                    // Correctness: $duration_to_schedule will not exceed time slot
                    // If enter else clause, then:
                    // $task_duration - $time_slot_duration < $task_min_split
                    //     $task_duration - $task_min_split < $time_slot_duration
                    $duration_to_schedule = $task_duration - $task_min_split;
                    // TODO: test and remove assertion
                    assert( $time_slot_duration >= $duration_to_schedule + $this->task_break );
                    self::insertSchedule(
                        $time_slot[ 'start' ],
                        $time_slot[ 'start' ]->copy()->addMinutes( $duration_to_schedule ),
                        $task );

                    // Update empty time slots
                    if( $time_slot_duration >= $duration_to_schedule + $this->task_break )
                    {
                        array_splice( $this->empty_slots, $empty_slot_id, 1, array(
                                array( 'start' => $time_slot[ 'start' ]->copy()->addMinutes( $task_duration + $this->task_break ),
                                       'end' => $time_slot[ 'end' ] === null ?
                                                null : $time_slot[ 'end' ]->copy() )
                            )
                        );
                    }

                    $task_duration -= $duration_to_schedule;

                }
            }
        }

        return true;
    }

    // Finds an appropriate time slot for non-fixed time events
    // Input: target task duration
    //        target task due time
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function findTimeSlot( $task_duration, $task_due, $min_split )
    {
        foreach( $this->empty_slots as $slot_id => $empty_slot )
        {
            if( $empty_slot[ 'start' ]->gte( $task_due ) )
            {
                break;
            }

            if( is_null($empty_slot[ 'end' ]) )
            {
                $empty_slot_duration = null;
            }
            else
            {
                $empty_slot_duration = $empty_slot[ 'start' ]->diffInMinutes( $empty_slot[ 'end' ] );
            }

            // Two times min_split to make sure that the other half of the split task
            // will have at least min_split duration
            if( is_null($empty_slot_duration)
             || ( $task_duration >= 2*$min_split
             && $empty_slot_duration >= $min_split + $this->task_break )
             || $empty_slot_duration >= $task_duration + $this->task_break )
            {
                return $slot_id;
            }
        }

        return null;
    }

    private function insertSchedule( $start, $end, $task )
    {
        $idx = self::insertIndex( 0, count( $this->schedule )-1, $start );
        array_splice( $this->schedule, $idx, 0, array( array(
                'start' => $start->copy(),
                'end' => $end->copy(),
                'task' => $task->toArray()
            ) )
        );
    }

    private function insertIndex( $low, $high, $start )
    {
        if( $low > $high )
        {
            return $high+1;
        }

        $mid = floor( ( $low+$high )/2 );

        if( $start->lt( $this->schedule[ $mid ][ 'start' ] ) )
        {
            return self::insertIndex( $low, $mid-1, $start );
        }
        else if( $start->gt( $this->schedule[ $mid ][ 'start' ] ) )
        {
            return self::insertIndex( $mid+1, $high, $start );
        }
        else
        {
            return $mid;
        }
    }

}
