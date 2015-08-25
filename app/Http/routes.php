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

$app->get('me', ['uses' => 'UsersController@getMe']);
$app->put('user', ['uses' => 'UsersController@putUser']);
$app->post('login', ['uses' => 'UsersController@postLogin']);
$app->get('user/{id}', ['uses' => 'UsersController@getUser']);
$app->post('logout', ['uses' => 'UsersController@postLogout']);
$app->post('user', ['uses' => 'UsersController@postRegister']);
$app->put('password', ['uses' => 'UsersController@putPassword']);
$app->put('user/{id}', ['uses' => 'UsersController@putUserById']);
$app->put('group/{id}/{group}', ['uses' => 'UsersController@putGroup']);

$app->get('collection/{name}', ['uses' => 'CollectionsController@getCollection']);
$app->post('collection/{name}', ['uses' => 'CollectionsController@postCollection']);
$app->delete('collection/{name}', ['uses' => 'CollectionsController@deleteCollection']);

$app->get('document/{name}', ['uses' => 'DocumentsController@listDocument']);
$app->post('document/{name}', ['uses' => 'DocumentsController@postDocument']);
$app->get('document/{name}/{id}', ['uses' => 'DocumentsController@getDocument']);
$app->put('document/{name}/{id}', ['uses' => 'DocumentsController@putDocument']);
$app->delete('document/{name}/{id}', ['uses' => 'DocumentsController@deleteDocument']);

$app->put('document/{name}/{id}/grant/{access}/user/{user}', ['uses' => 'DocumentsController@putUserPermission']);
$app->put('document/{name}/{id}/grant/{access}/group/{group}', ['uses' => 'DocumentsController@putGroupPermission']);
$app->delete('document/{name}/{id}/revoke/{access}/user/{user}', ['uses' => 'DocumentsController@deleteUserPermission']);
$app->delete('document/{name}/{id}/revoke/{access}/group/{group}', ['uses' => 'DocumentsController@deleteGroupPermission']);

$app->post('file', ['uses' => 'FilesController@postFile']);
$app->get('file/{id}', ['uses' => 'FilesController@getFile']);
$app->delete('file/{id}', ['uses' => 'FilesController@deleteFile']);
$app->get('file/{id}/details', ['uses' => 'FilesController@getDetails']);
$app->post('file/{document}', ['uses' => 'FilesController@postFileToDocument']);

$app->put('file/{id}/grant/{access}/user/{user}', ['uses' => 'FilesController@putUserPermission']);
$app->put('file/{id}/grant/{access}/group/{group}', ['uses' => 'FilesController@putGroupPermission']);
$app->delete('file/{id}/revoke/{access}/user/{user}', ['uses' => 'FilesController@deleteUserPermission']);
$app->delete('file/{id}/revoke/{access}/group/{group}', ['uses' => 'FilesController@deleteGroupPermission']);

$app->post('push', ['uses' => 'PushController@postPush']);
$app->put('push/{platform}', ['uses' => 'PushController@putDevice']);
$app->delete('push/{udid}', ['uses' => 'PushController@deleteDevice']);
