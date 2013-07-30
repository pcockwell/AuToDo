<?php

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

        DB::statement("SET FOREIGN_KEY_CHECKS=0");
		$this->call('UserTableSeeder');	
        $this->command->info('User table seeded!');
		$this->call('FixedEventTableSeeder');	
        $this->command->info('Fixed event table seeded!');
		$this->call('TaskTableSeeder');	
        $this->command->info('Task table seeded!');
		$this->call('PreferenceTableSeeder');	
        $this->command->info('Preference table seeded!');
        DB::statement("SET FOREIGN_KEY_CHECKS=1");
	}

}