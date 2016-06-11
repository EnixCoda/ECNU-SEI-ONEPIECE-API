<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('password');
            $table->text('cademy');
            $table->text('token');
            $table->text('lastAlia');
            $table->timestamps();
        });
        Schema::create('log', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('action');
            $table->timestamps();
        });
        Schema::create('contribute', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('fileId');
            $table->timestamps();
        });
        Schema::create('edit', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('type')->comment('MOVE or TRASH or RENAME');
            $table->text('original');
            $table->text('edit');
            $table->timestamps();
        });
        Schema::create('comment', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('username');
            $table->text('type')->comment('file or lesson');
            $table->text('key');
            $table->text('comment');
            $table->timestamps();
        });
        Schema::create('score', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stuId');
            $table->text('type')->comment('file or lesson');
            $table->text('key');
            $table->integer('score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user');
        Schema::drop('log');
        Schema::drop('contribute');
        Schema::drop('edit');
        Schema::drop('comment');
        Schema::drop('score');
    }
}
