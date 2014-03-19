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

    public function testCanParseDependencyInput() {
        $data = json_decode( '
            {
              "dependencygraph" : {
                "t1" : ["t2"],
                "t2" : ["t3"]
              }
            }
        ', true );

        //Note that 2013-07-05 is a Friday, so only Sleep and Class apply as relevant events

        $new_input = InputConverter::convertToObject($data);

        $this->assertTrue(isset($new_input['DependencyGraph']));
        $this->assertTrue($new_input['DependencyGraph']['t1'] == array('t2'));
        $this->assertTrue($new_input['DependencyGraph']['t2'] == array('t3'));

    }

}
