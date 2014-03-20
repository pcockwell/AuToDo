<?php
use Carbon\Carbon;
use Autodo\Support\InputConverter;

class ScheduleCreationTest extends TestCase {

    protected   $useDatabase    = true;
    private     $testUserId     = 1;

    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::createFromDate(2010, 1, 1));
    }

    public function tearDown()
    {
        Carbon::setTestNow();
    }


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
                  "name" : "name1",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 40,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name2",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 60,
                  "priority" : 0,
                  "break_before" : 20,
                  "break_after" : 20
                },
                {
                  "name" : "name3",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 30,
                  "priority" : 3,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name4",
                  "due" : "2013-11-04 12:00:00",
                  "duration" : 30,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                }
              ],
              "fixedevents" : [
                {
                  "name" : "Sleep",
                  "start_time" : 0,
                  "end_time" : 600,
                  "start_date" : "2012-09-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,1,2,3,4,5,6]",
                  "break_before" : 20,
                  "break_after" : 30
                },
                {
                  "name" : "Class",
                  "start_time" : 690,
                  "end_time" : 810,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[1,3,5]",
                  "break_before" : 30,
                  "break_after" : 30
                },
                {
                  "name" : "Workout",
                  "start_time" : 900,
                  "end_time" : 1020,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,2,4,6]",
                  "break_before" : 15,
                  "break_after" : 15
                }
              ],
              "schedule_start" : "2013-07-05 00:00:00"
            }
        ', true );

        //Note that 2013-07-05 is a Friday, so only Sleep and Class apply as relevant events
    
        $new_input = InputConverter::convertToObject($data);

        $tasks = array();
        $fixed_events = array();
        foreach( $new_input[ "Task" ] as $task ) {
            $tasks[ $task->name ] = $task;
        }
        foreach( $new_input[ "FixedEvent" ] as $fixed ) {
            $fixed_events[ $fixed->name ] = $fixed;
        }
        $prefs = new Preference;

        // Create an ApiController
        $api_controller = App::make('ApiController');

        $create_schedule_method = new ReflectionMethod(
            'ApiController', 'createSchedule'
        );
        $create_schedule_method->setAccessible(TRUE);
        
        //TODO: invoke/test this method without gross reflection
        $schedule = $create_schedule_method->invoke($api_controller, $tasks, $fixed_events, $prefs, $new_input['schedule_start']);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.

        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $schedule as $key => $timeslot ) {

            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "Class");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 14:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 15:20:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 15:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 16:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            }
        }

        print "Number of correct tasks is ".$correct_tasks;
        // Ensure that we got 4 correct tasks.
        $this->assertTrue($correct_tasks == 6);
    }


    public function testApi_PostScheduleJSONSuccess()
    {
        $content = '
            {
              "tasks" : [
                {
                  "name" : "name1",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 40,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name2",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 60,
                  "priority" : 0,
                  "break_before" : 20,
                  "break_after" : 20
                },
                {
                  "name" : "name3",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 30,
                  "priority" : 3,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name4",
                  "due" : "2013-11-04 12:00:00",
                  "duration" : 30,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                }
              ],
              "fixedevents" : [
                {
                  "name" : "Sleep",
                  "start_time" : 0,
                  "end_time" : 600,
                  "start_date" : "2012-09-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,1,2,3,4,5,6]",
                  "break_before" : 20,
                  "break_after" : 30
                },
                {
                  "name" : "Class",
                  "start_time" : 690,
                  "end_time" : 810,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[1,3,5]",
                  "break_before" : 30,
                  "break_after" : 30
                },
                {
                  "name" : "Workout",
                  "start_time" : 900,
                  "end_time" : 1020,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,2,4,6]",
                  "break_before" : 15,
                  "break_after" : 15
                }
              ],
              "schedule_start" : "2013-07-05 00:00:00"
            }
        ';

        //Note that 2013-07-05 is a Friday, so only Sleep and Class apply as relevant events

        Route::enableFilters();
        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        $correct_tasks = 0;
        foreach( $json_response as $key => $timeslot ) {

            $timeslot["start"] = new Carbon($timeslot["start"]["date"], $timeslot["start"]["timezone"]);
            $timeslot["end"] = new Carbon($timeslot["end"]["date"], $timeslot["end"]["timezone"]);

            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "Class");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 14:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 15:20:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 15:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 16:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            }
        }

        //print "Number of correct tasks is ".$correct_tasks;
        // Ensure that we got 8 correct tasks.
        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        $this->assertTrue($correct_tasks == 6);

        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/xml'),
            $content);

        $xml_response = new SimpleXMLElement($response->getContent(), true);
        $xml_response = json_decode(json_encode($xml_response));

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $xml_response->document as $key => $timeslot ) {

            $timeslot->start = new Carbon(trim($timeslot->start->date), trim($timeslot->start->timezone));
            $timeslot->end = new Carbon(trim($timeslot->end->date), trim($timeslot->end->timezone));

            if( $key == 0 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Class");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 14:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 14:30:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name4");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 15:20:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name1");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 15:40:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 16:40:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name2");
                $correct_tasks++;
            }
        }

        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        // Ensure that we got 8 correct tasks.
        $this->assertTrue($correct_tasks == 6);
    }

    public function testApi_PostScheduleJSONDependenciesSuccess()
    {
        print "\nMY TEST START\n";
        $content = '
            {
              "dependencygraph" : {
                "name4" : ["name1"]
              },
              "tasks" : [
                {
                  "name" : "name1",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 40,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name2",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 60,
                  "priority" : 0,
                  "break_before" : 20,
                  "break_after" : 20
                },
                {
                  "name" : "name3",
                  "due" : "2013-12-04 12:00:00",
                  "duration" : 30,
                  "priority" : 3,
                  "break_before" : 10,
                  "break_after" : 10
                },
                {
                  "name" : "name4",
                  "due" : "2013-11-04 12:00:00",
                  "duration" : 30,
                  "priority" : 1,
                  "break_before" : 10,
                  "break_after" : 10
                }
              ],
              "fixedevents" : [
                {
                  "name" : "Sleep",
                  "start_time" : 0,
                  "end_time" : 600,
                  "start_date" : "2012-09-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,1,2,3,4,5,6]",
                  "break_before" : 20,
                  "break_after" : 30
                },
                {
                  "name" : "Class",
                  "start_time" : 690,
                  "end_time" : 810,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[1,3,5]",
                  "break_before" : 30,
                  "break_after" : 30
                },
                {
                  "name" : "Workout",
                  "start_time" : 900,
                  "end_time" : 1020,
                  "start_date" : "2013-05-01 00:00:00",
                  "end_date" : "2013-09-01 00:00:00",
                  "recurrences" : "[0,2,4,6]",
                  "break_before" : 15,
                  "break_after" : 15
                }
              ],
              "schedule_start" : "2013-07-05 00:00:00"
            }
        ';

        //Note that 2013-07-05 is a Friday, so only Sleep and Class apply as relevant events

        Route::enableFilters();
        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        $correct_tasks = 0;
        foreach( $json_response as $key => $timeslot ) {

            $timeslot["start"] = new Carbon($timeslot["start"]["date"], $timeslot["start"]["timezone"]);
            $timeslot["end"] = new Carbon($timeslot["end"]["date"], $timeslot["end"]["timezone"]);

            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "Class");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 14:50:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 15:20:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-05 15:40:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-05 16:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            }
        }

        //print "Number of correct tasks is ".$correct_tasks;
        // Ensure that we got 8 correct tasks.
        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        $this->assertTrue($correct_tasks == 6);

        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/xml'),
            $content);

        $xml_response = new SimpleXMLElement($response->getContent(), true);
        $xml_response = json_decode(json_encode($xml_response));

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $xml_response->document as $key => $timeslot ) {

            $timeslot->start = new Carbon(trim($timeslot->start->date), trim($timeslot->start->timezone));
            $timeslot->end = new Carbon(trim($timeslot->end->date), trim($timeslot->end->timezone));

            if( $key == 0 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 00:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 10:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 10:30:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 11:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 11:30:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 13:30:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Class");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 14:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 14:30:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name4");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 14:40:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 15:20:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name1");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-05 15:40:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-05 16:40:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name2");
                $correct_tasks++;
            }
        }

        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        // Ensure that we got 8 correct tasks.
        $this->assertTrue($correct_tasks == 6);
        print "\nMY TEST END\n";
    }

    public function testApi_PostScheduleXMLSuccess()
    {
        $content = '<?xml version="1.0" encoding="UTF-8" ?>
<document>
    <tasks>
        <name>name1</name>
        <due>2013-12-04 12:00:00</due>
        <duration>40</duration>
        <priority>1</priority>
        <break_before>10</break_before>
        <break_after>10</break_after>
    </tasks>
    <tasks>
        <name>name2</name>
        <due>2013-12-04 12:00:00</due>
        <duration>60</duration>
        <priority>0</priority>
        <break_before>20</break_before>
        <break_after>20</break_after>
    </tasks>
    <tasks>
        <name>name3</name>
        <due>2013-12-04 12:00:00</due>
        <duration>30</duration>
        <priority>3</priority>
        <break_before>10</break_before>
        <break_after>10</break_after>
    </tasks>
    <tasks>
        <name>name4</name>
        <due>2013-11-04 12:00:00</due>
        <duration>30</duration>
        <priority>1</priority>
        <break_before>10</break_before>
        <break_after>10</break_after>
    </tasks>
    <fixedevents>
        <name>Sleep</name>
        <start_time>0</start_time>
        <end_time>600</end_time>
        <start_date>2012-09-01 00:00:00</start_date>
        <end_date>2013-09-01 00:00:00</end_date>
        <recurrences>[0,1,2,3,4,5,6]</recurrences>
        <break_before>20</break_before>
        <break_after>30</break_after>
    </fixedevents>
    <fixedevents>
        <name>Class</name>
        <start_time>690</start_time>
        <end_time>810</end_time>
        <start_date>2013-05-01 00:00:00</start_date>
        <end_date>2013-09-01 00:00:00</end_date>
        <recurrences>[1,3,5]</recurrences>
        <break_before>30</break_before>
        <break_after>30</break_after>
    </fixedevents>
    <fixedevents>
        <name>Workout</name>
        <start_time>900</start_time>
        <end_time>1020</end_time>
        <start_date>2013-05-01 00:00:00</start_date>
        <end_date>2013-09-01 00:00:00</end_date>
        <recurrences>[0,2,4,6]</recurrences>
        <break_before>15</break_before>
        <break_after>15</break_after>
    </fixedevents>
    <schedule_start>2013-07-06 00:00:00</schedule_start>
</document>  
';
        //Note that 2013-07-06 is a Saturday, so only Sleep and Workout apply as relevant events

        Route::enableFilters();
        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/xml'),
            $content);

        $xml_response = new SimpleXMLElement($response->getContent(), true);
        $xml_response = json_decode(json_encode($xml_response));

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $xml_response->document as $key => $timeslot ) {

            $timeslot->start = new Carbon(trim($timeslot->start->date), trim($timeslot->start->timezone));
            $timeslot->end = new Carbon(trim($timeslot->end->date), trim($timeslot->end->timezone));

            if( $key == 0 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 00:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 10:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 10:30:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 11:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 11:10:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 11:40:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name4");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 11:50:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 12:30:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name1");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 12:50:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 13:50:00')));
                $this->assertTrue(trim($timeslot->task->name) == "name2");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot->start->eq(new Carbon('2013-07-06 15:00:00')));
                $this->assertTrue($timeslot->end->eq(new Carbon('2013-07-06 17:00:00')));
                $this->assertTrue(trim($timeslot->task->name) == "Workout");
                $correct_tasks++;
            }
        }

        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        // Ensure that we got 8 correct tasks.
        $this->assertTrue($correct_tasks == 6);

        $response = $this->call('POST', '/api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/xml', 'HTTP_ACCEPT' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        //Make sure to fix the key orderings for #5 and #4 when we fix the display ordering
        $correct_tasks = 0;
        foreach( $json_response as $key => $timeslot ) {

            $timeslot["start"] = new Carbon($timeslot["start"]["date"], $timeslot["start"]["timezone"]);
            $timeslot["end"] = new Carbon($timeslot["end"]["date"], $timeslot["end"]["timezone"]);

            if( $key == 0 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 00:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 10:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Sleep");
                $correct_tasks++;
            } else if( $key == 1 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 10:30:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 11:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "name3");
                $correct_tasks++;
            } else if( $key == 2 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 11:10:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 11:40:00')));
                $this->assertTrue($timeslot['task']['name'] == "name4");
                $correct_tasks++;
            } else if( $key == 3 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 11:50:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 12:30:00')));
                $this->assertTrue($timeslot['task']['name'] == "name1");
                $correct_tasks++;
            } else if( $key == 4 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 12:50:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 13:50:00')));
                $this->assertTrue($timeslot['task']['name'] == "name2");
                $correct_tasks++;
            } else if( $key == 5 ) {
                $this->assertTrue($timeslot['start']->eq(new Carbon('2013-07-06 15:00:00')));
                $this->assertTrue($timeslot['end']->eq(new Carbon('2013-07-06 17:00:00')));
                $this->assertTrue($timeslot['task']['name'] == "Workout");
                $correct_tasks++;
            }
        }

        print "\nNumber of correct tasks is ".$correct_tasks."\n";
        // Ensure that we got 8 correct tasks.
        $this->assertTrue($correct_tasks == 6);
    }

    public function testApi_PostScheduleUnsupportedContentType() 
    {
        Route::enableFilters();

        $this->call('POST', '/api/schedule');
        $this->assertResponseStatus(400);
    }
}
