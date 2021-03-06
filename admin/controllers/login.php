<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Login extends Admin_Controller {

	public function __construct() {
		parent::__construct(); //  calls the constructor
	}

	public function index() {
		if ($this->user->islogged()) {
  			redirect('dashboard');
		}

		$this->template->setTitle('Login');
        $data['site_name']  = $this->config->item('site_name');
        $data['reset_url'] = site_url('login/reset');

		if ($this->input->post() AND $this->validateLoginForm() === TRUE) {
			redirect('dashboard');
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('login', $data);
	}

	public function reset() {
		$this->load->model('Staffs_model');
		if ($this->user->islogged()) {
  			redirect('dashboard');
		}

		$this->template->setTitle('Reset Password');
		$data['login_url'] = site_url('login');

		if ($this->input->post() AND $this->validateResetForm() === TRUE) {
			redirect('login');
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('login_reset', $data);
	}

	private function validateLoginForm() {
		// START of form validation rules
		$this->form_validation->set_rules('user', 'Username', 'xss_clean|trim|required');
		$this->form_validation->set_rules('password', 'Password', 'xss_clean|trim|required|min_length[6]|max_length[32]');
		// END of form validation rules

		if ($this->form_validation->run() === TRUE) {
			if (!$this->user->login($this->input->post('user'), $this->input->post('password'))) {										// checks if form validation routines ran successfully
				$this->alert->set('danger', 'Username and Password not found!');
				redirect('login');
			} else {
				return TRUE;
			}
		}
	}

	private function validateResetForm() {
		$this->form_validation->set_rules('user_email', 'Username or Email', 'xss_clean|trim|required|callback__check_user');	//validate form

		if ($this->form_validation->run() === TRUE) {										// checks if form validation routines ran successfully
			if ($this->Staffs_model->resetPassword($this->input->post('user_email'))) {		// checks if form validation routines ran successfully
				$this->alert->set('success', 'A new password will be e-mailed to you.');
				return TRUE;
			} else {
				$this->alert->set('danger', 'The e-mail could not be sent. Possible reason: your host may have disabled the mail() function.');
				redirect('login/reset');
			}
		} else {
			return FALSE;
		}
	}

	public function _check_user($str) {
		if (empty($str)) {
			$this->form_validation->set_message('_check_user', 'Enter a username or email address.');
			return FALSE;
		} else if (!$this->Staffs_model->resetPassword($str)) {
			$this->form_validation->set_message('_check_user', 'There is no user registered with that email address.');
			return FALSE;
		}

		return TRUE;
	}
}

/* End of file login.php */
/* Location: ./admin/controllers/login.php */