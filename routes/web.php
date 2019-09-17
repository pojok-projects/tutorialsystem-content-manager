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
$router->group(['prefix'=>'/conm/v1/category/'], function() use($router){
  	$router->get('list', 'ContentCategoryController@list');
	$router->get('{id}', 'ContentCategoryController@list');
    	$router->post('search_videos', 'ContentCategoryController@search_videos');
	$router->post('add', 'ContentCategoryController@add');
	$router->post('remove', 'ContentCategoryController@remove');
});

$router->group(['prefix' => '/conm/v1/'], function () use($router) {
    $router->get('/', 'metadataController@index');
    $router->get('/{id}', 'metadataController@index');
    $router->post('/store', 'metadataController@store');
    $router->post('/search', 'metadataController@search');
    $router->post('/update/{id}', 'metadataController@update');
    $router->get('/delete/{id}', 'metadataController@delete');
    $router->get('/download/{id}', 'metadataController@addDownload');
    $router->get('/save/{id}', 'metadataController@addSave');
    $router->get('/share/{id}', 'metadataController@addShare');
    $router->get('/view/{id}', 'metadataController@addViewer');
    $router->post('/like/{id}', 'reactController@like');
    $router->post('/dislike/{id}', 'reactController@dislike');
});
