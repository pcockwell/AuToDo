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

use Autodo\Support\InputConverter;

Route::filter('apiInputFilter', function(){
    $new_input = array();
    $errors = array(
        "success" => false,
        "errors" => array()
    );
    if (Input::isJson() || Input::isXml())
    {
        $new_input = InputConverter::convertToObject(Input::all());
        Input::replace($new_input);
    }
    else
    {
        if (Request::getMethod() != 'GET' && Request::getMethod() != 'DELETE')
        {
            // prepare a response, unsupported POST content
            $invalid_text = 'The request could not be fulfilled. Input provided was not an accepted data format.';
            $response = Response::make( $invalid_text, 400 );
            return $response;
        }
    }

    if (count($errors['errors']) > 0)
    {
        $response = Response::make( $errors, 400 );
        return $response;
    }
});

Route::filter('hasEmailInput', function ()
{
    if (!Input::has('email'))
    {
        return Response::make( 'No such function', 404 );
    }
});

Route::get('/api/oauth2callback', 'ApiController@oauth2Callback');

Route::get('password/reset/{token}', array(
    'uses' => 'RemindersController@reset',
    'as' => 'password.reset'
));

Route::post('password/reset/{token}', array(
    'uses' => 'RemindersController@update',
    'as' => 'password.update'
));

Route::group(array('prefix' => 'api', 'before' => 'apiInputFilter'), 
    function()
    {
        Route::get('password/reset', array(
            'uses' => 'RemindersController@request'
        ));

        Route::get('user/schedule', array('before' => 'auth.basic.once', 
            'uses' => 'ApiController@userSchedule'));
        
        Route::get('user/find', array('before' => 'auth.basic.once',
            'uses' => 'UserController@findByEmail'));

        // Controller to handle user accounts.
        Route::resource('user', 'UserController', 
            array('except' => array('show', 'index', 'create', 'edit')));

        // Controller to handle user accounts.
        Route::resource('user.task', 'TaskController', 
            array('before' => 'auth.basic.once', 'except' => array('create', 'edit')));
        // Controller to handle user accounts.
        Route::resource('user.fixedevent', 'FixedEventController', 
            array('before' => 'auth.basic.once', 'except' => array('create', 'edit')));
        // Controller to handle user accounts.
        Route::resource('preferences', 'PreferencesController',
            array('before' => 'auth.basic.once', 'except' => array('index', 'create', 'edit')));

        //Must always be the last entry in the file
        Route::controller('/', 'ApiController');
    }
);

Route::get('/', function()
{
    return Redirect::to('http://pcockwell.github.io/AuToDo/');
});

/*
$routes = Route::getRoutes();
foreach ($routes as $name => $r)
{
    echo $name . ": " . $r->getPath() . "<br/>";
    //echo print_r($r->getParameters(), true) . "<br/>";
}
die();
*/
