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


//===================Start app ========= php -S localhost:9090 -t public

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('lists', ['uses' => 'MailChimpController@getLists']);
    $router->get('lists/{id}', ['uses' => 'MailChimpController@getList']);
    $router->PUT('lists/{id}', ['uses' => 'MailChimpController@updateList']);
    $router->DELETE('lists/{id}', ['uses' => 'MailChimpController@deleteList']);
    $router->POST('lists/', ['uses' => 'MailChimpController@createList']);
});


$router->group(['prefix' => 'api'], function () use ($router)
{
    $router->get('lists/{id}/members/', ['uses' => 'MailChimpController@members']);
    $router->POST('lists/{id}/members/', ['uses' => 'MailChimpController@addMember']);
    $router->POST('lists/{id}/members/update', ['uses' => 'MailChimpController@updateMember']);
    $router->Delete('lists/{id}/members', ['uses' => 'MailChimpController@deleteMember']);

});