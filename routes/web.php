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

$router->get('/', function () {
    return response()->file(__DIR__ . '/../public/index.html');
});

// Rotas para documentação
$router->get('/docs', function () {
    return response()->file(__DIR__ . '/../public/api-documentation.html');
});

$router->get('/api-docs', function () {
    return response()->file(__DIR__ . '/../public/api-docs-simple.json', 200, [
        'Content-Type' => 'application/json'
    ]);
});

$router->get('/api-docs-simple.json', function () {
    return response()->file(__DIR__ . '/../public/api-docs-simple.json', 200, [
        'Content-Type' => 'application/json'
    ]);
});

// =====================================
// Rotas públicas de autenticação
// =====================================
$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/refresh', 'AuthController@refresh');
});

// =====================================
// Rotas públicas de usuários
// =====================================
$router->post('/users', 'UserController@store'); // Criação de usuário deve ser pública

// =====================================
// Rotas protegidas com JWT
// =====================================

// =====================================
// Documentação da API
// =====================================
$router->get('/docs', function () {
    return response()->file(__DIR__ . '/../public/api-documentation.html');
});

$router->get('/api-docs', function () {
    return response()->file(__DIR__ . '/../public/api-docs-simple.json', 200, [
        'Content-Type' => 'application/json'
    ]);
});

$router->get('/api-docs-simple.json', function () {
    return response()->file(__DIR__ . '/../public/api-docs-simple.json', 200, [
        'Content-Type' => 'application/json'
    ]);
});

// =====================================
// Rotas públicas de autenticação
// =====================================
$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('/login', 'AuthController@login');
    $router->post('/refresh', 'AuthController@refresh');
});

// =====================================
// Rotas protegidas com JWT
// =====================================

// Rotas de autenticação protegidas
$router->group(['prefix' => 'auth', 'middleware' => 'jwt.auth'], function () use ($router) {
    $router->post('/logout', 'AuthController@logout');
    $router->get('/me', 'AuthController@me');
});

// Rotas de tarefas protegidas
$router->group(['prefix' => 'tasks', 'middleware' => 'jwt.auth'], function () use ($router) {

    $router->get('/', 'TaskController@index');
    $router->get('/{taskId}', 'TaskController@get');
    $router->post('/', 'TaskController@store');
    $router->put('/{taskId}', 'TaskController@update');
    $router->delete('/{taskId}', 'TaskController@destroy');
    
    // Rotas de gerenciamento de cache
    $router->post('/cache/clear', 'TaskController@clearCache');
    $router->get('/cache/stats', 'TaskController@cacheStats');
    $router->post('/cache/warm', 'TaskController@warmCache');
});

// Rotas de usuários protegidas (apenas administradores)
$router->group(['prefix' => 'users', 'middleware' => ['jwt.auth', 'role:admin']], function () use ($router) {
    $router->get('/', 'UserController@index');
    $router->get('/{userId}', 'UserController@get');
    $router->delete('/{userId}', 'UserController@delete');
    $router->patch('/{userId}/restore', 'UserController@restore');
});