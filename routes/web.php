<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// $router->get('/', function () use ($router) {
//     return $router->app->version();
// });

$router->group(['prefix' => 'tasks'], function () use ($router) {

    $router->get('/', 'TaskController@index');
});

$router->group(['prefix' => 'users'], function () use ($router) {

    $router->get('/', 'UserController@index');
    $router->get('/{userId}', 'UserController@get');
    $router->post('/', 'UserController@store');
    $router->post('/', 'UserController@store');
    $router->delete('/{userId}', 'UserController@delete');
});