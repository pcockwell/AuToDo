<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Autodo extends CI_Controller {

	public function __construct(){
        parent::__construct();
        $this->load->model('user/UserModel');
        $this->load->model('task/TaskModel');
        $this->load->model('fixed_event/FixedEventModel');
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

		$now = strtotime("2012-09-17 8:00:00");
		$today = date("Y-m-d H:i:s", $now);

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
			$task_obj->priority = -1;
			$task_obj->timeslot = self::convert_timeslot_to_str( $event->start_time, $event->end_time );
			$task_list[$event->start_time] = $task_obj;
		}

		if ( $start_time < 1440 ){
			$free_time_segments[] = array($start_time, 1440);
		}

		$unscheduled_tasks = $tasks;

		foreach ( $free_time_segments as $segment ){
			$segment_time_left = $segment[1]-$segment[0];
			while ( count($unscheduled_tasks) > 0 && $segment_time_left > 0 ){
				$cur_task = $unscheduled_tasks[0];

				if ( $segment_time_left >= $cur_task->duration ){

					$task_obj = new stdClass();
					$task_obj->name = $cur_task->name;
					$task_obj->priority = $cur_task->priority;
					$task_obj->timeslot = self::convert_timeslot_to_str( $segment[0], $segment[0] + $cur_task->duration );
					$task_list[$segment[0]] = $task_obj;

					$segment_time_left -= $cur_task->duration;
					$segment[0] += $cur_task->duration;

					$unscheduled_tasks = array_slice($unscheduled_tasks, 1);
				}else{
					$cur_task->duration -= $segment_time_left;

					$unscheduled_tasks[0] = $cur_task;

					$task_obj = new stdClass();
					$task_obj->name = $cur_task->name;
					$task_obj->priority = $cur_task->priority;
					$task_obj->timeslot = self::convert_timeslot_to_str( $segment[0], $segment[1] );
					$task_list[$segment[0]] = $task_obj;

					$segment_time_left = 0;
				}
			}
		}

		ksort($task_list);

		$data['task_list'] = $task_list;

		$this->load->view('home', $data);
	}

	private function convert_timeslot_to_str($start_time, $end_time){
		$start_hr = intval($start_time / 60);
		$start_min = $start_time % 60;

		$start_str = sprintf("%02d", $start_hr) . ":" . sprintf("%02d", $start_min);

		$end_hr = intval($end_time / 60);
		$end_min = $end_time % 60;

		$end_str = sprintf("%02d", $end_hr) . ":" . sprintf("%02d", $end_min);

		return $start_str . "-" . $end_str;
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */