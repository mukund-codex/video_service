<?php

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

$router->group(['middleware' => []], function () use ($router) {
    $router->get('/', function () use ($router) {
        $res['success'] = true;
        $res['data'] = [
            'app_name' => env('APP_NAME', true),
            'app_version' => env('APP_VERSION',true),
        ];
        return response($res);
    });
});

$router->group(['namespace' => 'Process'], function() use ($router) {
    $router->post('video', 'VideoController@store');
});
