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
});

$router->group(['prefix' => '/conm/v1/comment/'], function() use($router) {
    $router->get('/get/{id}', 'commentController@get');
    $router->post('/add/{id}', 'commentController@add');
    $router->post('/delete', 'commentController@destroy');
    $router->post('/update', 'commentController@update');
});

$router->group(['prefix' => '/conm/v1/subtitle/'], function() use($router) {
    $router->get('/get/{id}', 'subtitleController@get');
    $router->post('/add/{id}', 'subtitleController@add');
    $router->post('/delete', 'subtitleController@destroy');
    $router->post('/update', 'subtitleController@update');
});

$router->group(['prefix' => '/conm/v1/react/'], function() use($router) {
    $router->post('/add/{react}/{id}', 'reactController@add');
    $router->get('/get/{react}/{id}', 'reactController@get');
    $router->post('/delete/{react}', 'reactController@destroy');
});
