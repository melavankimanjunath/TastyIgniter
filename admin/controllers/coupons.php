<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Coupons extends Admin_Controller {

    public $_permission_rules = array('access[index|edit]', 'modify[index|edit]');

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('pagination');
		$this->load->model('Coupons_model');
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

		if ($this->input->get('filter_type')) {
			$filter['filter_type'] = $data['filter_type'] = $this->input->get('filter_type');
			$url .= 'filter_type='.$filter['filter_type'].'&';
		} else {
			$filter['filter_type'] = $data['filter_type'] = '';
		}

		if (is_numeric($this->input->get('filter_status'))) {
			$filter['filter_status'] = $data['filter_status'] = $this->input->get('filter_status');
			$url .= 'filter_status='.$filter['filter_status'].'&';
		} else {
			$filter['filter_status'] = $data['filter_status'] = '';
		}

		if ($this->input->get('sort_by')) {
			$filter['sort_by'] = $data['sort_by'] = $this->input->get('sort_by');
		} else {
			$filter['sort_by'] = $data['sort_by'] = 'coupon_id';
		}

		if ($this->input->get('order_by')) {
			$filter['order_by'] = $data['order_by'] = $this->input->get('order_by');
			$data['order_by_active'] = $this->input->get('order_by') .' active';
		} else {
			$filter['order_by'] = $data['order_by'] = 'DESC';
			$data['order_by_active'] = 'DESC';
		}

		$this->template->setTitle('Coupons');
		$this->template->setHeading('Coupons');
		$this->template->setButton('+ New', array('class' => 'btn btn-primary', 'href' => page_url() .'/edit'));
		$this->template->setButton('Delete', array('class' => 'btn btn-danger', 'onclick' => '$(\'#list-form\').submit();'));

		$data['text_empty'] 		= 'There are no coupons available.';

		$order_by = (isset($filter['order_by']) AND $filter['order_by'] == 'ASC') ? 'DESC' : 'ASC';
		$data['sort_name'] 			= site_url('coupons'.$url.'sort_by=name&order_by='.$order_by);
		$data['sort_code'] 			= site_url('coupons'.$url.'sort_by=code&order_by='.$order_by);
		$data['sort_type'] 			= site_url('coupons'.$url.'sort_by=type&order_by='.$order_by);
		$data['sort_discount'] 		= site_url('coupons'.$url.'sort_by=discount&order_by='.$order_by);
		$data['sort_validity'] 		= site_url('coupons'.$url.'sort_by=validity&order_by='.$order_by);

		$data['coupons'] = array();
		$results = $this->Coupons_model->getList($filter);
		foreach ($results as $result) {
			$data['coupons'][] = array(
				'coupon_id'		=> $result['coupon_id'],
				'name'			=> $result['name'],
				'code'			=> $result['code'],
				'type'			=> ($result['type'] === 'P') ? 'Percentage' : 'Fixed Amount',
				'discount'		=> ($result['type'] === 'P') ? round($result['discount']) .'%' : $result['discount'],
				'min_total'		=> $result['min_total'],
				'validity'		=> ucwords($result['validity']),
				'description'	=> $result['description'],
				'status'		=> ($result['status'] === '1') ? 'Enabled' : 'Disabled',
				'edit' 			=> site_url('coupons/edit?id=' . $result['coupon_id'])
			);
		}

		if ($this->input->get('sort_by') AND $this->input->get('order_by')) {
			$url .= 'sort_by='.$filter['sort_by'].'&';
			$url .= 'order_by='.$filter['order_by'].'&';
		}

		$config['base_url'] 		= site_url('coupons').$url;
		$config['total_rows'] 		= $this->Coupons_model->getCount($filter);
		$config['per_page'] 		= $filter['limit'];

		$this->pagination->initialize($config);

		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		if ($this->input->post('delete') AND $this->_deleteCoupon() === TRUE) {
			redirect('coupons');
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('coupons', $data);
	}

	public function edit() {
		$coupon_info = $this->Coupons_model->getCoupon((int) $this->input->get('id'));

		if ($coupon_info) {
			$coupon_id = $coupon_info['coupon_id'];
			$data['action']	= site_url('coupons/edit?id='. $coupon_id);
		} else {
		    $coupon_id = 0;
			$data['action']	= site_url('coupons/edit');
		}

		if ($this->input->post('validity')) {
			$validity = $this->input->post('validity');
		} else if (!empty($coupon_info['validity'])) {
			$validity = $coupon_info['validity'];
		} else {
			$validity = 'forever';
		}

		$title = (isset($coupon_info['name'])) ? $coupon_info['name'] : 'New';
		$this->template->setTitle('Coupon: '. $title);
		$this->template->setHeading('Coupon: '. $title);
		$this->template->setButton('Save', array('class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();'));
		$this->template->setButton('Save & Close', array('class' => 'btn btn-default', 'onclick' => 'saveClose();'));
		$this->template->setBackButton('btn btn-back', site_url('coupons'));

		$data['text_empty'] 		= 'There is no history available for this coupon.';

		$data['coupon_id'] 			= $coupon_info['coupon_id'];
		$data['name'] 				= $coupon_info['name'];
		$data['code'] 				= $coupon_info['code'];
		$data['type'] 				= $coupon_info['type'];
		$data['discount'] 			= substr($coupon_info['discount'], 0, strripos($coupon_info['discount'], '.'));
		$data['min_total'] 			= substr($coupon_info['min_total'], 0, strripos($coupon_info['min_total'], '.'));
		$data['redemptions'] 		= $coupon_info['redemptions'];
		$data['customer_redemptions'] = $coupon_info['customer_redemptions'];
		$data['description'] 		= $coupon_info['description'];
		$data['validity'] 			= $validity;
		$data['fixed_date'] 		= (empty($coupon_info['fixed_date']) OR $coupon_info['fixed_date'] === '0000-00-00') ? '' : mdate('%d-%m-%Y', strtotime($coupon_info['fixed_date']));
		$data['fixed_from_time'] 	= (empty($coupon_info['fixed_from_time']) OR $coupon_info['fixed_from_time'] === '00:00:00') ? '' : mdate('%h:%i %a', strtotime($coupon_info['fixed_from_time']));
		$data['fixed_to_time'] 		= (empty($coupon_info['fixed_to_time']) OR $coupon_info['fixed_to_time'] === '00:00:00') ? '' : mdate('%h:%i %a', strtotime($coupon_info['fixed_to_time']));
		$data['period_start_date'] 	= (empty($coupon_info['period_start_date']) OR $coupon_info['period_start_date'] === '0000-00-00') ? '' : mdate('%d-%m-%Y', strtotime($coupon_info['period_start_date']));
		$data['period_end_date'] 	= (empty($coupon_info['period_end_date']) OR $coupon_info['period_end_date'] === '0000-00-00') ? '' : mdate('%d-%m-%Y', strtotime($coupon_info['period_end_date']));
		$data['recurring_every'] 	= (empty($coupon_info['recurring_every'])) ? array() : explode(', ', $coupon_info['recurring_every']);
		$data['recurring_from_time'] = (empty($coupon_info['recurring_from_time']) OR $coupon_info['recurring_from_time'] === '00:00:00') ? '' : mdate('%h:%i %a', strtotime($coupon_info['recurring_from_time']));
		$data['recurring_to_time'] 	= (empty($coupon_info['recurring_to_time']) OR $coupon_info['recurring_to_time'] === '00:00:00') ? '' : mdate('%h:%i %a', strtotime($coupon_info['recurring_to_time']));
		$data['date_added'] 		= $coupon_info['date_added'];
		$data['status'] 			= $coupon_info['status'];

		$data['weekdays'] = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

		$data['fixed_time'] 		= '24hours';
		if (isset($coupon_info['fixed_from_time'], $coupon_info['fixed_to_time']) AND ($coupon_info['fixed_from_time'] !== '00:00:00' OR $coupon_info['fixed_to_time'] !== '23:59:00')) {
			$data['fixed_time'] 	= 'custom';
		}

		$data['recurring_time'] 		= '24hours';
		if (isset($coupon_info['recurring_from_time'], $coupon_info['recurring_to_time']) AND ($coupon_info['recurring_from_time'] !== '00:00:00' OR $coupon_info['recurring_to_time'] !== '23:59:00')) {
			$data['recurring_time'] 	= 'custom';
		}

		$data['coupon_histories'] = array();
		$coupon_histories = $this->Coupons_model->getCouponHistories($coupon_id);
		foreach ($coupon_histories as $coupon_history) {
			$data['coupon_histories'][] = array(
				'coupon_history_id'	=> $coupon_history['coupon_history_id'],
				'order_id'			=> $coupon_history['order_id'],
				'customer_name'		=> $coupon_history['first_name'] .' '. $coupon_history['last_name'],
				'amount'			=> $coupon_history['amount'],
				'date_used'			=> mdate('%d %M %y', strtotime($coupon_history['date_used'])),
				'view'				=> site_url('orders/edit?id='. $coupon_history['order_id'])
			);
		}

		if ($this->input->post() AND $coupon_id = $this->_saveCoupon()) {
			if ($this->input->post('save_close') === '1') {
				redirect('coupons');
			}

			redirect('coupons/edit?id='. $coupon_id);
		}

		$this->template->setPartials(array('header', 'footer'));
		$this->template->render('coupons_edit', $data);
	}

    private function _saveCoupon() {
    	if ($this->validateForm() === TRUE) {
            $save_type = ( ! is_numeric($this->input->get('id'))) ? 'added' : 'updated';

			if ($coupon_id = $this->Coupons_model->saveCoupon($this->input->get('id'), $this->input->post())) {
				$this->alert->set('success', 'Coupon ' . $save_type . ' successfully.');
			} else {
				$this->alert->set('warning', 'An error occurred, ' . $save_type . ' updated.');
			}

			return $coupon_id;
		}
	}

    private function _deleteCoupon() {
    	if (is_array($this->input->post('delete'))) {
			foreach ($this->input->post('delete') as $key => $value) {
				$this->Coupons_model->deleteCoupon($value);
			}

			$this->alert->set('success', 'Coupon(s) deleted successfully!');
		}

		return TRUE;
	}

    private function validateForm() {
		$this->form_validation->set_rules('name', 'Coupon Name', 'xss_clean|trim|required|min_length[2]|max_length[128]');
		$this->form_validation->set_rules('code', 'Code', 'xss_clean|trim|required|min_length[2]|max_length[15]');
		$this->form_validation->set_rules('type', 'Type', 'xss_clean|trim|required|exact_length[1]');
		$this->form_validation->set_rules('discount', 'Discount', 'xss_clean|trim|required|numeric');

		if ($this->input->post('type') === 'P') {
			$this->form_validation->set_rules('discount', 'Discount', 'less_than[100]');
		}

		$this->form_validation->set_rules('min_total', 'Minimum Total', 'xss_clean|trim|numeric');
		$this->form_validation->set_rules('redemptions', 'Redemptions', 'xss_clean|trim|integer');
		$this->form_validation->set_rules('customer_redemptions', 'Customer Redemptions', 'xss_clean|trim|integer');
		$this->form_validation->set_rules('description', 'Description', 'xss_clean|trim|min_length[2]|max_length[1028]');
		$this->form_validation->set_rules('validity', 'Validity', 'xss_clean|trim|required');

		if ($this->input->post('validity') === 'fixed') {
			$this->form_validation->set_rules('validity_times[fixed_date]', 'Fixed date', 'xss_clean|trim|required|valid_date');
			$this->form_validation->set_rules('fixed_time', 'Fixed time', 'xss_clean|trim|required');

			if ($this->input->post('fixed_time') !== '24hours') {
				$this->form_validation->set_rules('validity_times[fixed_from_time]', 'Fixed from time', 'xss_clean|trim|required|valid_time');
				$this->form_validation->set_rules('validity_times[fixed_to_time]', 'Fixed to time', 'xss_clean|trim|required|valid_time');
			}
		} else if ($this->input->post('validity') === 'period') {
			$this->form_validation->set_rules('validity_times[period_start_date]', 'Period start date', 'xss_clean|trim|required|valid_date');
			$this->form_validation->set_rules('validity_times[period_end_date]', 'Period end date', 'xss_clean|trim|required|valid_date');
		} else if ($this->input->post('validity') === 'recurring') {
			$this->form_validation->set_rules('validity_times[recurring_every]', 'Recurring every', 'xss_clean|trim|required');
			if (isset($_POST['validity_times']['recurring_every'])) {
				foreach ($_POST['validity_times']['recurring_every'] as $key => $value) {
					$this->form_validation->set_rules('validity_times[recurring_every]['.$key.']', 'Recurring every', 'xss_clean|required');
				}
			}

			$this->form_validation->set_rules('recurring_time', 'Recurring time', 'xss_clean|trim|required');
			if ($this->input->post('recurring_time') !== '24hours') {
				$this->form_validation->set_rules('validity_times[recurring_from_time]', 'Recurring from time', 'xss_clean|trim|required|valid_time');
				$this->form_validation->set_rules('validity_times[recurring_to_time]', 'Recurring to time', 'xss_clean|trim|required|valid_time');
			}
		}

		$this->form_validation->set_rules('status', 'Status', 'xss_clean|trim|required|integer');

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

/* End of file coupons.php */
/* Location: ./admin/controllers/coupons.php */