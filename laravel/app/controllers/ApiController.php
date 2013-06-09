<?php

class ApiController extends BaseController {

	public function getIndex()
	{
		return View::make('hello');
	}

	public function getHello($name){
		return "Hello $name";
	}

	public function get_name($name){
		return "Hello $name";
	}

}