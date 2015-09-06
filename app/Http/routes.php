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

$app->get('user', ['uses' => 'UsersController@getUser']);
$app->put('user', ['uses' => 'UsersController@putUser']);
$app->post('user', ['uses' => 'UsersController@postUser']);
$app->post('user/login', ['uses' => 'UsersController@postLogin']);
$app->post('user/logout', ['uses' => 'UsersController@postLogout']);
$app->put('user/password', ['uses' => 'UsersController@putPassword']);
$app->get('user/{id}', ['uses' => 'UsersController@getUserById']);
$app->put('user/{id}', ['uses' => 'UsersController@putUserById']);
$app->put('user/group/{id}/{group}', ['uses' => 'UsersController@putGroup']);

$app->get('collection/{name}', ['uses' => 'CollectionsController@getCollection']);
$app->post('collection/{name}', ['uses' => 'CollectionsController@postCollection']);
$app->delete('collection/{name}', ['uses' => 'CollectionsController@deleteCollection']);

$app->get('document/{name}', ['uses' => 'DocumentsController@listDocument']);
$app->post('document/{name}', ['uses' => 'DocumentsController@postDocument']);
$app->get('document/{name}/{id}', ['uses' => 'DocumentsController@getDocument']);
$app->put('document/{name}/{id}', ['uses' => 'DocumentsController@putDocument']);
$app->delete('document/{name}/{id}', ['uses' => 'DocumentsController@deleteDocument']);
$app->put('document/{name}/{id}/{access}', ['uses' => 'DocumentsController@putDocumentPublic']);
$app->put('document/{name}/{id}/{access}/user/{user}', ['uses' => 'DocumentsController@putUserPermission']);
$app->put('document/{name}/{id}/{access}/group/{group}', ['uses' => 'DocumentsController@putGroupPermission']);
$app->delete('document/{name}/{id}/{access}/user/{user}', ['uses' => 'DocumentsController@deleteUserPermission']);
$app->delete('document/{name}/{id}/{access}/group/{group}', ['uses' => 'DocumentsController@deleteGroupPermission']);

$app->post('file', ['uses' => 'FilesController@postFile']);
$app->get('file/{id}', ['uses' => 'FilesController@getFile']);
$app->delete('file/{id}', ['uses' => 'FilesController@deleteFile']);
$app->get('file/{id}/details', ['uses' => 'FilesController@getDetails']);
$app->post('file/{document}', ['uses' => 'FilesController@postFileToDocument']);
$app->put('file/{id}/{access}', ['uses' => 'FilesController@putFilePublic']);

$app->put('file/{id}/{access}/user/{user}', ['uses' => 'FilesController@putUserPermission']);
$app->put('file/{id}/{access}/group/{group}', ['uses' => 'FilesController@putGroupPermission']);
$app->delete('file/{id}/{access}/user/{user}', ['uses' => 'FilesController@deleteUserPermission']);
$app->delete('file/{id}/{access}/group/{group}', ['uses' => 'FilesController@deleteGroupPermission']);

$app->post('push', ['uses' => 'PushController@postPush']);
$app->put('push/{platform}', ['uses' => 'PushController@putDevice']);
$app->delete('push/{udid}', ['uses' => 'PushController@deleteDevice']);
