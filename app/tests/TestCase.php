<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase {

    protected $useDatabase = true;

	/**
	 * Creates the application.
	 *
	 * @return Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	public function createApplication()
	{
		$unitTesting = true;

		$testEnvironment = 'testing';

		return require __DIR__.'/../../bootstrap/start.php';
	}

    public function setUp()
    {
        parent::setUp();
        if ($this->useDatabase)
        {
            $this->setUpDb();
        }
    }

    private function setUpDb()
    {
        # Migrate databases (to use SQLite)
        Artisan::call('migrate');

        # Make the mailer pretend, so it doesn't send real email
        Mail::pretend(true);

        # Seed the DB with test data
        Artisan::call('db:seed');
    }

    public function tearDownDb()
    {
        Artisan::call('migrate:reset');
    }
}
