<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->model('admin_model');
		$this->load->helper('url');
	}

	
	public function index()
	{
		if ($this->session->userdata('logged_in')) {
			redirect('dashboard');
		}
		$data['viewpage'] = 'index';
		$this->load->view('welcome_message', $data);
	}

	public function login()
	{
		$this->form_validation->set_rules('username', 'Username', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run() == FALSE) {
			redirect('login');
		} else {
			$username = $this->input->post('username');
			$password = $this->input->post('password');

			$user = $this->admin_model->get_user($username);

			if ($user && $this->admin_model->verify_password($password, $user->password)) {
				$userdata = array(
					'user_id' => $user->id,
					'username' => $user->username,
					'logged_in' => TRUE
				);
				$this->session->set_userdata($userdata);
				redirect('dashboard');
			} else {
				$this->session->set_flashdata('error', 'Invalid username or password.');
				redirect('login');
			}
		}
	}

	public function logout()
	{
		$this->session->unset_userdata('logged_in');
		$this->session->sess_destroy();
		redirect('login');
	}
}

