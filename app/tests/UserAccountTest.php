<?php

class UserAccountTest extends TestCase {

    protected   $useDatabase   = true;

    /**
     *  Test the creation of users.
     */
    public function testUser_Create()
    {
        $content = '{ "name": "Testy McTest", "email": "testy.m@email.com" }';
        $response = $this->call('POST', 'api/user', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        $this->assertResponseStatus(201);
        $this->assertEquals($json_response[ 'name' ], 'Testy McTest');
        $this->assertEquals($json_response[ 'email' ], 'testy.m@email.com');
        $this->assertTrue(is_numeric($json_response[ 'id' ]));
    }


}
