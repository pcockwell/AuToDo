<?php

class UserAccountTest extends TestCase {

    protected   $useDatabase   = true;

    /**
     *  Test the creation of users.
     */
    public function testUser_Create()
    {
        $content = '{ "name": "Testy McTest", "email": "testy.m@email.com" }';
        $response = $this->call('POST', '/api/user', 
            array(), array(), array('CONTENT_TYPE' => 'application/json'),
            $content);

        $json_response = json_decode($response->getContent(), true);

        $this->assertResponseStatus(201);
        $this->assertEquals($json_response[ 'name' ], 'Testy McTest');
        $this->assertEquals($json_response[ 'email' ], 'testy.m@email.com');
        $this->assertTrue(is_numeric($json_response[ 'id' ]));
    }

    /**
     * Test finding users by email address.
     */
    public function testUser_FindByEmail_exists()
    {
        $email = 'test@example.com';
        $response = $this->call('GET', '/api/user/find',
            array('email'=>$email), array(), array(), '');
        $json_response = json_decode($response->getContent(), true);

        $this->assertResponseOk();
        $this->assertEquals($json_response['name'], 'Test User');
        $this->assertEquals($json_response['email'], $email);
        $this->assertTrue(is_numeric($json_response['id']));
    }

    /**
     * Test trying to find a user by an email that doesn't exist.
     */
    public function testUser_FindByEmail_dnexist()
    {
        $exception = 'Illuminate\Database\Eloquent\ModelNotFoundException';
        $this->setExpectedException($exception);

        $email = 'fake_email@shouldntexist.com';
        $this->call('GET', '/api/user/find',
            array('email'=>$email), array(), array(), '');
    }

    /**
     * Test not passing an email to find.
     */
    public function testUser_FindByEmail_noemail()
    {
        $exception = 'Illuminate\Database\Eloquent\ModelNotFoundException';
        $this->setExpectedException($exception);
        $this->call('GET', '/api/user/find', array(), array(), array(), '');
    }
}
