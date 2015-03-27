<?php

error_reporting(E_ALL);
ini_set('display_errors','On');
ini_set('auto_detect_line_endings',TRUE);

/**
* Configuration Values
*/
$og_db_config = array(
						'host' => 'localhost',
						'user' => '',
						'pass' => '',
						'name' => ''
					);
					
$ee_db_config = array(
			'host' => 'localhost',
			'user' => '',
			'pass' => '',
			'name' => ''
		);

// the OpenGateway client ID, likely "1000"
$client_id = '1000';

// the gateway ID to use for all subscriptions
$gateway_id = '1';

/**
* END Configuration
*/

/**
* MySQL Connections
*
* Creates 2 open MySQL connections, one for EE and the other for OpenGateway
*/

$ee_db = mysql_connect($ee_db_config['host'], $ee_db_config['user'], $ee_db_config['pass'], TRUE);
mysql_select_db($ee_db_config['name'], $ee_db);

$og_db = mysql_connect($og_db_config['host'], $og_db_config['user'], $og_db_config['pass'], TRUE);
mysql_select_db($og_db_config['name'], $og_db);

/**
* Initialize Class
*/
$importer = new Importer($ee_db, $og_db, $client_id, $gateway_id);

/**
* Procedure
*
* This code should load the CSV file, parse the values, and call the appropriate methods
* from the Import class below.
*
* This is unique for each import.
*/

