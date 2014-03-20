<?php

use Illuminate\Database\Migrations\Migration;

class Softdeletion extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function($t)
        {
			$t->softDeletes();
        });

        Schema::table('fixed_events', function($t)
        {
			$t->softDeletes();
        });

        Schema::table('users', function($t)
        {
			$t->softDeletes();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('tasks', function($t)
        {
            $t->dropColumn('deleted_at');
        });

        Schema::table('fixed_events', function($t)
        {
            $t->dropColumn('deleted_at');
        });

        Schema::table('users', function($t)
        {
            $t->dropColumn('deleted_at');
        });
	}

}