<?php

use Illuminate\Database\Migrations\Migration;

class CreateTasks extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('tasks', function($t) {
            $t->engine = 'InnoDB';

            $t->increments('id');

            $t->integer('user_id')->unsigned();
            $t->string('name', 255);
            $t->integer('priority');
            $t->dateTime('due');
            $t->integer('duration');
            $t->integer('complete')->default(0);
            $t->dateTime('created_at');
            $t->dateTime('updated_at');

            $t->index('user_id');
            $t->foreign('user_id')->references('id')->on('users');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('tasks');
	}

}
