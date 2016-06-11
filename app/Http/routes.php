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

$app->get('', function () use($app) {
    return new \Symfony\Component\HttpFoundation\Response(file_get_contents("index.html"));
});

$app->get('test', [
    "middleware" => "log:test",
    "uses" => "TestController@get"
]);

$app->get('index', [
    "middleware" => "log:index",
    "uses" => "IndexController@get"
]);

$app->post('login', [
    "middleware" => "log:login",
    "uses" => "LoginController@main"
]);
//
$app->get('file/{fileId}/{section}', [
    "as" => "getFile",
    "middleware" => "log:file-get",
    "uses" => "FileController@get"
]);

$app->post('file/{fileId}/{section}', [
    "as" => "opFile",
    "middleware" => ["auth", "log:file-set"],
    "uses" => "FileController@set"
]);

$app->get('lesson/{lessonName}/{section}', [
    "as" => "getLesson",
    "middleware" => "log:lesson-get",
    "uses" => "LessonController@get"
]);

$app->post('lesson/{lessonName}/{section}', [
    "as" => "opLesson",
    "middleware" => ["auth", "log:lesson-set"],
    "uses" => "LessonController@set"
]);

$app->get('uploadToken', [
    "as" => "uploadToken",
    "middleware" => ["log:uploadToken-get"],
    "uses" => "UploadController@get"
]);

$app->get('ranking', [
    "as" => "ranking",
    "middleware" => ["log:ranking-get"],
    "uses" => "RankingController@get"
]);

$app->get('edit', [
    "as" => "edit",
    "middleware" => ["log:edit-get"],
    "uses" => "EditController@get"
]);

$app->post('edit', [
    "as" => "edit",
    "middleware" => ["auth", "log:edit-set"],
    "uses" => "EditController@set"
]);

$app->post('contribute', [
    "as" => "contri",
    "middleware" => ["auth", "log:contribute"],
    "uses" => "UploadController@set"
]);
