<?php

if (!defined('time_helper')) {
    define('time_helper', true);

	function convert_timeslot_to_str($start_time, $end_time){
		$start_hr = intval($start_time / 60);
		$start_min = $start_time % 60;

		$start_str = sprintf("%02d", $start_hr) . ":" . sprintf("%02d", $start_min);

		$end_hr = intval($end_time / 60);
		$end_min = $end_time % 60;

		$end_str = sprintf("%02d", $end_hr) . ":" . sprintf("%02d", $end_min);

		return $start_str . "-" . $end_str;
	}

	function convert_minute_value_to_time($minute_val, $date_str){
		$hr = intval($minute_val / 60);
		$min = $minute_val % 60;

		$time_str = sprintf("%02d", $hr) . ":" . sprintf("%02d", $min) . ":00";

		return strtotime($date_str . " " . $time_str);
	}

}
