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

// Route to view users
//TODO This should not be accessible
Route::get('users', function()
{
    $users = User::all();

    return View::make('users')->with('users', $users);
});

// Controller to handle user accounts.
Route::controller('user', 'UserController');

// Controller for base API function.
Route::controller('api', 'ApiController');

//Must always be the last entry in the file
Route::controller('/', 'ApiController');
