<?php

use Illuminate\Database\Migrations\Migration;

class CreateFixedEvents extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('fixed_events', function($t) {
            $t->engine = 'InnoDB';

            $t->increments('id');
           
            $t->integer('user_id')->unsigned();
            $t->string('name', 255);
            $t->integer('start_time');
            $t->integer('end_time');
            $t->timestamp('start_date');
            $t->timestamp('end_date');
            $t->string('recurrences', 255);
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
        Schema::drop('fixed_events');
	}

}
