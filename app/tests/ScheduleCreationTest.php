<?php

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
                        "name" : "name1",
                        "due" : 1000,
                        "duration" : 40,
                        "priority" : 1
                    },
                    {
                        "name" : "name2",
                        "due" : 1000,
                        "duration" : 60,
                        "priority" : 0
                    },
                    {
                        "name" : "name3",
                        "due" : 1000,
                        "duration" : 30,
                        "priority" : 3
                    },
                    {
                        "name" : "name4",
                        "due" : 1000,
                        "duration" : 30,
                        "priority" : 1
                    }
                ],
                "prefs" : {
                    "start" : "10",
                    "break" : "100"
                }
            }
        ', true );

        $max_priority = $this->call('GET', 'api/max-priority');

        $tasks = array();
        foreach( $data[ "tasks" ] as $task ) {
            $task_obj = new stdClass();
            $task_obj->name = $task[ "name" ];
            $task_obj->duration = $task[ "duration" ];
            if( isset( $task[ "start" ] ) ) {
                $task_obj->start = $task[ "start" ];
                $task_obj->end = $task[ "end" ];
                $task_obj->priority = $max_priority+1;
                $task_obj->due = $task[ "end" ];
            }
            else {
                $task_obj->priority = $task[ "priority" ];
                $task_obj->due = $task[ "due" ];
            }
            $tasks[ $task[ "name" ] ] = $task_obj;
        }
        $prefs = $data[ "prefs" ];

        // Create an ApiController
        $api_controller = App::make('ApiController');

        $create_schedule_method = new ReflectionMethod(
            'ApiController', 'createSchedule'
        );
        $create_schedule_method->setAccessible(TRUE);
        
        //TODO: invoke/test this method without gross reflection
        $schedule = $create_schedule_method->invoke($api_controller, $tasks, $prefs);

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        $correct_tasks = 0;
        foreach( $schedule as $start => $the_task ) {
            if( $start == 10 ) {
                $this->assertTrue($the_task->name == "name3");
                $correct_tasks++;
            } else if( $start == 140 ) {
                $this->assertTrue($the_task->name == "name1");
                $correct_tasks++;
            } else if( $start == 280 ) {
                $this->assertTrue($the_task->name == "name4");
                $correct_tasks++;
            } else if( $start == 410 ) {
                $this->assertTrue($the_task->name == "name2");
                $correct_tasks++;
            }
        }

        // Ensure that we got 4 correct tasks.
        $this->assertTrue($correct_tasks == 4);
    }


    public function testApi_PostScheduleSuccess()
    {
        $content = '
            {
                "tasks":[
                    {
                        "name" : "name1",
                        "due" : 1000,
                        "duration" : 40,
                        "priority" : 1
                    },
                    {
                        "name" : "name2",
                        "due" : 1000,
                        "duration" : 60,
                        "priority" : 0
                    },
                    {
                        "name" : "name3",
                        "due" : 1000,
                        "duration" : 30,
                        "priority" : 3
                    },
                    {
                        "name" : "name4",
                        "due" : 1000,
                        "duration" : 30,
                        "priority" : 1
                    }
                ],

                "prefs": {
                    "start" : "10",
                    "break" : "100"
                }
            }
        ';

        $response = $this->call('POST', 'api/schedule', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent());

        // Make sure that the tasks are in the correct scheduled order
        // with the correct start times.
        $correct_tasks = 0;
        foreach( $json_response as $start => $the_task ) {
            if( $start == 10 ) {
                $this->assertTrue($the_task->name == "name3");
                $correct_tasks++;
            } else if( $start == 140 ) {
                $this->assertTrue($the_task->name == "name1");
                $correct_tasks++;
            } else if( $start == 280 ) {
                $this->assertTrue($the_task->name == "name4");
                $correct_tasks++;
            } else if( $start == 410 ) {
                $this->assertTrue($the_task->name == "name2");
                $correct_tasks++;
            }
        }

        // Ensure that we got 4 correct tasks.
        $this->assertTrue($correct_tasks == 4);
    }

    public function testApi_PostScheduleUnsupportedContentType() {

        $this->call('POST', 'api/schedule');
        $this->assertResponseStatus(400);
    }
}
