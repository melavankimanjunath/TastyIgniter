<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Reservations_model extends TI_Model {

    public function getCount($filter = array()) {
        if (APPDIR === ADMINDIR) {
            if (!empty($filter['filter_search'])) {
                $this->db->like('reservation_id', $filter['filter_search']);
                $this->db->or_like('LCASE(location_name)', strtolower($filter['filter_search']));
                $this->db->or_like('LCASE(first_name)', strtolower($filter['filter_search']));
                $this->db->or_like('LCASE(last_name)', strtolower($filter['filter_search']));
                $this->db->or_like('LCASE(table_name)', strtolower($filter['filter_search']));
                $this->db->or_like('LCASE(staff_name)', strtolower($filter['filter_search']));
            }

            if (!empty($filter['filter_status'])) {
                $this->db->where('reservations.status', $filter['filter_status']);
            }

            if (!empty($filter['filter_location'])) {
                $this->db->where('reservations.location_id', $filter['filter_location']);
            }

            if (!empty($filter['filter_date'])) {
                $date = explode('-', $filter['filter_date']);
                $this->db->where('YEAR(reserve_date)', $date[0]);
                $this->db->where('MONTH(reserve_date)', $date[1]);

                if (isset($date[2])) {
                    $this->db->where('DAY(reserve_date)', $date[2]);
                }
            } else if (!empty($filter['filter_year']) AND !empty($filter['filter_month']) AND !empty($filter['filter_day'])) {
                $this->db->where('YEAR(reserve_date)', $filter['filter_year']);
                $this->db->where('MONTH(reserve_date)', $filter['filter_month']);
                $this->db->where('DAY(reserve_date)', $filter['filter_day']);
            } else if (!empty($filter['filter_year']) AND !empty($filter['filter_month'])) {
                $this->db->where('YEAR(reserve_date)', $filter['filter_year']);
                $this->db->where('MONTH(reserve_date)', $filter['filter_month']);
            }

            $this->db->join('locations', 'locations.location_id = reservations.location_id', 'left');
            $this->db->join('tables', 'tables.table_id = reservations.table_id', 'left');
            $this->db->join('staffs', 'staffs.staff_id = reservations.assignee_id', 'left');
        } else if (!empty($filter['customer_id']) AND is_numeric($filter['customer_id'])) {
            $this->db->where('customer_id', $filter['customer_id']);
        }

 		$this->db->from('reservations');
		return $this->db->count_all_results();
    }

	public function getList($filter = array()) {
		if (!empty($filter['page']) AND $filter['page'] !== 0) {
			$filter['page'] = ($filter['page'] - 1) * $filter['limit'];
		}

		if ($this->db->limit($filter['limit'], $filter['page'])) {
			$this->db->from('reservations');
			$this->db->join('tables', 'tables.table_id = reservations.table_id', 'left');
			$this->db->join('locations', 'locations.location_id = reservations.location_id', 'left');
			$this->db->join('statuses', 'statuses.status_id = reservations.status', 'left');

            if (APPDIR === ADMINDIR) {
                $this->db->join('staffs', 'staffs.staff_id = reservations.assignee_id', 'left');

                if (!empty($filter['filter_search'])) {
                    $this->db->like('reservation_id', $filter['filter_search']);
                    $this->db->or_like('LCASE(location_name)', strtolower($filter['filter_search']));
                    $this->db->or_like('LCASE(first_name)', strtolower($filter['filter_search']));
                    $this->db->or_like('LCASE(last_name)', strtolower($filter['filter_search']));
                    $this->db->or_like('LCASE(table_name)', strtolower($filter['filter_search']));
                    $this->db->or_like('LCASE(staff_name)', strtolower($filter['filter_search']));
                }

                if (!empty($filter['filter_status'])) {
                    $this->db->where('reservations.status', $filter['filter_status']);
                }

                if (!empty($filter['filter_location'])) {
                    $this->db->where('reservations.location_id', $filter['filter_location']);
                }

                if (!empty($filter['filter_date'])) {
                    $date = explode('-', $filter['filter_date']);
                    $this->db->where('YEAR(reserve_date)', $date[0]);
                    $this->db->where('MONTH(reserve_date)', $date[1]);

                    if (isset($date[2])) {
                        $this->db->where('DAY(reserve_date)', (int)$date[2]);
                    }

                } else if (!empty($filter['filter_year']) AND !empty($filter['filter_month']) AND !empty($filter['filter_day'])) {
                    $this->db->where('YEAR(reserve_date)', $filter['filter_year']);
                    $this->db->where('MONTH(reserve_date)', $filter['filter_month']);
                    $this->db->where('DAY(reserve_date)', $filter['filter_day']);
                } else if (!empty($filter['filter_year']) AND !empty($filter['filter_month'])) {
                    $this->db->where('YEAR(reserve_date)', $filter['filter_year']);
                    $this->db->where('MONTH(reserve_date)', $filter['filter_month']);
                }
            } else if (!empty($filter['customer_id']) AND is_numeric($filter['customer_id'])) {
                $this->db->where('customer_id', $filter['customer_id']);
            }

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

    public function getReservations($customer_id = FALSE) {
        if ($customer_id !== FALSE) {
            $this->db->from('reservations');
            $this->db->join('tables', 'tables.table_id = reservations.table_id', 'left');
            $this->db->join('statuses', 'statuses.status_id = reservations.status', 'left');
            $this->db->join('locations', 'locations.location_id = reservations.location_id', 'left');
            $this->db->order_by('reservation_id', 'ASC');

            $query = $this->db->get();
            $result = array();

            if ($query->num_rows() > 0) {
                $result = $query->result_array();
            }

            return $result;
        }
    }

    public function getReservation($reservation_id = FALSE, $customer_id = FALSE) {
		if ($reservation_id !== FALSE) {
			if (APPDIR === ADMINDIR) {
                $this->db->select('*, reservations.date_added, reservations.date_modified, reservations.status, tables.table_id, staffs.staff_id, locations.location_id');
                $this->db->join('staffs', 'staffs.staff_id = reservations.assignee_id', 'left');
            } else {
                $this->db->select('reservation_id, table_name, reservations.location_id, location_name, location_address_1, location_address_2, location_city, location_postcode, location_country_id, table_name, min_capacity, max_capacity, guest_num, occasion_id, customer_id, first_name, last_name, telephone, email, reserve_time, reserve_date, status_name, reservations.date_added, reservations.date_modified, reservations.status, comment, notify, ip_address, user_agent');
            }

            $this->db->join('tables', 'tables.table_id = reservations.table_id', 'left');
            $this->db->join('statuses', 'statuses.status_id = reservations.status', 'left');
            $this->db->join('locations', 'locations.location_id = reservations.location_id', 'left');

            $this->db->from('reservations');
            $this->db->where('reservation_id', $reservation_id);

            if (APPDIR === MAINDIR) {
                if ($customer_id !== FALSE) {
                    $this->db->where('customer_id', $customer_id);
                }

                $this->db->where('reservations.status >', '0');
            }

            $query = $this->db->get();

			if ($query->num_rows() > 0) {
				return $query->row_array();
			}
		}
	}

	public function getReservationDates() {
		$this->db->select('reserve_date, MONTH(reserve_date) as month, YEAR(reserve_date) as year');
		$this->db->from('reservations');
		$this->db->group_by('MONTH(reserve_date)');
		$this->db->group_by('YEAR(reserve_date)');
		$query = $this->db->get();
		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

	public function getTotalCapacityByLocation($location_id = FALSE) {
		$result = 0;

		$this->db->select_sum('tables.max_capacity', 'total_seats');

		if (!empty($location_id)) {
			$this->db->where('location_id', $location_id);
		}

		$this->db->from('location_tables');
		$this->db->join('tables', 'tables.table_id = location_tables.table_id', 'left');

		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			$row = $query->row_array();
			$result = $row['total_seats'];
		}

		return $result;
	}

	public function getTotalGuestsByLocation($location_id = FALSE, $date = FALSE) {
		$result = 0;

		$this->db->select_sum('reservations.guest_num', 'total_guest');
		//$this->db->where('status', (int)$this->config->item('reservation_status'));

		if (!empty($location_id)) {
			$this->db->where('location_id', $location_id);
		}

		if (!empty($date)) {
			$this->db->where('DATE(reserve_date)', $date);
		}

		$this->db->group_by('DAY(reserve_date)');
		$this->db->from('reservations');

		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			$row = $query->row_array();
			$result = $row['total_guest'];
		}

		return $result;
	}

    public function checkAvailability($data = array()) {

        $available_tables = $reserved_tables = array();

        $guest_tables = $this->getTablesByGuestNum($data['location'], $data['guest_num']);

        if ( ! $guest_tables OR empty($guest_tables)) {
            return 'NO_GUEST_TABLE';
        }

        if (!empty($data)) {
            $this->db->from('reservations');
        }

        if (isset($data['location'])) {
            $this->db->where('location_id', $data['location']);
        }

        if (is_array($guest_tables)) {
            $this->db->where_in('table_id', $guest_tables);
        }

        if (isset($data['reserve_date'])) {
            $this->db->where('reserve_date', mdate('%Y-%m-%d', strtotime($data['reserve_date'])));
        }

        if (isset($data['reserve_time'])) {
            $this->db->where('reserve_time', mdate('%h:%i:%s', strtotime($data['reserve_time'])));
        }

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $row = $query->row_array();

            if (in_array($row['table_id'], $guest_tables)) {
                $reserved_tables[] = $row['table_id'];
            }
        }

        $available_tables = array_diff($guest_tables, $reserved_tables);

        if ( ! $available_tables OR empty($available_tables)) {
            return 'NO_TABLE_AVAIL';
        }

        return array('tables' => $available_tables);
    }

    public function getTotalSeats($location_id) {
        $this->db->select_sum('tables.max_capacity', 'total_seats');
        $this->db->where('location_id', $location_id);
        $this->db->from('location_tables');
        $this->db->join('tables', 'tables.table_id = location_tables.table_id', 'left');

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            return $row['total_seats'];
        }
    }

    public function getTablesByGuestNum($location_id, $num) {

        $tables = array();

        if (isset($location_id, $num)) {
            $this->db->from('location_tables');
            $this->db->join('tables', 'tables.table_id = location_tables.table_id', 'left');

            $this->db->where('location_id', $location_id);
            $this->db->where('min_capacity <=', $num);
            $this->db->where('max_capacity >=', $num);
            $this->db->order_by('max_capacity', 'ASC');

            $query = $this->db->get();
            if ($query->num_rows() > 0) {
                foreach ($query->result_array() as $table) {
                    $tables[] = $table['table_id'];
                }
            }
        }

        return $tables;
    }

    public function updateReservation($update = array(), $status_id) {
		$query = FALSE;

		if (!empty($update['status'])) {
			$this->db->set('status', $update['status']);
		}

		if (!empty($update['assignee_id'])) {
			$this->db->set('assignee_id', $update['assignee_id']);
		}

		if (!empty($update['date_modified'])) {
			$this->db->set('date_modified', $update['date_modified']);
		}

		if (!empty($update['reservation_id'])) {
			$this->db->where('reservation_id', $update['reservation_id']);
			$query = $this->db->update('reservations');

			$this->load->model('Notifications_model');
			$notification = array('object' => 'reservation', 'object_id' => $update['reservation_id'],
				'actor_id' => $update['staff_id'], 'subject_id' => $update['status_id'],
			);

			if ((int) $status_id !== (int) $update['status_id']) {
				$notification['action'] = 'changed';
				$this->Statuses_model->addStatusHistory('reserve', $update);
				$this->Notifications_model->addNotification($notification);
			}

			if ($update['old_assignee_id'] !== $update['assignee_id']) {
				$this->load->model('Notifications_model');
				$notification['action'] = 'assigned';
				$notification['subject_id'] = $update['assignee_id'];
				$this->Notifications_model->addNotification($notification);
			}
		}

		return $query;
	}

	public function addReservation($add = array()) {
		$query = FALSE;

		if (!empty($add['location_id'])) {
			$this->db->set('location_id', $add['location_id']);
		}

		if (!empty($add['table_id'])) {
			$this->db->set('table_id', $add['table_id']);
		}

		if (!empty($add['customer_id'])) {
			$this->db->set('customer_id', $add['customer_id']);
		}

		if (!empty($add['guest_num'])) {
			$this->db->set('guest_num', $add['guest_num']);
		}

		if (!empty($add['reserve_date'])) {
			$this->db->set('reserve_date', $add['reserve_date']);
		}

		if (!empty($add['reserve_time'])) {
			$this->db->set('reserve_time', $add['reserve_time']);
		}

		if (!empty($add['date_added'])) {
			$this->db->set('date_added', $add['date_added']);
		}

		if (!empty($add['date_modified'])) {
			$this->db->set('date_modified', $add['date_modified']);
		}

		if (!empty($add['occasion_id'])) {
			$this->db->set('occasion_id', $add['occasion_id']);
		}

		if (!empty($add['customer_id'])) {
			$this->db->set('customer_id', $add['customer_id']);
		}

		if (!empty($add['first_name'])) {
			$this->db->set('first_name', $add['first_name']);
		}

		if (!empty($add['last_name'])) {
			$this->db->set('last_name', $add['last_name']);
		}

		if (!empty($add['email'])) {
			$this->db->set('email', $add['email']);
		}

		if (!empty($add['telephone'])) {
			$this->db->set('telephone', $add['telephone']);
		}

		if (!empty($add['comment'])) {
			$this->db->set('comment', $add['comment']);
		}

		if (!empty($add['user_agent'])) {
			$this->db->set('user_agent', $add['user_agent']);
		}

		if (!empty($add['ip_address'])) {
			$this->db->set('ip_address', $add['ip_address']);
		}

		if (!empty($add)) {
			if ($this->db->insert('reservations')) {
				$reservation_id = $this->db->insert_id();

				$notify = $this->_sendMail($reservation_id);
				$this->db->set('notify', $notify);

				$this->db->set('status', $this->config->item('reservation_status'));
				$this->db->where('reservation_id', $reservation_id);

				if ($this->db->update('reservations')) {
					$this->load->model('Statuses_model');
					$status = $this->Statuses_model->getStatus($this->config->item('reservation_status'));
					$reserve_history = array(
						'object_id' 	=> $reservation_id,
						'status_id' 	=> $status['status_id'],
						'notify' 		=> $notify,
						'comment' 		=> $status['comment'],
						'date_added' 	=> mdate('%Y-%m-%d %H:%i:%s', time())
					);

					$this->Statuses_model->addStatusHistory('reserve', $reserve_history);
				}

				$query = $reservation_id;
			}
		}

		return $query;
	}

	public function getMailData($reservation_id) {
		$data = array();

		$result = $this->getReservation($reservation_id);
		if ($result) {
			$this->load->library('country');

			$data['reserve_number'] 	= $result['reservation_id'];
			$data['reserve_link'] 		= root_url('main/reservations?id='. $result['reservation_id']);
			$data['reserve_time']		= mdate('%H:%i', strtotime($result['reserve_time']));
			$data['reserve_date']		= mdate('%l, %F %j, %Y', strtotime($result['reserve_date']));
			$data['reserve_guest'] 		= $result['guest_num'];
			$data['first_name'] 		= $result['first_name'];
			$data['last_name'] 			= $result['last_name'];
			$data['email'] 				= $result['email'];
			$data['signature'] 			= $this->config->item('site_name');
			$data['location_name']		= $result['location_name'];
		}

		return $data;
	}

	public function _sendMail($reservation_id) {
	   	$this->load->library('email');
		$this->load->library('mail_template');

		$notify = $send_mail = '0';

		$mail_data = $this->getMailData($reservation_id);
		if ($mail_data) {
			$this->email->initialize();

			$this->email->from($this->config->item('site_email'), $this->config->item('site_name'));

			if ($this->config->item('location_reserve_email') === '1' AND $this->location->getEmail()) {
				$this->email->cc($this->location->getEmail());
			}
            if ($this->config->item('customer_reserve_email') === 1 AND $this->config->item('location_reserve_email') === '1') {
                $this->email->to(strtolower($mail_data['email']));

                if ($this->location->getEmail()) {
                    $this->email->cc(strtolower($this->location->getEmail()));
                }

                $send_mail = '1';
            } else if ($this->config->item('customer_reserve_email') === 1 AND $this->config->item('location_reserve_email') !== '1') {
                $this->email->to(strtolower($mail_data['email']));
                $send_mail = '1';
            } else if ($this->config->item('customer_reserve_email') !== 1 AND $this->config->item('location_reserve_email') === '1' AND $this->location->getEmail()) {
                $this->email->to(strtolower($this->location->getEmail()));
                $send_mail = '1';
            }

            if ($send_mail === '1') {
                $message = $this->mail_template->parseTemplate('reservation', $mail_data);
                $this->email->subject($this->mail_template->getSubject());
                $this->email->message($message);

                if (!$this->email->send()) {
                    log_message('debug', $this->email->print_debugger(array('headers')));
                    $notify = '0';
                } else {
                    $notify = '1';
                }
            }
		}

		return $notify;
	}

	public function validateReservation($reservation_id) {
		if (!empty($reservation_id)) {
			$this->db->from('reservations');
			$this->db->where('reservation_id', $reservation_id);

			$query = $this->db->get();

			if ($query->num_rows() > 0) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function deleteReservation($reservation_id) {
		if (is_numeric($reservation_id)) {
			$this->db->where('reservation_id', $reservation_id);
			$this->db->delete('reservations');

			if ($this->db->affected_rows() > 0) {
				return TRUE;
			}
		}
	}
}

/* End of file reservations_model.php */
/* Location: ./system/tastyigniter/models/reservations_model.php */