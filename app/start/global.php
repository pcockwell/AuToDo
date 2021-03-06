<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
    app_path().'/models',
	app_path().'/database/seeds',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a rotating log file setup which creates a new file each day.
|
*/

$logFile = 'log-'.php_sapi_name().'.txt';

Log::useDailyFiles(storage_path().'/logs/'.$logFile);

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

use Illuminate\Database\Eloquent\ModelNotFoundException;
App::error(function(ModelNotFoundException $e)
{
    return Response::make('Not Found', 404);
});

App::error(function(Exception $exception, $code)
{
	Log::error($exception);
});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenace mode is in effect for this application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';

/*
|--------------------------------------------------------------------------
| Require Google API Files
|--------------------------------------------------------------------------
|
| Require any necessary Google API files
|
*/

set_include_path( get_include_path() . PATH_SEPARATOR . base_path() . '/vendor/google-api-client/src' );
require_once 'Google/Client.php';
require_once 'Google/Service/Calendar.php';

/*
|--------------------------------------------------------------------------
| Extend Validator Functionality
|--------------------------------------------------------------------------
|
| Add some additional validator functions for input data
|
*/

Validator::extend('validate_recurrence', function($attribute, $value, $parameters)
{
    if (!is_string($value)) return false;

    if (null === $decoded_value = json_decode($value)) return false;

    return is_array($decoded_value);
});

Validator::extend('alpha_space', function($attr, $value) {
    return preg_match('/^([a-z\x20])+$/i', $value);
});

Validator::extend('alpha_num_space', function($attribute, $value)
{
    return preg_match('/^([-a-z0-9\x20])+$/i', $value);
});

Validator::extend('boolean', function($attribute, $value)
{
    return $value === true || $value === false;
});
