<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::filter('json', function(){
    $new_input = array(
        'errors' => array()
    );
    if (Input::isJson())
    {
        foreach(Input::all() as $key => $content)
        {
            $class_type = str_singular(studly_case($key));
            if (class_exists($class_type))
            {
                $class = new ReflectionClass($class_type);
                $class_name = $class->getShortName();
                if (is_array($content))
                {
                    foreach($content as $content_item)
                    {
                        try
                        {
                            $new_input[$class_name][] = $class->newInstance($content_item);
                        }
                        catch (ValidationException $v)
                        {
                            $new_input['errors'] = array_merge($new_input['errors'], $v->get());
                        }
                    }
                }
                else
                {
                    try
                    {
                        $new_input[$class_name][] = $class->newInstance($content);
                    }
                    catch (ValidationException $v)
                    {
                        $new_input['errors'] = array_merge($new_input['errors'], $v->get());
                    }
                }
            }
            else
            {
                $new_input[$key] = $content;
            }
        }
        Input::replace($new_input);
    }
    else
    {
        if (Request::getMethod() != 'GET')
        {
            // prepare a response, unsupported POST content
            $invalid_text = 'The request could not be fulfilled. Input provided was not JSON.';
            $response = Response::make( $invalid_text, 400 );
            return $response;
        }
    }
});

Route::when('*.json', 'json');

Route::group(array('suffix' => array('.json', '.xml'), 'prefix' => 'api'), function()
{
    Route::get('user/{user_id}/schedule', 'ApiController@userSchedule')->where('user_id', '[0-9]+');
    // Controller to handle user accounts.
    Route::resource('user', 'UserController', array('except' => array('index', 'create', 'edit')));
    // Controller to handle user accounts.
    Route::resource('user.task', 'TaskController', array('except' => array('create', 'edit')));
    // Controller to handle user accounts.
    Route::resource('user.fixedevent', 'FixedEventController', array('except' => array('create', 'edit')));
    // Controller to handle user accounts.
    Route::resource('user.preferences', 'PreferencesController', array('except' => array('show', 'create', 'edit')));

    //Must always be the last entry in the file
    Route::controller('/', 'ApiController');
});

/*
$routes = Route::getRoutes();
foreach ($routes as $name => $r)
{
    echo $name . ": " . $r->getPath() . "<br/>";
    //echo print_r($r->getParameters(), true) . "<br/>";
}