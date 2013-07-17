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
        $user = User::getTestUser();
        return "Hello $user->name";
    }

    public function getPhpinfo()
    {
        phpinfo();
    }

    // Make max priority accessible.
    public function getMaxpriority()
    {
        return Task::TASK_MAX_PRIORITY;
    }

    public function postSchedule()
    {
        $sch = null;
        if (Request::is('api/schedule*'))
        {
            // valid json request
            $data = Input::all();
            // error checking omitted
            $tasks = array();
            $fixed_events = array();
            $prefs = null;

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

            if (isset($data['prefs']))
            {
                $prefs = $data['prefs'];
            }
            $sch = $this->createSchedule($tasks, $fixed_events, $prefs);
        }
        else
        {
            // prepare a response, unsupported POST content
            $invalid_text = 'The request could not be fulfilled.\n
                An unsupported content type was used.';
            $response = Response::make( $invalid_text, 400 );
            return $response;
        }
        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    private function createSchedule( $tasks, $fixed_events, $prefs )
    {
        // Reset arrays for the start of the current schedule request
        $this->task_conflicts = array();
        $this->event_conflicts = array();
        $this->schedule = array();
        $this->empty_slots = array();

        // populate variables with caller preferences
        if( isset( $prefs[ 'start' ] ) )
        {
            $this->schedule_start = new Carbon( $prefs[ 'start' ] );
        }
        else
        {
            $this->schedule_start = Carbon::now();
        }
        if( isset( $prefs[ "break" ] ) )
        {
            $break = $prefs[ "break" ];
        }
        else
        {
            $break = 0;
        }

        // begin scheduling with a single infinite time frame
        $this->empty_slots[] = array( 'start' => $this->schedule_start,
                                      'end' => null );
        self::fillFixedEvents( $fixed_events );

        $prioritized_tasks = self::sortTasks( $tasks );

        foreach( $prioritized_tasks as $priority => $task_list )
        {
            foreach( $task_list as $task_name => $task )
            {
                $ret = self::addTask( $tasks[ $task_name ] );
                if( !$ret )
                {
                    $this->task_conflicts[] = $tasks[ $task_name ];
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

    private function fillFixedEvents( $fixed_events )
    {
        // TODO: Will fixed events have collisions?  Do they need to be sorted?
        // Sorted would mean earlier start time+date => implied higher priority
        foreach( $fixed_events as $event )
        {
            if( self::addEvent( $event ) )
            {
                $this->event_conflicts[] = $event->toArray();
            }
        }
    }

    private function addEvent( $event )
    {
        $event_start_date = $event->start_date->gt( $this->schedule_start ) ?
                            $event->start_date->copy() : $this->schedule_start->copy();
        if( 60*$event_start_date->hour + $event_start_date->minute > $event->start_time )
        {
            $event_start_date->addDay();
        }
        $event_start_date->startOfDay();

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

            while( $current_date->lte( $event->end_date ) )
            {

                $empty_slot_id = self::findFixedSlot(
                    $current_date->copy()->addMinutes( $event->start_time ),
                    $current_date->copy()->addMinutes( $event->end_time ) );

                // TODO: What should be done here if not all recurrences can be scheduled?
                if( is_null($empty_slot_id) )
                {
                    return false;
                }

                // Add event to schedule
                $current_event_start = $current_date->copy()->addMinutes( $event->start_time );
                $current_event_end = $current_date->copy()->addMinutes( $event->end_time );
                self::insertSchedule(
                    $current_event_start,
                    $current_event_end,
                    $event );

                // Update empty time slots
                $time_slot = $this->empty_slots[ $empty_slot_id ];
                array_splice( $this->empty_slots, $empty_slot_id, 1, array(
                        array( 'start' => $time_slot[ 'start' ]->copy(),
                               'end' => $current_event_start->copy() ),
                        array( 'start' => $current_event_end->copy(),
                               'end' => is_null($time_slot[ 'end' ]) ?
                                        null : $time_slot[ 'end' ]->copy() )
                    )
                );

                $current_date->addDays( self::DAYS_IN_WEEK );
            }
        }

        return true;
    }

    // Finds the time slot for fixed time events
    // Input: start time of event
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function findFixedSlot( $event_start, $event_end )
    {
        foreach( $this->empty_slots as $slot_id => $empty_slot )
        {
            if( $empty_slot[ 'start' ]->gt( $event_start ) )
            {
                break;
            }

            if( $event_start->gte( $empty_slot[ 'start' ] )
             && ( is_null($empty_slot[ 'end' ]) || $event_end->lte( $empty_slot[ 'end' ] ) ) )
            {
                return $slot_id;
            }
        }

        return null;
    }

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
