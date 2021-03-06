<?php

use Carbon\Carbon;
use Autodo\GCal\Parser;

use Autodo\Support\InputConverter;

class ApiController extends BaseController
{

    const DAYS_IN_WEEK = 7;

    private $task_conflicts;
    private $event_conflicts;
    private $schedule;
    private $schedule_slots;

    private $schedule_start;

    private $google_client;
    private $gcal_service;

    public function __construct()
    {
        $this->google_client = new Google_Client();
        $this->google_client->addScope("https://www.googleapis.com/auth/calendar");

        $this->gcal_service = new Google_Service_Calendar($this->google_client);

        if (Session::has('access_token')) 
        {
            $this->google_client->setAccessToken(Session::get('access_token'));

            if ($this->google_client->isAccessTokenExpired())
            {
                Session::forget('access_token');
                $this->google_client->setAccessToken('null');
            }
        }
    }

    public function getIcsfile()
    {
      $url = URL::to('basic.ics');
      print_r("url = ");
      print_r($url);
      return Redirect::to($url);
    }

    public function missingMethod($parameters)
    {
        return "ApiController@missingMethod";
    }

    public function getPhpinfo()
    {
        phpinfo();
    }

    public function getGoogle()
    {
        if (array_key_exists('logout', Input::all()))
        {
            Session::forget('access_token');
            return 'You have just been logged out.';
        }
        
        if (!Session::has('access_token'))
        {
            return Redirect::to($this->google_client->createAuthUrl());
        }

        $events = $this->gcal_service->events->listEvents('primary', array('timeMin' => Carbon::now()->toATOMString()));

        $items = Parser::parseEventsList($events->items);
        foreach ($items as $item) {
            echo "<pre>" . print_r($item, true) . "</pre>";
        }
    }

    public function oauth2Callback()
    {
        if (Request::getMethod() == 'GET' && Input::has('code'))
        {
            $this->google_client->authenticate(Input::get('code'));
            Session::put('access_token', $this->google_client->getAccessToken());
        }
        return Redirect::to('/api/google');
    }

    // Make max priority accessible.
    private static function maxPriority()
    {
        return Task::TASK_MAX_PRIORITY;
    }

