<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Reset extends Main_Controller {

	public function __construct() {
		parent::__construct(); 																	// calls the constructor
		$this->load->library('customer'); 														// load the customer library
		$this->load->model('Customers_model');													// load the customers model
		$this->load->model('Security_questions_model');											// load the security questions model
		$this->lang->load('reset');
	}

	public function index() {
		if ($this->customer->islogged()) { 														// checks if customer is logged in then redirect to account page.
  			redirect('account/account');
		}

		$this->template->setBreadcrumb('<i class="fa fa-home"></i>', '/');
		$this->template->setBreadcrumb($this->lang->line('text_heading'), 'reset');

		// START of retrieving lines from language file to pass to view.
		$this->template->setTitle($this->lang->line('text_heading'));
		//$this->template->setHeading($this->lang->line('text_heading'));
		$data['text_heading'] 				= $this->lang->line('text_heading');
		$data['text_summary'] 				= $this->lang->line('text_summary');
		$data['entry_email'] 				= $this->lang->line('entry_email');
		$data['entry_s_question'] 			= $this->lang->line('entry_s_question');
		$data['entry_s_answer'] 			= $this->lang->line('entry_s_answer');
		$data['button_continue'] 			= $this->lang->line('button_continue');
		$data['button_reset_password'] 		= $this->lang->line('button_reset_password');
		$data['button_login'] 				= $this->lang->line('button_login');
		// END of retrieving lines from language file to send to view.

		$data['login_url'] 				= site_url('account/login');

		$data['questions'] = array();
		$results = $this->Security_questions_model->getQuestions();						// retrieve array of security questions from getQuestions method in Security questions model
		foreach ($results as $result) {															// loop through security questions array
			$data['questions'][] = array(														// create an array of security questions to pass to view
				'id'	=> $result['question_id'],
				'text'	=> $result['text']
			);
		}

		if ($this->session->userdata('customer_id_to_reset')) {							// checks if session userdata customer_id_to_reset value is set
			$customer_data = $this->Customers_model->getCustomer($this->session->userdata('customer_id_to_reset'));	// retrieve customer data from getCustomer method in Customers model
			if ($customer_data) {																// check if customer data is available send customer email and customer security question to view
				$data['customer_email'] = $customer_data['email'];
				$security_question_id = $customer_data['security_question_id'];
				$question_data = $this->Security_questions_model->getQuestion($security_question_id);	// retrieve security question data from getQuestions method in Security questions model
				$data['security_question'] = $question_data['question_text'];

			} else {																			// else send nothing to view
				$data['customer_email'] = '';
				$data['security_question'] = '';
			}
		}

		if ($this->input->post()) {																// checks if $_POST data is set
			$this->_resetPassword();															// invoke this _resetPassword method
		}

		if ($this->session->flashdata('security_question_id')) {
			$security_question_id = $this->session->flashdata('security_question_id');
			$question_data = $this->Security_questions_model->getQuestion($security_question_id);

			$data['security_question'] = $question_data['question_text'];
			$data['customer_email'] = $this->session->flashdata('customer_email');
		} else {
			$data['security_question'] = '';
		}

		if ($this->input->server('REQUEST_METHOD') === 'POST') {
			if ($this->_resetPassword() === TRUE) {
				redirect('account/login');
			}
		}

		$this->template->setPartials(array('header', 'content_top', 'content_left', 'content_right', 'content_bottom', 'footer'));
		$this->template->render('reset', $data);
	}

	private function _resetPassword() {															// method to validate password reset
		if ($this->input->post()) {

			if ($this->validateForm() === TRUE) {
				$email 					= $this->input->post('email');
				$security_question_id	= $this->input->post('security_question');
				$security_answer 		= $this->input->post('security_answer');

				$customer_data = $this->Customers_model->getCustomerByEmail($email); 			// retrieve customer data based on $_POST email value from getCustomerByEmail method in Customers model

				if ($customer_data['email'] !== $email) {																// check if customer data is available send customer email and customer security question to view
					$this->alert->set('alert', $this->lang->line('alert_no_email'));
				} else if ($customer_data['security_question_id'] !== $security_question_id) {
					$this->alert->set('alert', $this->lang->line('alert_no_s_question'));
				} else if ($customer_data['security_answer'] !== $security_answer) {
					$this->alert->set('alert', $this->lang->line('alert_no_s_answer'));
				} else {
					$customer_id = $customer_data['customer_id'];
					$reset_password = $this->Customers_model->resetPassword($customer_id, $email, $security_question_id, $security_answer); // invoke reset password method in Customers model using customer id, email and security answer
				}

				if ($reset_password) {													// checks if password reset was sucessful then display success message and delete customer_id_to_reset from session userdata
					$this->alert->set('alert', $this->lang->line('alert_reset_success'));
					return TRUE;
				}

				redirect('reset');												// redirect to password reset page
			}
		}
	}

	private function validateForm() {
		$this->form_validation->set_rules('email', 'Email Address', 'xss_clean|trim|required|valid_email');	//validate form
		$this->form_validation->set_rules('security_question', 'Security Question', 'xss_clean|trim|required|integer');
		$this->form_validation->set_rules('security_answer', 'Security Answer', 'xss_clean|trim|required|min_length[2]');

		if ($this->form_validation->run() === TRUE) {										// checks if form validation routines ran successfully
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

/* End of file reset.php */
/* Location: ./main/controllers/reset.php */