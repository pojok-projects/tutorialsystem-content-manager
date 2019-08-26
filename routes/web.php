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
$router->group(['prefix'=>'/conm/v1'], function() use($router){
	$router->get('category/list', 'CategoryController@list');
    $router->get('category/list/{id}', 'CategoryController@list');
    $router->post('category/search_videos', 'CategoryController@search_videos');
    $router->get('category/search_videos/{genre}', 'CategoryController@search_videos');
});
