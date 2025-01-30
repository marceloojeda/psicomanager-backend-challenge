<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Middleware\JwtMiddleware;

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


$router->group([
        'prefix' => 'api'
    ], function ($router) {

        $router->post('register', 'AuthController@register');

        $router->group([

            'middleware' => [JwtMiddleware::class]

        ], function ($router) {
            $router->post('login', 'AuthController@login');
            $router->post('logout', 'AuthController@logout');
            $router->post('profile', 'AuthController@getUser');

            $router->group(['prefix' => 'tasks'], function () use ($router) {
                $router->get('/', 'TaskController@index');
                $router->get('/{taskId}', 'TaskController@get');
            });

            $router->group(['prefix' => 'users'], function () use ($router) {
                $router->get('/', 'UserController@index');
                $router->get('/{userId}', 'UserController@get');
                $router->post('/', 'UserController@store');
                $router->delete('/{userId}', 'UserController@delete');
            });

            $router->get('/logs', 'LogController@index');
        });
});
