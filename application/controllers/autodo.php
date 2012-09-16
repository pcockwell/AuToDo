<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Autodo extends CI_Controller {
	private $now;

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

		$this->now = strtotime("2012-09-16 8:00:00");
		$today = date("Y-m-d H:i:s", $this->now);

		$utilization = 0;
		$task_list = array();

		$tasks = $this->TaskModel->get_all_tasks_by_user_id(1, 0);
		$fixed_events = $this->FixedEventModel->get_all_fixed_events_by_user_date(1, $today);

		if ( $fixed_events == false ){
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
			$task_list[$event->start_time] = $task_obj;
		}

		if ( $start_time < 1440 ){
			$free_time_segments[] = array($start_time, 1440);
		}

		$unscheduled_tasks = $tasks;

		$task_list = self::make_schedule($task_list, $unscheduled_tasks, $free_time_segments);

		$data['task_list'] = $task_list;

		$this->load->view('home', $data);
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

					$task_obj = new stdClass();
					$task_obj->name = $cur_task->name;
					$task_obj->priority = $cur_task->priority;
					$task_obj->timeslot = convert_timeslot_to_str( $seg_start, $seg_start + $cur_task->duration_remaining );
					$task_list[$seg_start] = $task_obj;

					$scheduled_tasks[$seg_start] = $cur_task;

					$segment_time_left -= $cur_task->duration_remaining;
					$seg_start += $cur_task->duration_remaining;

					$unscheduled_tasks = array_slice($unscheduled_tasks, 1);
				}else{
					$cur_task->duration_remaining -= $segment_time_left;

					$unscheduled_tasks[0] = $cur_task;

					$task_obj = new stdClass();
					$task_obj->name = $cur_task->name;
					$task_obj->priority = $cur_task->priority;
					$task_obj->timeslot = convert_timeslot_to_str( $seg_start, $seg_end );
					$task_list[$seg_start] = $task_obj;

					$segment_time_left = 0;
				}

				$task_segment_finished = ($segment_time_left > 0) ? $seg_start : $seg_end;

				if ( convert_minute_value_to_time( $task_segment_finished, date("y-m-d", $this->now) ) > strtotime($cur_task->due) ){

					$cur_lowest_priority_task = $cur_task;

					foreach ( $scheduled_tasks as $i => $task ){
						if ( $task->priority < $cur_task->priority && $task->priority < $cur_lowest_priority_task->priority ){
							$cur_lowest_priority_task = $task;
						}
						unset($task_list[$i]);
					}

					printr("SCHEDULING PROBLEM - DROPPING FOLLOWING TASK:");
					printr($cur_lowest_priority_task);

					foreach ( $scheduled_tasks as $task ){
						if ( $task != $cur_lowest_priority_task ){
							unset($task->duration_remaining);
							array_unshift($unscheduled_tasks, $task);
						}
					}
					
					return self::make_schedule($task_list, $unscheduled_tasks, $free_time_segments);

				}
			}
		}

		ksort($task_list);

		return $task_list;

	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */