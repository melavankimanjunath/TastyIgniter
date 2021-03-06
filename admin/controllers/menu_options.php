<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Menu_options extends Admin_Controller {

    public $_permission_rules = array('access[index|edit]', 'modify[index|edit]');

    public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('pagination');
		$this->load->library('currency'); // load the currency library
		$this->load->model('Menu_options_model'); // load the menus model
	}

	public function index() {
		$url = '?';
		$filter = array();
		if ($this->input->get('page')) {
			$filter['page'] = (int) $this->input->get('page');
		} else {
			$filter['page'] = '';
		}

		if ($this->config->item('page_limit')) {
			$filter['limit'] = $this->config->item('page_limit');
		}

		if ($this->input->get('filter_search')) {
			$filter['filter_search'] = $data['filter_search'] = $this->input->get('filter_search');
			$url .= 'filter_search='.$filter['filter_search'].'&';
		} else {
			$data['filter_search'] = '';
		}

		if ($this->input->get('filter_display_type')) {
			$filter['filter_display_type'] = $data['filter_display_type'] = $this->input->get('filter_display_type');
			$url .= 'filter_display_type='.$filter['filter_display_type'].'&';
		} else {
			$filter['filter_display_type'] = $data['filter_display_type'] = '';
		}

		if ($this->input->get('sort_by')) {
			$filter['sort_by'] = $data['sort_by'] = $this->input->get('sort_by');
		} else {
			$filter['sort_by'] = $data['sort_by'] = 'priority';
		}

		if ($this->input->get('order_by')) {
			$filter['order_by'] = $data['order_by'] = $this->input->get('order_by');
			$data['order_by_active'] = $this->input->get('order_by') .' active';
		} else {
			$filter['order_by'] = $data['order_by'] = 'ASC';
			$data['order_by_active'] = 'ASC';
		}

		$this->template->setTitle('Menu Options');
		$this->template->setHeading('Menu Options');
		$this->template->setButton('+ New', array('class' => 'btn btn-primary', 'href' => page_url() .'/edit'));
		$this->template->setButton('Delete', array('class' => 'btn btn-danger', 'onclick' => '$(\'#list-form\').submit();'));

		$data['text_empty'] 		= 'There are no menu options, please add!.';

		$order_by = (isset($filter['order_by']) AND $filter['order_by'] == 'ASC') ? 'DESC' : 'ASC';
		$data['sort_name'] 			= site_url('menu_options'.$url.'sort_by=option_name&order_by='.$order_by);
		$data['sort_priority'] 		= site_url('menu_options'.$url.'sort_by=priority&order_by='.$order_by);
		$data['sort_display_type'] 	= site_url('menu_options'.$url.'sort_by=display_type&order_by='.$order_by);
		$data['sort_id'] 			= site_url('menu_options'.$url.'sort_by=option_id&order_by='.$order_by);

		$data['menu_options'] = array();
		$results = $this->Menu_options_model->getList($filter);
		foreach ($results as $result) {
			$data['menu_options'][] = array(
				'option_id' 	=> $result['option_id'],
				'option_name' 	=> $result['option_name'],
				'priority' 		=> $result['priority'],
				'display_type' 	=> ucwords($result['display_type']),
				'edit' 			=> site_url('menu_options/edit?id=' . $result['option_id'])
			);
		}

		if ($this->input->get('sort_by') AND $this->input->get('order_by')) {
			$url .= 'sort_by='.$filter['sort_by'].'&';
			$url .= 'order_by='.$filter['order_by'].'&';
		}

		$config['base_url'] 		= site_url('menu_options').$url;
		$config['total_rows'] 		= $this->Menu_options_model->getCount($filter);
		$config['per_page'] 		= $filter['limit'];

		$this->pagination->initialize($config);

		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		if ($this->input->post('delete') AND $this->_deleteMenuOption() === TRUE) {

			redirect('menu_options');
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('menu_options', $data);
	}

	public function edit() {
		$option_info = $this->Menu_options_model->getOption((int) $this->input->get('id'));

		if ($option_info) {
			$option_id = $option_info['option_id'];
			$data['action']	= site_url('menu_options/edit?id='. $option_id);
		} else {
			$option_id = 0;
			$data['action']	= site_url('menu_options/edit');
		}

		$title = (isset($option_info['option_name'])) ? $option_info['option_name'] : 'New';
		$this->template->setTitle('Menu Option: '. $title);
		$this->template->setHeading('Menu Option: '. $title);
		$this->template->setButton('Save', array('class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();'));
		$this->template->setButton('Save & Close', array('class' => 'btn btn-default', 'onclick' => 'saveClose();'));
		$this->template->setBackButton('btn btn-back', site_url('menu_options'));

		$data['option_id'] 			= $option_info['option_id'];
		$data['option_name'] 		= $option_info['option_name'];
		$data['display_type'] 		= $option_info['display_type'];
		$data['priority'] 			= $option_info['priority'];

		if ($this->input->post('option_values')) {
			$values = $this->input->post('option_values');
		} else {
			$values = $this->Menu_options_model->getOptionValues($option_id);
		}

		$data['values'] = array();
		foreach ($values as $value) {
			$data['values'][] = array(
				'option_value_id'	=> $value['option_value_id'],
				'value'				=> $value['value'],
				'price'				=> $value['price']
			);
		}

		if ($this->input->post() AND $option_id = $this->_saveOption()){
			if ($this->input->post('save_close') === '1') {
				redirect('menu_options');
			}

			redirect('menu_options/edit?id='. $option_id);
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('menu_options_edit', $data);
	}

	public function autocomplete() {
		$json = array();

		if ($this->input->get('term')) {
			$filter = array(
				'option_name' => $this->input->get('term')
			);

			$results = $this->Menu_options_model->getAutoComplete($filter);
			if ($results) {
				foreach ($results as $result) {
					$json['results'][] = array(
						'id' 				=> $result['option_id'],
						'text' 				=> utf8_encode($result['option_name']),
						'display_type' 		=> utf8_encode($result['display_type']),
						'priority' 			=> $result['priority'],
						'option_values' 	=> $result['option_values']
					);
				}
			} else {
				$json['results'] = array('id' => '0', 'text' => 'No Matches Found');
			}
		}

		$this->output->set_output(json_encode($json));
	}

	private function _saveOption() {
    	if ($this->validateForm() === TRUE) {
            $save_type = (! is_numeric($this->input->get('id'))) ? 'added' : 'updated';

			if ($option_id = $this->Menu_options_model->saveOption($this->input->get('id'), $this->input->post())) {
				$this->alert->set('success', 'Menu option ' . $save_type . ' successfully.');
			} else {
				$this->alert->set('warning', 'An error occurred, ' . $save_type . ' updated.');
			}

			return $option_id;
		}
	}

	private function _deleteMenuOption() {
    	if (is_array($this->input->post('delete'))) {
			foreach ($this->input->post('delete') as $key => $option_id) {
				$this->Menu_options_model->deleteOption($option_id);
			}

			$this->alert->set('success', 'Menu option(s) deleted successfully!');
		}

		return TRUE;
	}

	private function validateForm() {
		$this->form_validation->set_rules('option_name', 'Option Name', 'xss_clean|trim|required|min_length[2]|max_length[32]');
		$this->form_validation->set_rules('display_type', 'Display Type', 'xss_clean|trim|required|alpha');
		$this->form_validation->set_rules('priority', 'Priority', 'xss_clean|trim|required|integer');

		if ($this->input->post('option_values')) {
			foreach ($this->input->post('option_values') as $key => $value) {
				$this->form_validation->set_rules('option_values['.$key.'][value]', 'Value', 'xss_clean|trim|required|min_length[2]|max_length[128]');
				$this->form_validation->set_rules('option_values['.$key.'][price]', 'Price', 'xss_clean|trim|required|numeric');
			}
		}

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

/* End of file menu_options.php */
/* Location: ./admin/controllers/menu_options.php */