    public function userSchedule()
    {
        $sch = null; 
        //$user = User::find($user_id);
        $user = Auth::user();
        if (!isset($user)) {
            return Response::make( "Not found", 404 );
        }
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
        $this->setScheduleFile($sch, $user);

        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    private function setScheduleFile($schedule, $user)
    {
        $ics_file_name = "";
        $ics_file_entry = DB::table('ics_files')
            ->where('user_id', $user->id)->first();
        if ($ics_file_entry)
        {
            $ics_file_name = $ics_file_entry->filename;
        }
        else
        {
            $ics_file_name = md5(time()).$user->id;
            DB::table('ics_files')->insert(
                array('user_id' => $user->id,
                      'filename' => $ics_file_name)
            );
        }

        $ics = $this->format_ics($schedule);

        $filepath = base_path().'/public/ics/'.$ics_file_name.'.ics';
        file_put_contents($filepath, $ics);
    }

    private function format_ics($schedule)
    {
        $data = "BEGIN:VCALENDAR\n".
                "VERSION:2.0\n".
                "PRODID:-//AuToDo API//EN\n";
        foreach ($schedule as $evs)
        {
            $data = $data."BEGIN:VEVENT\n";

            // Change into ics-happy format
            $st_date = $evs['start'];
            $dtstart = str_pad(strval($st_date->year), 4, "0", STR_PAD_LEFT);
            $dtstart .= str_pad(strval($st_date->month), 2, "0", STR_PAD_LEFT);
            $dtstart .= str_pad(strval($st_date->day), 2, "0", STR_PAD_LEFT);
            $dtstart .= "T";
            $dtstart .= str_pad(strval($st_date->hour), 2, "0", STR_PAD_LEFT);
            $dtstart .= str_pad(strval($st_date->minute), 2, "0", STR_PAD_LEFT);
            $dtstart .= str_pad(strval($st_date->second), 2, "0", STR_PAD_LEFT);
            $data = $data."DTSTART:".$dtstart."\n";

            $en_date = $evs['end'];
            $dtend = str_pad(strval($en_date->year), 4, "0", STR_PAD_LEFT);
            $dtend .= str_pad(strval($en_date->month), 2, "0", STR_PAD_LEFT);
            $dtend .= str_pad(strval($en_date->day), 2, "0", STR_PAD_LEFT);
            $dtend .= "T";
            $dtend .= str_pad(strval($en_date->hour), 2, "0", STR_PAD_LEFT);
            $dtend .= str_pad(strval($en_date->minute), 2, "0", STR_PAD_LEFT);
            $dtend .= str_pad(strval($en_date->second), 2, "0", STR_PAD_LEFT);
            $data = $data."DTEND:".$dtend."\n";

            $data = $data."SUMMARY:".$evs['task']['name']."\n";

            $data = $data."END:VEVENT\n";
        }

        $data = $data."END:VCALENDAR\n";
        return $data;
    }

    public function postSchedule()
    {
        $sch = null; 
        // valid json request
        $data = Input::all();
        // error checking omitted
        $tasks = array();
        $dep_graph = null;
        $fixed_events = array();
        $prefs = new Preference;
        $sched_start = null;

        if (isset($data['schedule_start']))
        {
            $sched_start = $data['schedule_start'];
        }

        if (isset($data['Task']))
        {
            foreach ($data['Task'] as $task)
            {
                $tasks[$task->name] = $task;
            }
        }

        if (isset($data['DependencyGraph']))
        {
            $dep_graph = $data['DependencyGraph'];
        }

        if (isset($data['google_calendar']) && $data['google_calendar'])
        {
            //Can't seem to save the session between calls to different functions

            if (Session::has('access_token'))
            {
                $events = $this->gcal_service->events->listEvents('primary', array('timeMin' => Carbon::parse( $sched_start )->toATOMString()));

                $events = Parser::parseEventsList($events->items);
                foreach ($events as $fixed_event) 
                {
                    $fixed_events[$fixed_event->name] = $fixed_event;
                }
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
        $sch = $this->createSchedule($tasks, $fixed_events, $prefs, $sched_start, $dep_graph);
        // prepare a 200 OK response
        $response = Response::make( $sch, 200 );
        return $response;
    }

    private function createSchedule($tasks, $fixed_events, $prefs, $sched_start = null, $dep_graph = null)
    {
        // Reset arrays for the start of the current schedule request
        $this->task_conflicts = array();
        $this->event_conflicts = array();
        $this->schedule = array();
        $this->schedule_slots = array();

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

        $show_fixed_events = $prefs->show_fixed_events;
        $schedule_until_latest = $prefs->schedule_until_latest;

        // begin scheduling with a single infinite time frame
        $this->schedule_slots[] = array( 'start' => $this->schedule_start,
                                      'end' => null,
                                      'content' => null );

        $prioritized_tasks = self::sortTasks( $tasks, $dep_graph );

        // Find last due date
        $last_due_time = null;
        foreach( $prioritized_tasks as $priority => $task_list )
        {
            $curr_last_due_time = $tasks[end($task_list)]->due;
            if( is_null( $last_due_time ) || $curr_last_due_time->gt( $last_due_time ) )
            {
                $last_due_time = $curr_last_due_time->copy();
            }
        }

        // Add fixed events to schedule
        if( $show_fixed_events && $schedule_until_latest )
        {
            foreach( $fixed_events as $event )
            {
                $curr_last_due_time = $event->end_date->copy()->endOfDay();
                if( is_null( $last_due_time ) || $curr_last_due_time->gt( $last_due_time ) )
                {
                    $last_due_time = $curr_last_due_time->copy();
                }
            }
        }

        // TODO: Will fixed events have collisions?  Do they need to be sorted?
        // Sorted would mean earlier start time+date => implied higher priority

        //Fill slots
        foreach( $fixed_events as $event )
        {
            if( !self::addEvent( $event, $last_due_time, false ) )
            {
                $this->event_conflicts[] = $event->toArray();
            }
        }

        foreach( $prioritized_tasks as $priority => $task_list )
        {
            foreach( $task_list as $task_name )
            {
                if( !self::addTask( $tasks[ $task_name ] ) )
                {
                    $this->task_conflicts[] = $tasks[ $task_name ];
                }
            }
        }

        self::fillSchedule($show_fixed_events, count($tasks) - count($this->task_conflicts));

        // Return the schedule to the caller.
        return $this->schedule;
    }

    private function fillSchedule($show_fixed_events, $scheduled_task_count)
    {
        $last_due_time = null;
        if ($scheduled_task_count == 0)
        {
            $last_due_time = $this->schedule_start->copy()->endOfDay();
        }

        $tasks_added_to_schedule = 0;

        foreach ($this->schedule_slots as $slot_id => $slot)
        {
            if (!is_null($last_due_time) && $slot['start']->gte($last_due_time))
            {
                break;
            }

            if ($slot['content'] == null || (!$show_fixed_events && $slot['content'] instanceof FixedEvent))
            {
                continue;
            }
            else
            {
                $this->schedule[] = array(
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'task' => $slot['content']->toArray()
                );
                if ($slot['content'] instanceof Task)
                {
                    $tasks_added_to_schedule++;
                    if ($tasks_added_to_schedule == $scheduled_task_count)
                    {
                        $last_due_time = $slot['end']->copy()->endOfDay();
                    }
                }
            }
        }
    } 

    // Sort tasks based on priority, and sort by due date within each priority.
    // Since this is an API call, all tasks (even fixed ones) are treated as
    // tasks to be scheduled.
    // Input: array of unsorted tasks
    // Output: array of arrays of sorted tasks
    //         inner array has no key, value=task->name
    private function sortTasks( $tasks, $dep_graph )
    {
        // An array of priorities each mapping to an array of tasks
        $prioritized_tasks = array();

        if (is_null($dep_graph)) {
          // Put each task into its priority array
          foreach( $tasks as $task )
          {
              if( !isset( $prioritized_tasks[ $task->priority ] ) )
              {
                  $prioritized_tasks[ $task->priority ] = array();
              }
              $prioritized_tasks[ $task->priority ][] =
                  array('due' => $task->due, 'name' => $task->name);
          }

          foreach ($prioritized_tasks as &$task_list) {
            usort($task_list,
                function($a, $b) {
                  if($a['due']->eq($b['due'])) {
                    return 0;
                  }
                  return $a['due']->lt($b['due']) ? -1 : 1;
                });
          }

          foreach ($prioritized_tasks as &$task_list) {
            foreach ($task_list as &$task_details) {
              $task_details = $task_details['name'];
            }
          }
        } else {
          // An array of name mapped tasks for dependency sorting
          $dep_tasks = array();
          foreach ($tasks as $task) {
            $dep_tasks[$task->name] = $task;
          }

          // Get an array of dep sorted tasks. The array contains the name of
          // the task.
          $dep_sorted_tasks = $dep_graph->sortTasks($dep_tasks);
          $dep_free_tasks = $dep_graph->depFreeTasks($dep_tasks);

          usort($dep_free_tasks,
              function($a, $b) {
                if($a->due->eq($b->due)) {
                  return 0;
                }
                return $a->due->lt($b->due) ? -1 : 1;
              });

          // $dep_free_tasks is now sorted by due date and contains only tasks
          // that are not found within the dependency graph.
          $i = 0; // Pointer for $dep_sorted_tasks
          $j = 0; // Pointer for $dep_free_tasks
          while ($i < count($dep_sorted_tasks) && $j < count($dep_free_tasks)) {
            if ($dep_free_tasks[$j]->due->lt(
                $dep_tasks[$dep_sorted_tasks[$i]]->due)) {
              $task_to_add = $dep_free_tasks[$j];
              ++$j;
            } else {
              $task_to_add = $dep_tasks[$dep_sorted_tasks[$i]];
              ++$i;
            }
            
            if(!array_key_exists($task_to_add->priority, $prioritized_tasks)) {
              $prioritized_tasks[$task_to_add->priority] = array();
            }
            $prioritized_tasks[$task_to_add->priority][] = $task_to_add->name;
          }

          for(; $i < count($dep_sorted_tasks); ++$i) {
            $curr_task = $dep_tasks[$dep_sorted_tasks[$i]];
            if(!array_key_exists($curr_task->priority, $prioritized_tasks)) {
              $prioritized_tasks[$curr_task->priority] = array();
            }
            $prioritized_tasks[$curr_task->priority][] = $curr_task->name;
          }

          for(; $j < count($dep_free_tasks); ++$j) {
            $curr_task = $dep_free_tasks[$j];
            if(!array_key_exists($curr_task->priority, $prioritized_tasks)) {
              $prioritized_tasks[$curr_task->priority] = array();
            }
            $prioritized_tasks[$curr_task->priority][] = $curr_task->name;
          }
        }

        krsort( $prioritized_tasks );
        return $prioritized_tasks;
    }

    private function addEvent( $event, $last_due_time, $fill_schedule )
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

        if (!empty($event_recurrences))
        {
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
                        $idx = self::insertIndex( 0, count( $this->schedule )-1, $current_event_start );
                        array_splice( $this->schedule, $idx, 0, array( array(
                                'start' => $current_event_start->copy(),
                                'end' => $current_event_end->copy(),
                                'task' => $event->toArray()
                            ) )
                        );
                    }
                    else
                    {
                        self::fillFixedSlot( $current_date, $event );
                    }

    //     This part commented out just in case we ever want to do conflict checking for
    //     fixed events.
    //                 $time_slot = $this->schedule_slots[ $empty_slot_id ];
    //                 array_splice( $this->schedule_slots, $empty_slot_id, 1, array(
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
        }
        else
        {
            $current_date = $event_start_date->copy();
            self::fillFixedSlot( $current_date, $event );

        }

        return true;
    }

    private function fillFixedSlot( $current_date, $event )
    {
        $low = 0;
        $max_slot_id = count( $this->schedule_slots )-1;
        $high = $max_slot_id;

        $current_date = $current_date->copy()->startOfDay();
        $start_time = $current_date->copy()->addMinutes( $event->start_time );
        $end_time = $current_date->copy()->addMinutes( $event->end_time );

        while( true )
        {
            assert( $low <= $high );
            $mid = floor( ( $low+$high )/2 );
            $time_slot = $this->schedule_slots[ $mid ];

            $timeframe_start = $time_slot['start'];
            $timeframe_end = $time_slot['end'];

            if ($mid > 0)
            {
                $timeframe_start = $this->schedule_slots[ $mid - 1 ]['end'];
            }

            if ($mid < $max_slot_id)
            {
                $timeframe_end = $this->schedule_slots[ $mid + 1 ]['start'];
            }

            if( $start_time->gte( $timeframe_start )
                && ( is_null( $timeframe_end )
                    || $start_time->lte( $timeframe_end ) ) )
            {
                break;
            }
            elseif( !is_null( $timeframe_end )
                 && $start_time->gt( $timeframe_end ) )
            {
                $low = $mid+1;
            }
            else
            {
                $high = $mid-1;
            }
        }

        assert( isset( $mid ) );
        $mid = (int) $mid;
        $time_slot = $this->schedule_slots[ $mid ];

        if ( is_null($time_slot['content']) )
        {
            // extra_break is an array that shows how much break time is not
            // accounted for between the slots before and after the chosen
            // time slot in the schedule_slots array
            $extra_break = self::getExtraBreakTime($mid, $event);

            $start_with_break_buffer = $start_time->copy()->subMinutes($extra_break['before']);
            $end_with_break_buffer = $end_time->copy()->addMinutes($extra_break['after']);

            $in_start = $time_slot[ 'start' ]->lte( $start_with_break_buffer );
            $in_end = is_null( $time_slot[ 'end' ] ) ?
                      true : $time_slot[ 'end' ]->gte( $end_with_break_buffer );

            // TODO (oscar): What about in later cases for in_start and in_end?
            // Is the task
            // always guaranteed to fit into the time slot? If no, then is it
            // just recording a portion of the fixed event?
            //
            // Nested Else case? I thought the binary search above made sure
            // that the
            // time slot at slot[$mid] was sufficient to hold the task. Why are
            // we checking the rest of the array?
            //
            // If the time slot is currently holding an event: are breaks
            // flexible in that we schedule as much as possible of the requested
            // break?
            if( $in_start && $in_end )
            {
                array_splice( $this->schedule_slots, $mid, 1, 
                    array(
                        array( 'start' => $time_slot[ 'start' ]->copy(),
                               'end' => $start_with_break_buffer,
                               'content' => $time_slot[ 'content' ] ),
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event ),
                        array( 'start' => $end_with_break_buffer,
                               'end' => is_null( $time_slot[ 'end' ] ) ?
                                        null : $time_slot[ 'end' ]->copy(),
                               'content' => $time_slot[ 'content' ] )
                    )
                );
            }
            elseif( $in_start && !$in_end )
            {
                array_splice( $this->schedule_slots, $mid, 1, 
                    array(
                        array( 'start' => $time_slot[ 'start' ]->copy(),
                               'end' => $start_with_break_buffer,
                               'content' => $time_slot[ 'content' ] ),
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event ),
                    )
                );
            }
            elseif( !$in_start && $in_end )
            {
                array_splice( $this->schedule_slots, $mid, 1, 
                    array(
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event ),
                        array( 'start' => $end_with_break_buffer,
                               'end' => is_null( $time_slot[ 'end' ] ) ?
                                        null : $time_slot[ 'end' ]->copy(),
                               'content' => $time_slot[ 'content' ] )
                    )
                );
            }
            else
            {
                $num_slots_to_replace = 1;
                $cur_slot_id_to_check = $mid + 1;
                $end_time_with_break = $end_time->copy()->addMinutes($event->break_after);
                while ($cur_slot_id_to_check <= $max_slot_id)
                {
                    $next_slot_end_with_break = $this->schedule_slots[ $cur_slot_id_to_check ]['end']->copy()->addMinutes($this->schedule_slots[ $cur_slot_id_to_check ]['content']->break_after);
                    if ($end_time_with_break->gte($next_slot_end_with_break))
                    {
                        $num_slots_to_replace++;
                    }
                    $cur_slot_id_to_check++;
                }

                array_splice( $this->schedule_slots, $mid, $num_slots_to_replace, 
                    array(
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event )
                    ) 
                );
            }
        }
        else
        {
            //Current time slot has an event in it
            $other_event = $time_slot['content'];

            //Other event begins before this one
            if ($time_slot['start']->lte($start_time))
            {
                $other_event_start_with_break = $time_slot['start']->copy()->subMinutes($other_event->break_before);
                $start_with_break = $start_time->copy()->subMinutes($event->break_before);

                // 
                $time_slot['content']->break_before += max($start_with_break->diffInMinutes($other_event_start_with_break), 0);

                $other_event_end_with_break = $time_slot['end']->copy()->addMinutes($other_event->break_after);
                $end_with_break = $end_time->copy()->addMinutes($event->break_after);
                $time_slot_after = $this->schedule_slots[ $mid + 1 ];

                $event->break_after += max($end_with_break->diffInMinutes($other_event_end_with_break), 0);

                if (is_null($time_slot_after['content']))
                {
                    $time_slot_after['start'] = $end_time->copy()->addMinutes($event->break_after);
                }

                array_splice( $this->schedule_slots, $mid, 0, 
                    array(
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event )
                    )
                );
            }
            //New event begins before this event (aka within other event's break time)
            else
            {
                $other_event_start_with_break = $time_slot['start']->copy()->subMinutes($other_event->break_before);
                $start_with_break = $start_time->copy()->subMinutes($event->break_before);

                $event->break_before += max($other_event_start_with_break->diffInMinutes($start_with_break), 0);

                //Need to check slot before this to see if it is occupied or not
                $slot_before_id = $mid - 1;
                if (array_key_exists($slot_before_id, $this->schedule_slots))
                {
                    //If it exists and is unoccupied, shorten it and add this event
                    if (is_null($this->schedule_slots[$slot_before_id]['content']))
                    {
                        $start_with_break_buffer = $start_time->copy()->subMinutes($event->break_before);
                        array_splice( $this->schedule_slots, $slot_before_id, 1, 
                            array(
                                array( 'start' => $time_slot[ 'start' ]->copy(),
                                       'end' => $start_with_break_buffer,
                                       'content' => $time_slot[ 'content' ] ),
                                array( 'start' => $start_time->copy(),
                                       'end' => $end_time->copy(),
                                       'content' => $event ),
                            )
                        );
                    }
                    //If occupied, simply add this event after it 
                    else
                    {
                        array_splice( $this->schedule_slots, $slot_before_id, 0, 
                            array(
                                array( 'start' => $start_time->copy(),
                                       'end' => $end_time->copy(),
                                       'content' => $event )
                            )
                        );
                    }
                }
                else
                {
                    array_unshift( $this->schedule_slots, 
                        array( 'start' => $start_time->copy(),
                               'end' => $end_time->copy(),
                               'content' => $event 
                        )
                    );
                }
            }
        }
    }

    private function addTask( $task, $ignoreBreaks = false )
    {
        $schedule_slot_id = self::findTimeSlot( $task, $ignoreBreaks );

        // TODO: half scheduled tasks
        if( is_null($schedule_slot_id) )
        {
            if( !$ignoreBreaks )
            {
                return self::addTask($task, true);
            }
            else
            {
                return false;
            }
        }

        $time_slot = $this->schedule_slots[ $schedule_slot_id ];

        $extra_break = array(
            "before" => 0,
            "after" => 0
        );

        if ( !$ignoreBreaks )
        {
            $extra_break = self::getExtraBreakTime($schedule_slot_id, $task);
        }

        $task_slot_start = $time_slot[ 'start' ]->copy()->addMinutes($extra_break['before']);
        $task_slot_end = $task_slot_start->copy()->addMinutes($task->duration);

        array_splice( $this->schedule_slots, $schedule_slot_id, 1, 
            array(
                array( 'start' => $task_slot_start,
                       'end' => $task_slot_end,
                       'content' => $task ),
                array( 'start' => $task_slot_end->copy()->addMinutes( $extra_break['after'] ),
                       'end' => $time_slot[ 'end' ] === null ?
                                null : $time_slot[ 'end' ]->copy(),
                       'content' => $time_slot[ 'content' ] )
            )
        );

        return true;
    }

    // Finds an appropriate time slot for non-fixed time events
    // Input: target task duration
    //        target task due time
    // Output: start time of empty time slot
    //         duration of empty time slot
    private function findTimeSlot( $task, $ignoreBreaks = false )
    {
        if ( !$ignoreBreaks )
        {
            foreach( $this->schedule_slots as $slot_id => $slot )
            {
                if( $slot[ 'start' ]->gte( $task->due ) )
                {
                    break;
                }

                if ( $slot[ 'content' ] != null )
                {
                    continue;
                }

                if( is_null($slot[ 'end' ]) )
                {
                    $slot_duration = null;
                }
                else
                {
                    $slot_duration = $slot[ 'start' ]->diffInMinutes( $slot[ 'end' ] );
                }

                $extra_break = self::getExtraBreakTime($slot_id, $task);

                if ( $slot['start']->copy()->addMinutes($task->duration + $extra_break['before'] + $extra_break['after'])->gt($task->due) )
                {
                    break;
                }

                if( is_null($slot_duration) || $slot_duration >= $task->duration + $extra_break['before'] + $extra_break['after'] )
                {
                    return $slot_id;
                }
            }
        }
        else
        {
            $task->break_before = 0;
            $task->break_after = 0;
            $free_time_in_segment = 0;
            $slot_id = count($this->schedule_slots) - 1;
            while( $slot_id >= 0 && $free_time_in_segment < $task->duration )
            {
                $slot = $this->schedule_slots[$slot_id];
                if( $slot[ 'start' ]->gte( $task->due ) || ($slot['content'] != null && $slot[ 'end' ]->gte( $task->due )) )
                {
                    $slot_id--;
                    continue;
                }

                //Reset if we hit an event
                if ( $slot[ 'content' ] instanceof FixedEvent )
                {
                    $free_time_in_segment = 0;
                }
                //Add full slot duration if empty
                else if ( $slot['content'] == null )
                {
                    if (!is_null($slot['end']) && $slot['end']->lte( $task->due ) )
                    {
                        $free_time_in_segment += $slot[ 'start' ]->diffInMinutes( $slot[ 'end' ] );
                    }
                    else
                    {
                        $free_time_in_segment += $slot[ 'start' ]->diffInMinutes( $task->due );
                    }
                }

                //If not last slot
                if ( $slot_id > 0 )
                {
                    $previous_slot = $this->schedule_slots[$slot_id-1];

                    if ( $slot['content'] == null && $previous_slot['content'] instanceof Task )
                    {
                        $free_time_in_segment += $previous_slot[ 'end' ]->diffInMinutes( $slot[ 'start' ] );
                    }
                    else if ( $slot[ 'content' ] instanceof FixedEvent && $previous_slot['content'] instanceof Task )
                    {
                        $free_time_in_segment += max( $previous_slot['content']->break_after - $slot['content']->break_before, 0 );
                    }
                    else if ( $slot[ 'content' ] instanceof Task )
                    {
                        if ( $previous_slot['content'] == null )
                        {
                            $free_time_in_segment += $slot['content']->break_before;
                        }
                        else if ( $previous_slot['content'] instanceof Task )
                        {
                            $free_time_in_segment += $previous_slot[ 'end' ]->diffInMinutes( $slot[ 'start' ] );
                        }
                        else if ( $previous_slot['content'] instanceof FixedEvent )
                        {
                            $free_time_in_segment += max( $slot['content']->break_before - $previous_slot['content']->break_after, 0 );
                        }
                    }

                }
                //If last slot and currently is a task
                else if ( $slot[ 'content' ] instanceof Task )
                {
                    $free_time_in_segment += $slot['content']->break_before;
                }

                $slot_id--;
            }

            if ( $task->duration > $free_time_in_segment )
            {
                return false;
            }

            $continue_adjustments = true;
            $time_shifted_so_far = 0;
            $start_slot_id = $slot_id;
            while ( $continue_adjustments )
            {
                $slot = $this->schedule_slots[$slot_id];
                if ( $slot_id > 0 )
                {
                    $previous_slot = $this->schedule_slots[$slot_id-1];

                    if ( $slot[ 'content' ] instanceof FixedEvent )
                    {
                        if ($slot_id != $start_slot_id)
                        {
                            if ( $previous_slot['content'] instanceof Task )
                            {
                                $previous_slot['content']->break_after = 0;
                            }
                            $empty_slot_end_time = $slot['start']->copy()->subMinutes($slot['comtent']->break_before);
                            array_splice($this->schedule_slots, $slot_id-1, 0,
                                array(
                                    array( 'start' => $empty_slot_end_time->copy()->subMinutes($time_shifted_so_far),
                                           'end' => $empty_slot_end_time,
                                           'content' => null )
                                )
                            );
                            break;
                        }
                        else
                        {
                            $slot_id++;
                        }
                    }
                    else if ( $slot['content'] == null )
                    {
                        $end_time = $task->due;
                        if ( !is_null($slot['end']) && $slot['end']->lte( $task->due ) )
                        {
                            $end_time = $slot['end'];
                        }

                        if ( $previous_slot['content'] instanceof Task )
                        {
                            $previous_slot['content']->break_after = 0;
                        }

                        if ( !is_null($slot['end']) && $slot['end']->lte( $task->due ) )
                        {
                            array_splice($this->schedule_slots, $slot_id, 1,
                                array(
                                    array( 'start' => $slot['start']->copy()->subMinutes($time_shifted_so_far),
                                           'end' => $task->due->copy(),
                                           'content' => null )
                                )
                            );
                        }
                        else
                        {
                            array_splice($this->schedule_slots, $slot_id, 1,
                                array(
                                    array( 'start' => $slot['start']->copy()->subMinutes($time_shifted_so_far),
                                           'end' => $task->due->copy(),
                                           'content' => null ),
                                    array( 'start' => $task->due->copy() ,
                                           'end' => $slot[ 'end' ] === null ?
                                                    null : $slot[ 'end' ]->copy(),
                                           'content' => null )
                                )
                            );
                        }
                        break;
                    }
                    else if ( $slot[ 'content' ] instanceof Task )
                    {
                        if ( $previous_slot['content'] instanceof Task )
                        {
                            $time_shifted_so_far += $previous_slot[ 'end' ]->diffInMinutes( $slot[ 'start' ] );
                            $previous_slot['content']->break_after = 0;
                            $slot['content']->break_before = 0;
                            $slot['start']->subMinutes($time_shifted_so_far);
                            $slot['end']->subMinutes($time_shifted_so_far);
                        }
                        else if ( $previous_slot['content'] instanceof FixedEvent )
                        {
                            $time_shifted_so_far += max( $slot['content']->break_before - $previous_slot['content']->break_after, 0 );
                            $slot['content']->break_before = max( $slot['content']->break_before, $previous_slot['content']->break_after );
                            $slot['start']->subMinutes($time_shifted_so_far);
                            $slot['end']->subMinutes($time_shifted_so_far);
                        }
                        $slot_id++;
                    }

                }
                //If first slot in array and currently is a task
                else if ( $slot[ 'content' ] instanceof Task )
                {
                    $time_shifted_so_far += $slot['content']->break_before;
                    $slot['content']->break_before = 0;
                    $slot['start']->subMinutes($time_shifted_so_far);
                    $slot['end']->subMinutes($time_shifted_so_far);
                    $slot_id++;
                }
                //If first slot in array and currently is a empty slot
                else if ( $slot[ 'content' ] == null )
                {
                    //Remove item completely
                    $time_shifted_so_far += $slot[ 'start' ]->diffInMinutes( $slot[ 'end' ] );
                    array_shift($this->schedule_slots);
                }
            }

            return self::findTimeSlot($task);

        }

        return null;
    }

    private function getExtraBreakTime($slot_id, $item)
    {
        $extra_break = array(
            'before' => 0, 
            'after' => 0
        );

        $time_slot = $this->schedule_slots[$slot_id];
        //Check break_before buffer
        $slot_before_id = $slot_id - 1;
        if (array_key_exists($slot_before_id, $this->schedule_slots))
        {
            $slot_before = $this->schedule_slots[$slot_before_id];
            $break_before_needed = max($item->break_before, is_null($slot_before['content']) ? 0 : $slot_before['content']->break_after);
            if ($slot_before['end']->diffInMinutes( $time_slot[ 'start' ] ) < $break_before_needed )
            {
                $extra_break['before'] = $break_before_needed - $slot_before['end']->diffInMinutes( $time_slot[ 'start' ] );
            }
        }

        //Check break_after buffer
        $slot_after_id = $slot_id + 1;
        if (array_key_exists($slot_after_id, $this->schedule_slots))
        {
            $slot_after = $this->schedule_slots[$slot_after_id];
            $break_after_needed = max($item->break_after, is_null($slot_after['content']) ? 0 : $slot_after['content']->break_before);
            if (!is_null($slot_after['end']) && $slot_after['end']->diffInMinutes( $time_slot[ 'start' ] ) < $break_after_needed )
            {
                $extra_break['after'] = $break_after_needed - $slot_after['end']->diffInMinutes( $time_slot[ 'start' ] );
            }
        }

        return $extra_break;
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
