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

$app->get('', function () use ($app) {
    return new \Illuminate\Http\Response(file_get_contents('assets/index.html'));
});

$app->get('test', [
    'middleware' => ['auth'],
    'uses' => 'TestController@get'
]);

$app->get('index', [
    'uses' => 'IndexController@get'
]);

$app->post('login', [
    'uses' => 'LoginController@main'
]);

$app->get('file/{fileId}/{section}', [
    'as' => 'getFile',
    'uses' => 'FileController@get'
]);

$app->post('file/{fileId}/{section}', [
    'as' => 'opFile',
    'middleware' => ['auth'],
    'uses' => 'FileController@set'
]);

$app->get('lesson/{lessonName}/{section}', [
    'as' => 'getLesson',
    'uses' => 'LessonController@get'
]);

$app->post('lesson/{lessonName}/{section}', [
    'as' => 'opLesson',
    'middleware' => ['auth'],
    'uses' => 'LessonController@set'
]);

$app->get('uploadToken', [
    'middleware' => ['auth'],
    'uses' => 'UploadController@get'
]);

$app->post('uploaded', [
    'middleware' => ['auth'],
    'uses' => 'UploadController@set'
]);

$app->get('ranking', [
    'as' => 'ranking',
    'uses' => 'RankingController@get'
]);

$app->get('edit', [
    'as' => 'edit',
    'uses' => 'EditController@get'
]);

$app->post('edit', [
    'as' => 'edit',
    'middleware' => ['auth'],
    'uses' => 'EditController@set'
]);

$app->post('contribute', [
    'as' => 'contri',
    'middleware' => ['auth'],
    'uses' => 'UploadController@set'
]);