// load the CSV file
if (($handle = fopen("./member_import.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    	// each value in this file has tags like <client>test</client>
        $member_id = strip_tags($data[0]);
        $sub_start = strtotime(strip_tags($data[1]));
        $sub_end = strtotime(strip_tags($data[2]));
        $plan_id = strip_tags($data[3]);
        $paid = money_format("%!i",strip_tags($data[4]));
        
        // get member data from EE
        $result = mysql_query('SELECT * FROM `exp_members` WHERE `member_id` = \'' . $member_id . '\'', $ee_db);
        
        if (mysql_num_rows($result) == 0) {
        	die('Unable to load member data for member_id: ' . $member_id);
        }
        
		// get member data into $member array
		$member = mysql_fetch_array($result, MYSQL_ASSOC);
		
		// generate first/last name from screen name
		$names = explode(' ', $member['screen_name']);
		$first_name = isset($names[0]) ? $names[0] : 'FirstName';
		$last_name = (end($names) != '' and end($names) != $first_name) ? end($names) : 'LastName';
        
        // create a customer record for the user, unless there is one
        $result = mysql_query('SELECT `customer_id` FROM `customers` WHERE `internal_id`=\'' . $member_id . '\'', $og_db);
        
        if (mysql_num_rows($result) > 0) {
        	$customer = mysql_fetch_array($result, MYSQL_ASSOC);
        	$customer_id = $customer['customer_id'];
        }
        else {
	        $customer_id = $importer->create_customer($member_id, $member['email'], $first_name, $last_name);
	    }
        
        // create subscription record
        $recurring_id = $importer->create_subscription($customer_id, $plan_id, 'membrr', $sub_start, $sub_end, $paid);
        
        echo 'Imported member id #' . $member_id . ' as customer #' . $customer_id . ' with subscription #' . $recurring_id . '.<br />';
    }
    fclose($handle);
}
else {
	die('Unable to open CSV file.');
}
		
/**
* Importer Class 
*
* Creates records in OpenGateway and Membrr to import subscriptions from a data source
*
* @author Electric Function, Inc.
*/
class Importer {
	var $ee_db;
	var $og_db;
	var $client_id;
	var $gateway_id;
	
	function __construct (&$ee_db, &$og_db, $client_id, $gateway_id) {
		$this->ee_db = $ee_db;
		$this->og_db = $og_db;
		$this->client_id = $client_id;
		$this->gateway_id = $gateway_id;
	}

	/**
	* Create Customer
	*
	* @param int $member_id
	* @param string $email
	* @param string $first_name
	* @param string $last_name
	*
	* @return int $customer_id
	*/
	function create_customer ($member_id, $email, $first_name, $last_name) {
		mysql_query('INSERT INTO `customers` (`client_id`,
											  `first_name`,
											  `last_name`,
											  `company`,
											  `internal_id`,
											  `address_1`,
											  `address_2`,
											  `city`,
											  `state`,
											  `postal_code`,
											  `country`,
											  `phone`,
											  `email`,
											  `active`,
											  `date_created`
											  ) VALUES (
											  \'' . $this->client_id . '\',
											  \'' . $first_name . '\',
											  \'' . $last_name . '\',
											  \'\',
											  \'' . $member_id . '\',
											  \'\',
											  \'\',
											  \'\',
											  \'\',
											  \'\',
											  \'\',
											  \'\',
											  \'' . $email . '\',
											  \'1\',
											  \'' . date('Y-m-d H:i:s') . '\');', $this->og_db);
											  
		$customer_id = mysql_insert_id($this->og_db);
		
		return $customer_id;
	}
	
	/**
	* Create Subscription
	*
	* Creates a Membrr/OG subscription
	*
	* @param int $customer_id The OG customer ID
	* @param int $plan_id The plan ID (either from OpenGateway or Membrr, specified below)
	* @param string $plan_type Either "opengateway" or "membrr", depending on which ID this refers to
	* @param date $start_date
	* @param date $end_date
	* @param float $amount (Optional) Specify the amount paid for the sub, else it's taken from the OG plans database
	*
	* @return int $recurring_id
	*/
	function create_subscription ($customer_id, $plan_id, $plan_type = 'opengateway', $start_date, $end_date, $amount = FALSE) {
		// if we are using a Membrr plan, we need the API plan ID
		if (strtolower($plan_type) == 'membrr') {
			$result = mysql_query('SELECT * FROM `exp_membrr_plans` WHERE `plan_id` = \'' . $plan_id . '\'', $this->ee_db) or die(mysql_error());
			
			if (mysql_num_rows($result) == 0) {
				die('We were told that the plan ID we received corresponded with a Membrr plan, but we couldn\'t retrieve any plan info for plan ID #' . $plan_id . ' from `exp_membrr_plans`.');
			}
			
			$plan = mysql_fetch_array($result, MYSQL_ASSOC);
			
			$plan_id = $plan['api_plan_id'];
		}
		// now the $plan_id corresponds to the OpenGateway Plan ID
		
		// get the OG plan data (notification URL, interval, etc.)
		$result = mysql_query('SELECT * FROM `plans` WHERE `plan_id` = \'' . $plan_id . '\'', $this->og_db);
		
		if (mysql_num_rows($result) == 0) {
			die('We tried to get the OpenGateway plan data for plan ID #' . $plan_id . ' but returned no results.');
		}
		
		$plan = mysql_fetch_array($result, MYSQL_ASSOC);
		
		// make sure start and end dates are timestamps
		$start_date = (is_numeric($start_date)) ? $start_date : strtotime($start_date);
		$end_date = (is_numeric($end_date)) ? $end_date : strtotime($end_date);
		
		if (empty($start_date) or empty($end_date)) {
			die('We are having a date problem with customer #' . $customer_id . '\'s subscription creation.');
		}
		
		// get proper amount
		$amount = (!empty($amount)) ? $amount : money_format("%!i",$plan['amount']);
		
		// create OG subscription record
		mysql_query('INSERT INTO `subscriptions` (`client_id`,
												  `gateway_id`,
												  `customer_id`,
												  `plan_id`,
												  `notification_url`,
												  `start_date`,
												  `end_date`,
												  `last_charge`,
												  `next_charge`,
												  `number_charge_failures`,
												  `number_occurrences`,
												  `charge_interval`,
												  `amount`,
												  `api_customer_reference`,
												  `api_payment_reference`,
												  `api_auth_number`,
												  `active`,
												  `cancel_date`,
												  `timestamp`
												  ) VALUES (
												  \'' . $this->client_id . '\',
												  \'' . $this->gateway_id . '\',
												  \'' . $customer_id . '\',
												  \'' . $plan_id . '\',
												  \'' . $plan['notification_url'] . '\',
												  \'' . date('Y-m-d', $start_date) . '\',
												  \'' . date('Y-m-d', $end_date) . '\',
												  \'' . date('Y-m-d', $start_date) . '\',
												  \'' . date('Y-m-d', $end_date) . '\',
												  \'0\',
												  \'1\',
												  \'' . $plan['interval'] . '\',
												  \'' . $amount . '\',
												  \'\',
												  \'\',
												  \'\',
												  \'1\',
												  \'\',
												  \'' . date('Y-m-d', $start_date) . '\');', $this->og_db);
												  
		$recurring_id = mysql_insert_id($this->og_db);
		
		// create OpenGateway order record
		mysql_query('INSERT INTO `orders` (`client_id`,
										   `gateway_id`,
										   `customer_id`,
										   `subscription_id`,
										   `card_last_four`,
										   `amount`,
										   `customer_ip_address`,
										   `status`,
										   `timestamp`,
										   `refunded`,
										   `refund_date`
										   ) VALUES (
										   \'' . $this->client_id . '\',
										   \'' . $this->gateway_id . '\',
										   \'' . $customer_id . '\',
										   \'' . $recurring_id . '\',
										   \'\',
										   \'' . $amount . '\',
										   \'\',
										   \'1\',
										   \'' . date('Y-m-d H:i:s', $start_date) . '\',
										   \'0\',
										   \'0000-00-00 00:00:00\');', $this->og_db);
		
		$order_id = mysql_insert_id($this->og_db);		
		
		// create fake Order Authorization info
		mysql_query('INSERT INTO `order_authorizations` (`order_id`,
														 `tran_id`,
														 `authorization_code`,
														 `security_key`
														 ) VALUES (
														 \'' . $order_id . '\',
														 \'import\',
														 \'import_auth\',
														 \'import_key\');', $this->og_db);
													 
		// record Subscription in Membrr
		// get member_id
		$result = mysql_query('SELECT * FROM `customers` WHERE `customer_id`=\'' . $customer_id . '\'', $this->og_db);
		
		if (mysql_num_rows($result) == 0) {
			die('Unable to retrieve customer record for customer ID #' . $customer_id);
		}
		
		$customer = mysql_fetch_array($result, MYSQL_ASSOC);
		$member_id = $customer['internal_id'];
		
		// get membrr_plan_id
		$result = mysql_query('SELECT * FROM `exp_membrr_plans` WHERE `api_plan_id` =\'' . $plan_id . '\'', $this->ee_db) or die(mysql_error());
		
		$membrr_plan = mysql_fetch_array($result, MYSQL_ASSOC);
		$membrr_plan_id = $membrr_plan['plan_id'];
		
		// is this sub already cancelled?
		$cancel_date = ($end_date < time()) ? date('Y-m-d H:i:s', $end_date) : '0000-00-00 00:00:00';
		$cancelled = ($end_date < time()) ? '1' : '0';
		
		// automatically adjust other vars related to status
		$active = ($cancelled == '1') ? '0' : '1';
		$expiry_processed = ($cancelled == '1') ? '1' : '0';
		
		mysql_query('INSERT INTO `exp_membrr_subscriptions` (`recurring_id`,
															 `member_id`,
															 `plan_id`,
															 `subscription_price`,
															 `date_created`,
															 `date_cancelled`,
															 `next_charge_date`,
															 `end_date`,
															 `expired`,
															 `cancelled`,
															 `active`,
															 `expiry_processed`
															 ) VALUES (
															 \'' . $recurring_id . '\',
															 \'' . $member_id . '\',
															 \'' . $membrr_plan_id . '\',
															 \'' . $amount . '\',
															 \'' . date('Y-m-d H:i:s', $start_date) . '\',
															 \'' . $cancel_date . '\',
															 \'' . date('Y-m-d H:i:s', $end_date) . '\',
															 \'' . date('Y-m-d H:i:s', $end_date) . '\',
															 \'0\',
															 \'' . $cancelled . '\',
															 \'' . $active . '\',
															 \'' . $expiry_processed . '\');', $this->ee_db) or die(mysql_error());
															 
															 
															 
		$subscription_id = mysql_insert_id($this->ee_db);
		
		// move user into expiry group or active group if possible
		if ($cancelled == '1' and !empty($membrr_plan['plan_member_group_expire'])) {
			mysql_query('UPDATE `exp_members` SET `group_id`=\'' . $membrr_plan['plan_member_group_expire'] . '\' WHERE `member_id`=\'' . $member_id . '\'', $this->ee_db);
		}
		elseif ($cancelled == '0' and !empty($membrr_plan['plan_member_group'])) {
			mysql_query('UPDATE `exp_members` SET `group_id`=\'' . $membrr_plan['plan_member_group'] . '\' WHERE `member_id`=\'' . $member_id . '\'', $this->ee_db);
		}
															 		
		// record Payment in Membrr
		mysql_query('INSERT INTO `exp_membrr_payments` (`charge_id`,
														`recurring_id`,
														`amount`,
														`date`
														) VALUES (
														\'' . $order_id . '\',
														\'' . $recurring_id . '\',
														\'' . $amount . '\',
														\'' . date('Y-m-d H:i:s', $start_date) . '\');', $this->ee_db) or die(mysql_error());
														
		$payment_id = mysql_insert_id($this->ee_db);
		
		return $recurring_id;		
	}
}