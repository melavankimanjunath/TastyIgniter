<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Customer_groups_model extends TI_Model {

    public function getCount($filter = array()) {
		$this->db->from('customer_groups');
		return $this->db->count_all_results();
    }

	public function getList($filter = array()) {
		if (!empty($filter['page']) AND $filter['page'] !== 0) {
			$filter['page'] = ($filter['page'] - 1) * $filter['limit'];
		}

		if ($this->db->limit($filter['limit'], $filter['page'])) {
			$this->db->from('customer_groups');

			if (!empty($filter['sort_by']) AND !empty($filter['order_by'])) {
				$this->db->order_by($filter['sort_by'], $filter['order_by']);
			}

			$query = $this->db->get();
			$result = array();

			if ($query->num_rows() > 0) {
				$result = $query->result_array();
			}

			return $result;
		}
	}

	public function getCustomerGroups() {
		$this->db->from('customer_groups');

		$query = $this->db->get();
		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

	public function getCustomerGroup($customer_group_id) {
		$this->db->from('customer_groups');

		$this->db->where('customer_group_id', $customer_group_id);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
		}
	}

	public function saveCustomerGroup($customer_group_id, $save = array()) {
        if (empty($save)) return FALSE;

		if (!empty($save['group_name'])) {
			$this->db->set('group_name', $save['group_name']);
		}

		if (!empty($save['description'])) {
			$this->db->set('description', $save['description']);
		}

		if ($save['approval'] === '1') {
			$this->db->set('approval', $save['approval']);
		} else {
			$this->db->set('approval', '0');
		}

		if (is_numeric($customer_group_id)) {
			$this->db->where('customer_group_id', $customer_group_id);
			$query = $this->db->update('customer_groups');
		} else {
            $query = $this->db->insert('customer_groups');
            $customer_group_id = $this->db->insert_id();
        }

        return ($query === TRUE AND is_numeric($customer_group_id)) ? $customer_group_id : FALSE;
	}

	public function deleteCustomerGroup($customer_group_id) {
		if (is_numeric($customer_group_id)) {
			$this->db->where('customer_group_id', $customer_group_id);
			$this->db->delete('customer_groups');

			if ($this->db->affected_rows() > 0) {
				return TRUE;
			}
		}
	}
}

/* End of file customer_groups_model.php */
/* Location: ./system/tastyigniter/models/customer_groups_model.php */