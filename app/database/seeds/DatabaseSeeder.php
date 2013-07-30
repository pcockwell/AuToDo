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

		$this->call('UserTableSeeder');	
        $this->command->info('User table seeded!');
		$this->call('FixedEventTableSeeder');	
        $this->command->info('Fixed event table seeded!');
		$this->call('TaskTableSeeder');	
        $this->command->info('Task table seeded!');
		$this->call('PreferenceTableSeeder');	
        $this->command->info('Preference table seeded!');
	}

}