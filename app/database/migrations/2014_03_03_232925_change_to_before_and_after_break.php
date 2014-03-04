<?php

use Illuminate\Database\Migrations\Migration;

class ChangeToBeforeAndAfterBreak extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function($t)
        {
            $t->integer('break_before')->default(0);
            $t->integer('break_after')->default(0);
        });

        Schema::table('fixed_events', function($t)
        {
            $t->integer('break_before')->default(0);
            $t->integer('break_after')->default(0);
        });

        Schema::table('preferences', function($t)
        {
            $t->dropColumn('break');
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
            $t->dropColumn('break_before');
            $t->dropColumn('break_after');
        });

        Schema::table('fixed_events', function($t)
        {
            $t->dropColumn('break_before');
            $t->dropColumn('break_after');
        });

        Schema::table('preferences', function($t)
        {
            $t->integer('break');
        });
	}

}
