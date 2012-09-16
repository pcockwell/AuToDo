<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Autodo extends CI_Controller {
	private $now;
	private $conflicts;

	public function __construct(){
        parent::__construct();
        $this->load->model('user/UserModel');
        $this->load->model('task/TaskModel');
        $this->load->model('fixed_event/FixedEventModel');
        $this->load->helper('time');
	}

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
        $user = self::get_logged_in_user(false);
        if ( $user !== false ){
        	redirect("autodo/schedule");
        }
		$this->load->view('home');
	}

	public function save_task(){
		if ( $this->input->post() === false ){
			$this->output->set_output('{"result": "false", "error":"No data sent"}');
			return;
		}
        $user = self::get_logged_in_user(false);

        if ( $user === false ){
			$this->output->set_output('{"result": "false", "error":"User not logged in"}');
			return;
		}

		$new_task = $this->TaskModel->create();

		$new_task->due = date("Y-m-d H:i:s", intval($this->input->post('inputDue')));
		$new_task->duration = intval($this->input->post('inputDuration'));
		$new_task->priority = intval($this->input->post('inputPriority'));
		$new_task->name = $this->input->post('inputTaskName');
		$new_task->user_id = $user->id;
		$new_task->complete = 0;

		if ( $this->TaskModel->save($new_task) ){
			$this->output->set_output(json_encode($new_task));
		}else{
			$this->output->set_output('{"result": "false", "error":"Error saving task"}');
		}
		return;
	}

	public function schedule()
	{
        $user = self::get_logged_in_user();

		$this->now = strtotime("2012-09-17 8:00:00");
		$today = date("Y-m-d H:i:s", $this->now);

		$utilization = 0;
		$task_list = array();

		$tasks = $this->TaskModel->get_all_tasks_by_user_id($user->id, 0);
		$fixed_events = $this->FixedEventModel->get_all_fixed_events_by_user_date($user->id, $today);

		if ( $fixed_events === false ){
			printr("Fixed Event Conflict");
		}

		$free_time_segments = array();
		$start_time = 0;

		foreach ( $fixed_events as $event ){
			if ( $event->start_time > $start_time ){
				$free_time_segments[]= array($start_time, $event->start_time);
			}
			$start_time = $event->end_time;

			$task_obj = new stdClass();
			$task_obj->name = $event->name;
			$task_obj->priority = 3;
			$task_obj->timeslot = convert_timeslot_to_str( $event->start_time, $event->end_time );
			$task_obj->recurrences = $event->recurrences;
			$task_list[$event->start_time] = $task_obj;
		}

		if ( $start_time < 1440 ){
			$free_time_segments[] = array($start_time, 1440);
		}

		$unscheduled_tasks = $tasks;

		$task_list = self::make_schedule($task_list, $unscheduled_tasks, $free_time_segments);

		$data['task_list'] = $task_list;
		$data['conflicts'] = $this->conflicts;
		$data['user'] = $user;

		$this->load->view('schedule', $data);
	}

	private function make_schedule($task_list, $unscheduled_tasks, $free_time_segments){

		$scheduled_tasks = array();

		foreach ( $free_time_segments as $segment ){
			$seg_start = $segment[0];
			$seg_end = $segment[1];
			$segment_time_left = $seg_end-$seg_start;
			while ( count($unscheduled_tasks) > 0 && $segment_time_left > 0 ){
				$cur_task = $unscheduled_tasks[0];
				if ( !property_exists($cur_task, "duration_remaining") ){
					$cur_task->duration_remaining = $cur_task->duration;
				}

				if ( $segment_time_left >= $cur_task->duration_remaining ){

					$cur_task->timeslot = convert_timeslot_to_str( $seg_start, $seg_start + $cur_task->duration_remaining );

					$new_task_obj = clone $cur_task;
					unset($new_task_obj->duration_remaining);
					$task_list[$seg_start] = $new_task_obj;

					$segment_time_left -= $cur_task->duration_remaining;
					$seg_start += $cur_task->duration_remaining;

					unset($cur_task->duration_remaining);
					$scheduled_tasks[$seg_start] = $cur_task;

					$unscheduled_tasks = array_slice($unscheduled_tasks, 1);
				}else{
					$cur_task->duration_remaining -= $segment_time_left;

					$unscheduled_tasks[0] = $cur_task;

					$cur_task->timeslot = convert_timeslot_to_str( $seg_start, $seg_end );
					$task_list[$seg_start] = clone $cur_task;

					$segment_time_left = 0;
				}

				$task_segment_finished = ($segment_time_left > 0) ? $seg_start : $seg_end;

				if ( convert_minute_value_to_time( $task_segment_finished, date("y-m-d", $this->now) ) > strtotime($cur_task->due) ){

					$cur_lowest_priority_task = $cur_task;
					foreach ( $scheduled_tasks as $i => $sched_task ){
						if ( $sched_task->priority < $cur_task->priority && $sched_task->priority < $cur_lowest_priority_task->priority ){
							$cur_lowest_priority_task = $sched_task;
						}
						$matching_task_list_items = array();
						foreach ( $task_list as $task_index => $task ){
							if ( property_exists($task, "id") && $task->id == $sched_task->id ){
								$matching_task_list_items[] = $task_index;
							}
						}

						foreach ( $matching_task_list_items as $index ){
							unset( $task_list[$index] );
						}
					}

					if ( !isset ($this->conflicts) ){
						$this->conflicts = array();
					}

					unset($cur_lowest_priority_task->timeslot);
					$this->conflicts[] = $cur_lowest_priority_task; 

					foreach ( $scheduled_tasks as $i => $sched_task ){
						if ( $sched_task->id != $cur_lowest_priority_task->id ){
							array_unshift($unscheduled_tasks, $sched_task);
						}
					}

					return self::make_schedule($task_list, $unscheduled_tasks, $free_time_segments);

				}
			}
		}

		ksort($task_list);

		return array_values($task_list);

	}

	private function get_logged_in_user($redirect = true){

        $client = new apiClient();
        $client->setApplicationName("AuToDo");
        $oauth2 = new apiOauth2Service($client);
        $token = $this->session->userdata('token');
        if ($token){
            $client->setAccessToken($token);
            try{
                $user_info = $oauth2->userinfo->get();
                $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
                $user = $this->UserModel->get_user_by_email($email);
            }catch(apiServiceException $e){
            	if ( $redirect ){
            		redirect('autodo');
            	}else{
            		return false;
            	}
            }
        }else{
        	if ( $redirect ){
        		redirect('autodo');
        	}else{
        		return false;
        	}
        }

        return $user;
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */