<?php

class SmokeTest extends TestCase {

    /**
     * Test that the server is up.
     *
     * Hits the 'hello' page.
     * Should produce "Hello `name`"
     *
     * @return void
     */
    public function testServerUp()
    {
        $test_name = 'TEST';
        $response = $this->call('GET', 'api/name/'.$test_name);
        $this->assertEquals('Hello '.$test_name, 
            $response->getContent());
    }

}
