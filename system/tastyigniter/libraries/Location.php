<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Location {
	private $locations = array();
	private $location_id;
	private $location_name;
	private $location_email;
	private $location_telephone;
	private $local_options;
	private $local_info;
	private $search_query;
	private $area_id;
	private $order_type;
	private $polygons = array();
	private $delivery_areas = array();
	private $opening_hours = array();
	private $opening_hour = array();
	private $opening_time = '00:00';
	private $closing_time = '23:59';
	private $opening_status;
	private $opened = FALSE;
	private $datestring = "%Y/%m/%d";
	private $timestring = "%H:%i";
	private $current_day;
	private $current_date;
	private $current_time;

	public function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->helper('date');
		$this->CI->load->model('Locations_model');

		$time = now();
		$this->current_day = date('l');
		$this->current_date = mdate($this->datestring, $time);
		$this->current_time = mdate($this->timestring, $time);

		$this->getLocations();
		$this->getOpeningHours();

		$local_info = $this->CI->session->userdata('local_info');
		if (is_array($local_info) AND !empty($local_info)) {
			$this->initialize($local_info);
		}
	}

	public function initialize($local_info) {
		if (isset($this->locations[$local_info['location_id']]) AND is_array($this->locations[$local_info['location_id']])) {
			$result = $this->locations[$local_info['location_id']];

			$this->location_id 			= $result['location_id'];
			$this->location_name 		= $result['location_name'];
			$this->location_email 		= $result['location_email'];
			$this->location_telephone 	= $result['location_telephone'];
			$this->local_options 		= (!empty($result['options'])) ? unserialize($result['options']) : array();
			$this->local_info 			= $result;
			$this->search_query 		= (empty($local_info['search_query'])) ? '' : $local_info['search_query'];
			$this->area_id 				= (empty($local_info['area_id'])) ? '' : $local_info['area_id'];
			$this->order_type 			= (isset($local_info['order_type'])) ? $local_info['order_type'] : '1';

			$this->setLocationOpeningHours();
			$this->setDeliveryAreas();
		} else {
			$this->clearLocal();
		}
	}

	public function currentDate() {
		return $this->current_date;
	}

	public function currentTime() {
		return $this->current_time;
	}

	public function getId() {
		return $this->location_id;
	}

	public function getName() {
		return $this->location_name;
	}

	public function getEmail() {
		return strtolower($this->location_email);
	}

	public function getTelephone() {
		return $this->location_telephone;
	}

	public function getDescription() {
		return $this->local_info['description'];
	}

	public function getAddress($format = TRUE) {
		$location_address = array(
			'address_1'      => $this->local_info['location_address_1'],
			'address_2'      => $this->local_info['location_address_2'],
			'city'           => $this->local_info['location_city'],
			'state'          => $this->local_info['location_state'],
			'postcode'       => $this->local_info['location_postcode']
		);
		$this->CI->load->library('country');
		$address = $this->CI->country->addressFormat($location_address);

		if ($format === FALSE) {
			$address = str_replace('<br />', ', ', $address);
		}

		return $address;
	}

	public function getMainLocal() {
		$main_address = $this->CI->config->item('main_address');

		if (!empty($main_address) AND is_array($main_address)) {
			if (isset($this->locations[$main_address['location_id']])
				AND is_array($this->locations[$main_address['location_id']])) {

				$location = $this->locations[$main_address['location_id']];

				return $location;
			}
		}
	}

	public function getFormatAddress($address = array(), $format = TRUE) {
		if (!empty($address) AND is_array($address)) {
			$location_address = array(
				'address_1'      => $address['location_address_1'],
				'address_2'      => $address['location_address_2'],
				'city'           => $address['location_city'],
				'state'          => $address['location_state'],
				'postcode'       => $address['location_postcode']
			);

			$this->CI->load->library('country');
			$address = $this->CI->country->addressFormat($location_address);

			if ($format === FALSE) {
				$address = str_replace('<br />', ', ', $address);
			}
		}

		return $address;
	}

	public function local() {
		return $this->local_info;
	}

	public function getOpeningType() {
		return (!isset($this->local_options['opening_hours']['opening_type'])) ? '' : $this->local_options['opening_hours']['opening_type'];
	}

	public function openingHours($location_id = '', $weekday = '') {
		$opening_hours = array();

		if (is_numeric($location_id) AND isset($this->opening_hours[$location_id])) {
			$opening_hours = $this->opening_hours[$location_id];

			if (!empty($weekday)) {
				foreach ($this->opening_hours[$location_id] as $hour) {
					if (isset($hour['day']) AND $hour['day'] == $weekday) {
						$opening_hours = $hour;
					}
				}
			}
		}

		if (empty($opening_hours) AND isset($this->opening_hours[$this->location_id])) {
			$opening_hours = $this->opening_hours[$this->location_id];
		}

		return $opening_hours;
	}

	public function openingStatus() {
		return $this->opening_status;
	}

	public function openingTime() {
		return $this->opening_time;
	}

	public function closingTime() {
		return $this->closing_time;
	}

	public function isOpened() {
		return ($this->opening_status !== '1' AND ($this->opening_time <= $this->current_time AND $this->closing_time >= $this->current_time) OR ($this->opening_time === '00:00' AND $this->closing_time === '00:00'));
	}

	public function hasDelivery() {
    	return (!empty($this->local_info['offer_delivery']) AND $this->local_info['offer_delivery'] === '1') ? TRUE : FALSE;
	}

	public function hasCollection() {
    	return (!empty($this->local_info['offer_collection']) AND $this->local_info['offer_collection'] === '1') ? TRUE : FALSE;
	}

	public function orderType() {
		return $this->order_type;
	}

	public function searchQuery() {
		return $this->search_query;
	}

	public function deliveryAreas() {
		return $this->delivery_areas;
	}

	public function deliveryCharge() {
    	return (!empty($this->delivery_areas[$this->area_id]['charge'])) ? $this->delivery_areas[$this->area_id]['charge'] : 0;
	}

	public function minimumOrder() {
    	return (!empty($this->delivery_areas[$this->area_id]['min_amount'])) ? $this->delivery_areas[$this->area_id]['min_amount'] : 0;
	}

	public function getReservationInterval() {
    	return (!empty($this->local_info['reservation_interval'])) ? $this->local_info['reservation_interval'] : $this->CI->config->item('reservation_interval');
	}

	public function deliveryTime() {
		return (!empty($this->local_info['delivery_time'])) ? $this->local_info['delivery_time'] : $this->CI->config->item('delivery_time');
	}

	public function collectionTime() {
		return (!empty($this->local_info['collection_time'])) ? $this->local_info['collection_time'] : $this->CI->config->item('collection_time');
	}

	public function lastOrderTime() {
		return (is_numeric($this->local_info['last_order_time']) AND $this->local_info['last_order_time'] > 0) ? mdate($this->timestring, strtotime($this->closing_time) - ($this->local_info['last_order_time'] * 60)) : $this->closing_time;
	}

	public function payments($implode = FALSE) {
		$payments = (!empty($this->local_options['payments'])) ? $this->local_options['payments'] : NULL;

        return ($payments AND $implode) ? implode($payments, $implode) : $payments;
	}

	public function checkDeliveryTime($time) {
		$time = mdate($this->timestring, strtotime($time));
    	return (($this->opening_status !== '1' AND $this->opening_time <= $time AND $this->closing_time >= $time) OR ($this->opening_time === '00:00' AND $this->closing_time === '00:00'));
	}

	public function setLocation($location_id) {
		if (is_numeric($location_id)) {
			$local_info = $this->CI->session->userdata('local_info');
			if (is_array($local_info) AND !empty($local_info)) {
				$local_info['location_id'] = $location_id;
				$this->CI->session->set_userdata('local_info', $local_info);
			}
		}
	}

	public function setDeliveryAreas() {
		if (isset($this->local_options['delivery_areas']) AND is_array($this->local_options['delivery_areas'])) {
			foreach ($this->local_options['delivery_areas'] as $area_id => $area) {
				$this->delivery_areas[$area_id] = array(
					'area_id'		=> $area_id,
					'name'			=> $area['name'],
					'charge'		=> $area['charge'],
					'min_amount'	=> $area['min_amount']
				);
			}
		}
	}

	public function setOrderType($order_type) {
		if (is_numeric($order_type)) {
			$local_info = $this->CI->session->userdata('local_info');
			if (is_array($local_info) AND !empty($local_info)) {
				$local_info['order_type'] = $order_type;
				$this->CI->session->set_userdata('local_info', $local_info);
			}
		}
	}

	public function setLocationOpeningHours() {
		if (isset($this->opening_hours[$this->location_id]) AND is_array($this->opening_hours[$this->location_id])) {

			foreach ($this->opening_hours[$this->location_id] as $hour) {
				if ($this->current_day === $hour['day']) {
					$this->opening_time 	= $hour['open'];
					$this->closing_time 	= $hour['close'];
					$this->opening_status 	= $hour['status'];
				}
			}
		}
	}

	public function checkMinimumOrder($cart_total) {
		return ($cart_total >= $this->minimumOrder());
	}

	public function checkDeliveryCoverage($search_query = FALSE) {
        $search_query = ($search_query === FALSE) ? $this->search_query : $search_query;
        $coords = $this->getLatLng($search_query, TRUE);
		$delivery_area = $this->checkDeliveryArea($coords);

		if ($delivery_area !== 'outside' AND $delivery_area['location_id'] == $this->location_id) {
			return $delivery_area;
		}

		return FALSE;
	}

	public function searchRestaurant($search_query = FALSE) {																// method to perform regular expression match on postcode string and return latitude and longitude
		$this->CI->session->unset_userdata('local_info');

		$output = $this->getLatLng($search_query, TRUE);
		if ($output === 'NO_SEARCH_QUERY' OR $output === 'ENTER_POSTCODE' OR $output === 'FAILED') {
			return $output;
		}

		$delivery_area = $this->checkDeliveryArea($output);
		if ($delivery_area !== FALSE AND count($delivery_area) == 2) {
			$local_info = array('location_id' => $delivery_area['location_id'], 'area_id' => $delivery_area['area_id'], 'search_query' => $output['search_query']);
			$this->CI->session->set_userdata('local_info', $local_info);
		}

		return $delivery_area;
	}

	public function checkDeliveryArea($coords) {
		$location = $point = '';
		$delivery_area = array();

		if (is_array($coords) AND count($coords) == 3) {
			$point = $coords['lat'].'|'.$coords['lng'];

			$polygons = $this->getPolygons();
			foreach ($polygons as $location_id => $areas) {
				foreach ($areas as $area_id => $area) {
					if (isset($area['vertices']) AND isset($area['circle']) AND isset($area['type'])) {
						if ($area['type'] == 'shape') {
							$location = ($this->pointInPolygon($point, $area['vertices']) === 'outside') ? '' : $location_id;
						} else if ($area['type'] == 'circle') {
							$location = ($this->pointInCircle($point, $area['circle']) === FALSE) ? '' : $location_id;
						}
					}

					if ($location !== '') {
						$delivery_area = array('location_id' => $location_id, 'area_id' => $area_id);
						break 2;
					}
				}
			}
		}

		return (empty($delivery_area)) ? 'outside' : $delivery_area;
	}

    public function pointInPolygon($point, $vertices = array(), $pointOnVertex = TRUE) {
        // Transform string coordinates into arrays with x and y values
        $point = $this->_coordsStringToArray($point);

		// Check if the point sits exactly on a vertex
		if ($pointOnVertex === TRUE AND $this->_pointOnVertex($point, $vertices) === TRUE) {
			return "vertex";
		}

		// Check if the point is inside the polygon or on the boundary
		$intersections = 0;

		for ($i = 1; $i < count($vertices); $i++) {
			$vertex1 = $vertices[$i-1];
			$vertex2 = $vertices[$i];

			if ($vertex1['y'] == $vertex2['y']
			AND $vertex1['y'] == $point['y']
			AND $point['x'] > min($vertex1['x'], $vertex2['x'])
			AND $point['x'] < max($vertex1['x'], $vertex2['x'])) {
				return "boundary";
			}

			if ($point['y'] > min($vertex1['y'], $vertex2['y'])
			AND $point['y'] <= max($vertex1['y'], $vertex2['y'])
			AND $point['x'] <= max($vertex1['x'], $vertex2['x'])
			AND $vertex1['y'] != $vertex2['y']) {

				$xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];

				// Check if point is on the polygon boundary (other than horizontal)
				if ($xinters == $point['x']) {
					return "boundary";
				}

				if ($vertex1['x'] == $vertex2['x'] OR $point['x'] <= $xinters) {
					$intersections++;
				}
			}
		}

		// If the number of edges we passed through is odd, then it's in the polygon.
		if ($intersections % 2 != 0) {
			return "inside";
		} else {
			return "outside";
		}
    }

	public function pointInCircle($point, $circle = array()) {
        $point = $this->_coordsStringToArray($point);
        $center = $this->_coordsStringToArray($circle['center']);
		$earth_radius = ($this->CI->config->item('distance_unit') === 'km') ? 6371 : 3959;
		$radius = ($this->CI->config->item('distance_unit') === 'km') ? $circle['radius']/1000 : $circle['radius']/1609.344;

		$dLat = deg2rad($center['y'] - $point['y']);
		$dLon = deg2rad($center['x'] - $point['x']);

		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($point['y'])) * cos(deg2rad($center['y'])) * sin($dLon/2) * sin($dLon/2);
		$c = 2 * asin(sqrt($a));
		$distance = $earth_radius * $c;

		return ($distance <= $radius) ? TRUE : FALSE;
	}

    public function _pointOnVertex($point, $vertices) {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return TRUE;
            }
        }

    }

    public function _coordsStringToArray($coordsString) {
        $coordinates = explode('|', $coordsString);
        return array('y' => $coordinates[0], 'x' => $coordinates[1]);
    }

    public function getLocations() {
		if (empty($this->locations)) {
			$this->CI->db->where('location_status', '1');
			$query = $this->CI->db->get('locations');

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $result) {
					$locations[$result['location_id']] = $result;
				}

				$this->locations = $locations;
			}
		}

		return $this->locations;
	}

    public function getOpeningHours() {
		if (empty($this->opening_hours)) {
			$query = $this->CI->db->get('working_hours');

			if ($query->num_rows() > 0) {
				$weekdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
				foreach ($query->result_array() as $result) {
					$opening_hours[$result['location_id']][] = array(
						'location_id'	=> $result['location_id'],
						'day'			=> $weekdays[$result['weekday']],
						'open'			=> ($result['opening_time'] === '00:00:00') ? '00:00' : mdate($this->timestring, strtotime($result['opening_time'])),
						'close'			=> ($result['closing_time'] === '00:00:00') ? '00:00' : mdate($this->timestring, strtotime($result['closing_time'])),
						'status'		=> $result['status']
					);
				}

				$this->opening_hours = $opening_hours;
			}
		}

		return $this->opening_hours;
	}

    public function getPolygons() {
		if (empty($this->polygons)) {
			$polygons = array();

			foreach ($this->locations as $result) {
				$options = (!empty($result['options'])) ? unserialize($result['options']) : array();

				if (!empty($options['delivery_areas']) AND is_array($options['delivery_areas'])) {
					foreach ($options['delivery_areas'] as $key => $area) {
						$vertices = (!empty($area['vertices'])) ? json_decode($area['vertices']) : array();
						$circle = (!empty($area['circle'])) ? json_decode($area['circle']) : array();

						foreach ($vertices as $vertex) {
							$polygons[$result['location_id']][$key]['vertices'][] = array('x' => $vertex->lng, 'y' => $vertex->lat);
						}

						if (isset($circle[0]->center) AND isset($circle[1]->radius)) {
							$polygons[$result['location_id']][$key]['circle'] = array('center' => $circle[0]->center->lat.'|'.$circle[0]->center->lng, 'radius' => $circle[1]->radius);
						}

						$polygons[$result['location_id']][$key]['type'] = $area['type'];
					}
				}
			}

			$this->polygons = $polygons;
    	}

    	return $this->polygons;
    }

	public function generateHours($start_hour, $end_hour, $interval) {
		$hours = array();
		$start_formatted = strtotime($start_hour);
		$end_formatted = strtotime($end_hour);
		$interval = $interval * 60;

		if ($start_formatted < $end_formatted) {

			for ($i = ($start_formatted + $interval); $i <= ($end_formatted); $i += $interval) {
				$hr24 = mdate($this->timestring, $i);
				$hours[] = $hr24;
			}
  		}

  		$hours[] = mdate($this->timestring, $end_formatted);
  		return $hours;
	}

	public function getLatLng($search_query = FALSE, $check_setting = FALSE) {																// method to perform regular expression match on postcode string and return latitude and longitude
		if (empty($search_query)) {
			return "NO_SEARCH_QUERY";
		}

		if (is_string($search_query)) {
			$postcode = strtoupper(str_replace(' ', '', $search_query));								// strip spaces from postcode string and convert to uppercase

			if (preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/", $postcode) OR
			preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/", $postcode) OR
			preg_match("/^GIR0[A-Z]{2}$/", $postcode)) {
				$search_query = $postcode;
				$is_postcode = TRUE;
			} else {
				$search_query = explode(' ', $search_query);
				$is_postcode = FALSE;
			}
		}

		if (is_array($search_query)) {
			$address_string =  implode(', ', $search_query);
			$search_query = $address_string;
		}

        if ($check_setting === TRUE) {
            $search_by = $this->CI->config->item('search_by');
            if ($search_by === 'postcode' AND $is_postcode === FALSE) {
                return "ENTER_POSTCODE";
            }
        }

        $url  = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($search_query) .'&sensor=false'; //encode $postcode string and construct the url query
		$geocode_data = @file_get_contents($url);
		$output = json_decode($geocode_data);											// decode the geocode data

		if ($output AND $output->status === 'OK') {														// create variable for geocode data status
			return array(
				'search_query'	=> $search_query,
				'lat' 			=> $output->results[0]->geometry->location->lat,
				'lng' 			=> $output->results[0]->geometry->location->lng
			);
		} else {
			return "FAILED";
		}
	}

	public function clearLocal() {
		$this->location_id = '';
		$this->location_email = '';
		$this->local_options = '';
		$this->local_info = '';
		$this->search_query = '';
		$this->opening_hours = '';
		$this->opening_time = '';
		$this->closing_time = '';
		$this->opened = FALSE;
		$this->current_day = '';
		$this->current_date = '';
		$this->current_time = '';
	}
}

// END Location Class

/* End of file Location.php */
/* Location: ./system/tastyigniter/libraries/Location.php */