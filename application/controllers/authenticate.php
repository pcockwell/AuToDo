<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pcockwell
 * Date: 5/29/12
 * Time: 4:11 PM
 * To change this template use File | Settings | File Templates.
 */

class Authenticate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model("user/UserModel");
        session_start();
    }

    public function index(){

        $token = $this->session->userdata('token');
		//If user logged in, log them out
        if ($token) {
		    self::logout();
        }

        //Otherwise log them in
		self::login();

    }

	private function login(){

        $client = new apiClient();
        $client->setApplicationName("AuToDo");
        $oauth2 = new apiOauth2Service($client);
        $authUrl = $client->createAuthUrl();
        redirect($authUrl);

	}

    public function logout(){

        $token = $this->session->userdata('token');
        if ($token) {
            $this->session->unset_userdata('token');
            $this->session->unset_userdata('name');
            $this->session->unset_userdata('user');
        }

        redirect('autodo');

    }

	public function authenticate_callback(){
        $client = new apiClient();
        $client->setApplicationName("AuToDo");
        $oauth2 = new apiOauth2Service($client);

        if ($this->input->get('code')) {
            $client->authenticate();
            $this->session->set_userdata(array('token' => $client->getAccessToken()));

            $user_info = $oauth2->userinfo->get();
            $this->session->set_userdata(array('name' => $user_info['name']));

            // These fields are currently filtered through the PHP sanitize filters.
            // See http://www.php.net/manual/en/filter.filters.sanitize.php
            $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
            $user = $this->UserModel->get_or_create_user($email, $user_info['name']);
            $this->session->set_userdata(array('user' => json_encode($user)));
            redirect('autodo/schedule');
        }else if ( $this->input->get('error') == 'access_denied' ){
            redirect('autodo');
        }
        redirect('autodo');
	}

}
