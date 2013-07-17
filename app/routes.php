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
        return "Input provided was not JSON";
    }
});

Route::when('*.json', 'json');

// Route to view users
//TODO This should not be accessible
Route::get('users', function()
{
    $users = User::all();

    return View::make('users')->with('users', $users);
});

Route::group(array('suffix' => array('.json', '.xml')), function()
{
    // Controller to handle user accounts.
    Route::controller('user', 'UserController');

    // Controller for base API function.
    Route::controller('api', 'ApiController');
});

//Must always be the last entry in the file
Route::controller('/', 'ApiController');
