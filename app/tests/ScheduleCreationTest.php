<?php
use Carbon\Carbon;

class ScheduleCreationTest extends TestCase {

    /**
     * Perform a basic scheduling test.
     *
     * Uses a JSON string to test schedule creation.
     *
     * @return void
     */
    public function testApi_CreateSchedule()
    {
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

        // Create an ApiController
        $api_controller = App::make('ApiController');

        $create_schedule_method = new ReflectionMethod(
            'ApiController', 'createSchedule'
        );
        $create_schedule_method->setAccessible(TRUE);
        
        //TODO: invoke/test this method without gross reflection
        $schedule = $create_schedule_method->invoke($api_controller, $tasks, $fixed_events, $prefs);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.

        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $schedule as $key => $timeslot ) {
            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 07:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            }else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "Class");
                $correct_tasks++;
            }else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            }else if( $key == 6 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 13:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            }else if( $key == 7 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 15:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 17:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Workout");
                $correct_tasks++;
            }
        }

        print "Number of correct tasks is ".$correct_tasks;
        // Ensure that we got 4 correct tasks.
        $this->assertTrue($correct_tasks == 8);
    }


    public function testApi_PostScheduleSuccess()
    {
        $content = '
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
        ';

        $response = $this->call('POST', 'api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $json_response as $key => $timeslot ) {
            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 07:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            }else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "Class");
                $correct_tasks++;
            }else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            }else if( $key == 6 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 13:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            }else if( $key == 7 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 15:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 17:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Workout");
                $correct_tasks++;
            }
        }

        print "Number of correct tasks is ".$correct_tasks;
        // Ensure that we got 8 correct tasks.
        $this->assertTrue($correct_tasks == 8);
    }

    public function testApi_PostScheduleUnsupportedContentType() {

        $this->call('POST', 'api/schedule');
        $this->assertResponseStatus(400);
    }
}
