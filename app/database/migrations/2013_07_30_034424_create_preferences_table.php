<?php

use Illuminate\Database\Migrations\Migration;

class CreatePreferencesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('preferences', function($t) {
	        $t->increments('id');
	        $t->integer('user_id');
	        $t->integer('break')->unsigned()->default(Preference::DEFAULT_BREAK);
	        $t->boolean('show_fixed_events')->default(Preference::DEFAULT_SHOW_FIXED_EVENTS);
	        $t->boolean('schedule_until_latest')->default(Preference::DEFAULT_SCHEDULE_UNTIL_LATEST);
	        $t->datetime('created_at');
	        $t->datetime('updated_at');
	        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
	    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('preferences');
	}

}