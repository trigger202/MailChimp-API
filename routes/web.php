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

$router->get('/', function () use ($router) {
    return $router->app->version();
});


//$client =app::make('App\APIClient');



$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('lists', ['uses' => 'ListController@getLists']);
    $router->get('lists/{id}', ['uses' => 'ListController@getList']);
    $router->POST('lists/{id}/update', ['uses' => 'ListController@updateList']);
    $router->DELETE('lists/{id}', ['uses' => 'ListController@deleteList']);

    $router->POST('lists/', ['uses' => 'ListController@createList']);
//    $router->get('lists/test', ['uses' => 'ListController@getCount']);
});