<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * AuToDo Specific constants file
 */
$config = array();

if ( ENVIRONMENT == 'production' ){
	define('DB_HOST','localhost');
	define('DB_USER','root');
	define('DB_PASSWORD','');
}else if ( ENVIRONMENT == 'development' ){
	define('DB_HOST','localhost');
	define('DB_USER','root');
	define('DB_PASSWORD','Dont4Get');
}