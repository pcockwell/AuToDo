<?php

class SmokeTest extends TestCase {

    /**
     * Test that the server is up.
     *
     * Should hit the base link redirect to github io page.
     *
     * @return void
     */
    public function testDefaultRedirect()
    {
        $this->client->restart();

        $response = $this->call('GET', '/');
        $this->assertRedirectedTo('http://pcockwell.github.io/AuToDo/');
        print $response;
    }

}
