<?php

class ApiController extends BaseController {

	public function getIndex()
	{
		return View::make('hello');
	}

	public function getHello($name){
		return "Hello $name";
	}

}