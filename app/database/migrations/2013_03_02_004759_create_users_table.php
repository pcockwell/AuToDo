<?php

use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function($t) {
            $t->engine = 'InnoDB';

            $t->increments('id');

            $t->string('email', 255)->unique();
            $t->string('name', 255);
            $t->dateTime('created_at');
            $t->dateTime('updated_at');
            
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('users');
	}

}
