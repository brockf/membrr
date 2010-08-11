<?php

/*
=====================================================
 Membrr for EE
-----------------------------------------------------
 http://www.membrr.com/
-----------------------------------------------------
 Copyright (c) 2010, Electric Function, Inc.
 Use this software at your own risk.  Electric
 Function, Inc. assumes no responsibility for
 loss or damages as a result of using this software.
 
 This software is copyrighted.
=====================================================
*/

if (!class_exists('Membrr_EE')) {
	class Membrr_EE {
		var $cache;
		
		// constructor, with maintenance	
		function Membrr_EE () {	
			$this->EE =& get_instance();
			
			// deal with channel protection expirations	
			if ($this->EE->db->table_exists('exp_membrr_subscriptions')) {					 
				$this->EE->db->where('(`end_date` <= NOW() and `end_date` != \'0000-00-00 00:00:00\') AND `exp_membrr_channel_posts`.`post_id` IS NOT NULL AND `exp_membrr_channel_posts`.`active` = \'1\'',NULL,FALSE);
				$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_channel_posts.recurring_id = exp_membrr_subscriptions.recurring_id','LEFT');
				$query = $this->EE->db->get('exp_membrr_subscriptions');
				
				foreach ($query->result_array() AS $row) {
					// we need to change the status of the post
					
					// get info on channel protection
					$channel = $this->Getchannel($row['channel_name'],'blog_name');
					
					// update the status
					$this->EE->db->update('exp_channel_ti`tles',array('status' => $channel['status_name']), array('entry_id' => $row['channel_entry_id']));
										  
					// delete the link
					$this->EE->db->update('exp_membrr_channel_posts',array('active' => '0'), array('post_id' => $row['post_id']));
				}
			}
			
			// deal with normal expirations and member usergroup moves
			if ($this->EE->db->table_exists('exp_membrr_subscriptions')) {	
				$this->EE->db->select('exp_membrr_subscriptions.member_id');
				$this->EE->db->select('exp_membrr_subscriptions.recurring_id');
				$this->EE->db->select('exp_membrr_subscriptions.plan_id');
				$this->EE->db->select('exp_membrr_plans.plan_member_group_expire');				 
				$this->EE->db->where('(`end_date` <= NOW() and `end_date` != \'0000-00-00 00:00:00\')',NULL,FALSE);
				$this->EE->db->where('exp_membrr_subscriptions.expiry_processed','0');
				$this->EE->db->join('exp_membrr_plans','exp_membrr_plans.plan_id = exp_membrr_subscriptions.plan_id','LEFT');
				$query = $this->EE->db->get('exp_membrr_subscriptions');
				
				foreach ($query->result_array() AS $row) {
					if ($row['plan_member_group_expire'] > 0) {
						// move to a new usergroup?
						$this->EE->db->where('member_id',$row['member_id']);
						$this->EE->db->where('group_id !=','1');
						$this->EE->db->update('exp_members',array('group_id' => $row['plan_member_group_expire']));
					}
					
					// call "membrr_expire" hook with: member_id, subscription_id, plan_id
					if (isset($this->EE->extensions->extensions['membrr_expire']))
					{
					    $this->EE->extensions->call_extension('membrr_expire', $row['member_id'], $row['recurring_id'], $row['plan_id']);
					    if ($this->EE->extensions->end_script === TRUE) return;
					} 
					
					// this expiry is processed
					$this->EE->db->where('recurring_id',$row['recurring_id']);
					$this->EE->db->update('exp_membrr_subscriptions',array('expiry_processed' => '1'));
				}
			}
		}
	
		function Subscribe ($plan_id, $member_id, $credit_card, $customer, $end_date = FALSE, $first_charge = FALSE, $recurring_charge = FALSE, $cancel_url = '', $return_url = '', $gateway_id = FALSE) {
			$plan = $this->GetPlan($plan_id);
			
			// calculate initial charge
			if ($plan['initial_charge'] != $plan['price'] and $first_charge === FALSE) {
				$first_charge = $plan['initial_charge'];
			}	
			
			$config = $this->GetConfig();
			
			if (!class_exists('Opengateway')) {
				require(dirname(__FILE__) . '/opengateway.php');
			}
			
			$connect_url = $config['api_url'] . '/api';
			$recur = new Recur;
			$recur->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			
			// use existing customer_id if we can
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('GetCustomers');
			$opengateway->Param('internal_id', $member_id);
			$response = $opengateway->Process();
			
			if ($response['total_results'] > 0) {
				// there is already a customer record here
				$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
				
				$recur->UseCustomer($customer['id']);
			}
			else {
				// no customer records, yet
				$recur->Param('internal_id', $member_id, 'customer');
				$recur->Customer($customer['first_name'],$customer['last_name'],'',$customer['address'],$customer['address_2'],$customer['city'],$customer['region'],$customer['country'],$customer['postal_code'],'',$customer['email']);
			}
			
			// end date?
			if ($end_date != FALSE) {
				$recur->Param('end_date', $end_date, 'recur');
			}
			
			// if different first charge?
			if ($first_charge !== FALSE) {
				$recur->Amount($first_charge);
			}
			else {
				$recur->Amount($plan['price']);
			}
			
			// if different recurring rate?
			if ($recurring_charge != FALSE) {
				$recur->Param('amount', $recurring_charge, 'recur');
			}
			
			$recur->UsePlan($plan['api_id']);
			
			$security = (empty($credit_card['security_code'])) ? FALSE : $credit_card['security_code'];
			if ($credit_card and !empty($credit_card) and isset($credit_card['number']) and !empty($credit_card['number'])) {
				$recur->CreditCard($credit_card['name'], $credit_card['number'], $credit_card['expiry_month'], $credit_card['expiry_year'], $security);
			}
			
			// specify the gateway?
			// from arguments first:
			if (!empty($gateway_id)) {
				$recur->Param('gateway_id',$gateway_id);
			}
			// from plan first:
			elseif (!empty($plan['gateway'])) {
				$recur->Param('gateway_id',$plan['gateway']);
			}
			// then from general settings:
			elseif (!empty($config['gateway'])) {
				$recur->Param('gateway_id',$config['gateway']);
			}
			
			// add IP address to request
			// get true IP
			if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				$current_ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$current_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$current_ip = $_SERVER['REMOTE_ADDR'];
			}
			$recur->Param('customer_ip_address',$current_ip);
			
			// if they are using PayPal, we need the following parameters
			if (empty($return_url)) {
				$this->EE->db->select('action_id');
				$this->EE->db->where('class','Membrr');
				$this->EE->db->where('method','post_notify');
				$result = $this->EE->db->get('exp_actions');
				$action_id = $result->row_array();
				$action_id = $action_id['action_id'];
			 	$return_url = $this->EE->functions->create_url('?ACT=' . $action_id . '&member=' . $member_id . '&plan_id=' . $plan_id, 0);
			}
			$recur->Param('return_url',htmlspecialchars($return_url));
			if (empty($cancel_url)) {
				$cancel_url = $this->EE->functions->fetch_current_uri();
			}
			$recur->Param('cancel_url',htmlspecialchars($cancel_url));
			
			// call "membrr_pre_subscribe" hook with: $recur, $member_id, $plan_id, $recurring_charge, $first_charge, $end_date, $next_charge_date
			
			if (isset($this->EE->extensions->extensions['membrr_pre_subscribe']))
			{
			    $this->EE->extensions->call_extension('membrr_pre_subscribe', $recur, $member_id, $plan_id, $recurring_charge, $first_charge, $end_date, $next_charge_date);
			    if ($this->EE->extensions->end_script === TRUE) return FALSE;
			}
			
			$response = $recur->Charge();
			
			if (isset($response['response_code']) and $response['response_code'] == '100') {
				// success!
				
				// calculate payment amount
				$recur_payment = ($recurring_charge == FALSE) ? $plan['price'] : money_format("%!i",$recurring_charge);
				
				// calculate end date
				if ($end_date != FALSE) {
					// we must also account for their signup time
					$time_created = date('H:i:s');
					$end_date = date('Y-m-d',strtotime($end_date)) . ' ' . $time_created;
				}
				elseif ($end_date == FALSE) {
					if ($plan['occurrences'] == '0') {
						// unlimited occurrences
						$end_date = '0000-00-00 00:00:00';
					}
					else {
						$end_date = date('Y-m-d H:i:s',time() + ($plan['free_trial'] * 86400) + ($plan['occurrences'] * $plan['interval'] * 86400));
					}
				}
				
				// calculate next charge date
				if (!empty($plan['free_trial'])) {
					$next_charge_date = time() + ($plan['free_trial'] * 86400);
				}
				else {
					$next_charge_date = time() + ($plan['interval'] * 86400);
				}
				
				if ((date('Y-m-d',$next_charge_date) == date('Y-m-d',strtotime($end_date)) or $next_charge_date > strtotime($end_date)) and $end_date != '0000-00-00 00:00:00') {
					$next_charge_date = '0000-00-00';
				}
				else {
					$next_charge_date = date('Y-m-d',$next_charge_date);
				}
				
				$payment = ($first_charge == FALSE) ? $plan['price'] : money_format("%!i",$first_charge);
				 
				if ($plan['free_trial'] == 0) {
					// create payment record               						  
					$this->RecordPayment($response['recurring_id'], $response['charge_id'], $payment);
				}
				
				$this->RecordSubscription($response['recurring_id'], $member_id, $plan_id, $next_charge_date, $end_date, $recur_payment); 
			}
			
			return $response;
		}
		
		function RecordSubscription ($recurring_id, $member_id, $plan_id, $next_charge_date, $end_date, $payment) {
			// create subscription record
			$insert_array = array(
								'recurring_id' => $recurring_id,
								'member_id' => $member_id,
								'plan_id' => $plan_id,
								'subscription_price' => $payment,
								'date_created' => date('Y-m-d H:i:s'),
								'date_cancelled' => '0000-00-00 00:00:00',
								'next_charge_date' => $next_charge_date,
								'end_date' => $end_date,
								'expired' => '0',
								'cancelled' => '0',
								'active' => '1',
								'expiry_processed' => '0'
							);

			$this->EE->db->insert('exp_membrr_subscriptions',$insert_array);
			
			$plan = $this->GetPlan($plan_id);

			// shall we move to a new usergroup?
			if (!empty($plan['member_group'])) {
				$this->EE->db->where('member_id',$member_id);
				$this->EE->db->where('group_id !=','1');
				$this->EE->db->update('exp_members',array('group_id' => $plan['member_group']));
			}
			
			// call "membrr_subscribe" hook with: member_id, subscription_id, plan_id, end_date
			
			if (isset($this->EE->extensions->extensions['membrr_subscribe']))
			{
			    $this->EE->extensions->call_extension('membrr_subscribe', $member_id, $recurring_id, $plan_id, $end_date);
			    if ($this->EE->extensions->end_script === TRUE) return $response;
			}
			
			return TRUE;
		}
		
		function RecordPayment ($subscription_id, $charge_id, $amount) {
			$insert_array = array(
								'charge_id' => $charge_id,
								'recurring_id' => $subscription_id,
								'amount' => $amount,
								'date' => date('Y-m-d H:i:s'),
								'refunded' => '0'
							);
							
			$this->EE->db->insert('exp_membrr_payments',$insert_array);
			
			return TRUE;
		}
		
		function Refund ($charge_id) {
			$config = $this->GetConfig();
			$connect_url = $config['api_url'] . '/api';
			require_once(dirname(__FILE__) . '/opengateway.php');
			
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('Refund');
			$opengateway->Param('charge_id', $charge_id);
			$response = $opengateway->Process();
			
			if (isset($response['response_code']) and $response['response_code'] == '50') {
				$this->EE->db->update('exp_membrr_payments',array('refunded' => '1'),array('charge_id' => $charge_id));
				return array('success' => TRUE);
			}
			elseif (isset($response['response_code'])) {
				return array('success' => FALSE, 'error' => $response['response_text']);
			}
			else {
				return array('success' => FALSE, 'error' => $response['error_text']);
			}
		}
		
		/*
		* Set Next Charge
		*
		* @param int $subscription_id The subscription ID #
		* @param string $next_charge_date YYYY-MM-DD format of the next charge date
		*/
		function SetNextCharge ($subscription_id, $next_charge_date) {
			$this->EE->db->update('exp_membrr_subscriptions',array('next_charge_date' => $next_charge_date),array('recurring_id' => $subscription_id));
			
			return TRUE;
		}
		
		function GetChannels () {		
			$this->EE->db->select('exp_membrr_channels.*');
			$this->EE->db->select('exp_channels.channel_name');
			$this->EE->db->select('exp_statuses.status AS status_name',FALSE);
			$this->EE->db->join('exp_statuses','exp_membrr_channels.expiration_status = exp_statuses.status_id','LEFT');
			$this->EE->db->join('exp_channels','exp_membrr_channels.channel_id = exp_channels.channel_id','LEFT');
			$this->EE->db->order_by('exp_channels.channel_name','ASC');
			$result = $this->EE->db->get('exp_membrr_channels');
			
			$channels = array();		
			
			if ($result->num_rows() == 0) {
				return FALSE;
			}
					      
			foreach ($result->result_array() as $row) {
				$channels[] = array(
								'id' => $row['protect_channel_id'],
								'channel_id' => $row['channel_id'],
								'channel_name' => $row['channel_name'],
								'expiration_status' => $row['expiration_status'],
								'status_name' => $row['status_name'],
								'order_form' => $row['order_form'],
								'one_post' => $row['one_post'],
								'plans' => explode('|',$row['plans'])
								);
			}
			
			return $channels;
		}
		
		function GetChannel ($id, $field = 'exp_membrr_channels.protect_channel_id') {		
			$this->EE->db->select('exp_membrr_channels.*');
			$this->EE->db->select('exp_channels.channel_name');
			$this->EE->db->select('exp_statuses.status AS status_name',FALSE);
			$this->EE->db->join('exp_statuses','exp_membrr_channels.expiration_status = exp_statuses.status_id','LEFT');
			$this->EE->db->join('exp_channels','exp_membrr_channels.channel_id = exp_channels.channel_id','LEFT');
			$this->EE->db->where($field,$id);
			$result = $this->EE->db->get('exp_membrr_channels');		
			
			if ($result->num_rows() == 0) {
				return FALSE;
			}
					      
			$row = $result->row_array();
			
			$channel = array(
							'id' => $row['protect_channel_id'],
							'channel_id' => $row['channel_id'],
							'channel_name' => $row['channel_name'],
							'expiration_status' => $row['expiration_status'],
							'status_name' => $row['status_name'],
							'order_form' => $row['order_form'],
							'one_post' => $row['one_post'],
							'plans' => explode('|',$row['plans'])
							);
			
			return $channel;
		}
		
		function DeleteChannel ($id) {	
			$this->EE->db->delete('exp_membrr_channels',array('protect_channel_id' => $id));	
			
			return TRUE;
		}
		
		function GetSubscriptionForChannel ($user, $plans, $one_post, $sub_id = FALSE) {
			$plans_query = '\'';
			foreach ($plans as $plan) {
				$plans_query .= $plan . '\', \'';
			}
			
			$plans_query = rtrim($plans_query, ', \'');
			
			$plans_query .= '\'';
			
			// are we looking for a specific sub?
			if ($sub_id != FALSE) {
				$this->EE->db->where('exp_membrr_subscriptions.recurring_id',$sub_id);
			}
			
			if ($one_post == '0') {
				$this->EE->db->select('exp_membrr_subscriptions.recurring_id');
				$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
				$this->EE->db->where('exp_membrr_subscriptions.member_id',$user);
				$this->EE->db->where('`plan_id` IN (' . $plans_query . ')',null,FALSE);
				$this->EE->db->where('(`exp_membrr_subscriptions`.`end_date` = \'0000-00-00 00:00:00\' OR `exp_membrr_subscriptions`.`end_date` > NOW())',null,FALSE);
			}
			elseif ($one_post == '1') {
				// subscription mustn't be linked to another post		   
				$this->EE->db->select('exp_membrr_subscriptions.recurring_id');
				$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
				$this->EE->db->where('exp_membrr_subscriptions.member_id',$user);
				$this->EE->db->where('(`exp_membrr_channel_posts`.`post_id` IS NULL or `exp_membrr_channel_posts`.`active` != \'1\')',null,FALSE);
				$this->EE->db->where('`plan_id` IN (' . $plans_query . ')',null,FALSE);
				$this->EE->db->where('(`exp_membrr_subscriptions`.`end_date` = \'0000-00-00 00:00:00\' OR `exp_membrr_subscriptions`.`end_date` > NOW())',null,FALSE);
				$this->EE->db->order_by('exp_membrr_channel_posts.active','DESC');
			}
			
			$result = $this->EE->db->get('exp_membrr_subscriptions');
			
			if ($result->num_rows() == 0) {
				return FALSE;
			}
			else {
				$row = $result->row_array();
				return $row['recurring_id'];
			}
		}
		
		function UpdateAddress ($member_id, $first_name, $last_name, $street_address, $address_2, $city, $region, $region_other, $country, $postal_code) {
			$this->EE->db->select('address_id');
			$this->EE->db->where('member_id',$member_id);
			$result = $this->EE->db->get('exp_membrr_address_book');
			
			if ($result->num_rows() > 0) {
				// update
				
				$address = $result->row_array();
				$update_array = array(
									'member_id' => $member_id,
									'first_name' => $first_name, 
									'last_name' => $last_name, 
									'address' => $street_address, 
									'address_2' => $address_2, 
									'city' => $city, 
									'region' => $region, 
									'region_other' => $region_other, 
									'country' => $country, 
									'postal_code' => $postal_code
								);
				$this->EE->db->update('exp_membrr_address_book',$update_array, array('address_id' => $address['address_id']));
				
				// update OpenGateway customer record
				$config = $this->GetConfig();
					
				if (!class_exists('OpenGateway')) {
					require(dirname(__FILE__) . '/opengateway.php');
				}
				
				$connect_url = $config['api_url'] . '/api';
				$server = new OpenGateway;
				$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
				
				// get customer ID
				$server->SetMethod('GetCustomers');
				$server->Param('internal_id', $member_id);
				$response = $server->Process();
				
				if ($response['total_results'] > 0) {	
					// there is already a customer record here
					$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
					
					$server->SetMethod('UpdateCustomer');
					$server->Param('customer_id',$customer['id']);
					$server->Param('first_name', $first_name); 
					$server->Param('last_name', $last_name); 
					$server->Param('address_1', $street_address); 
					$server->Param('address_2', $address_2); 
					$server->Param('city', $city); 
					$server->Param('state', (empty($region)) ? $region_other : $region); 
					$server->Param('country', $country); 
					$server->Param('postal_code', $postal_code);
					$response = $server->Process();
				}
				else {
					// this is unexpected, there should be a record here
				}
			}
			else {
				// insert
				$insert_array = array(
									'member_id' => $member_id,
									'first_name' => $first_name, 
									'last_name' => $last_name, 
									'address' => $street_address, 
									'address_2' => $address_2, 
									'city' => $city, 
									'region' => $region, 
									'region_other' => $region_other, 
									'country' => $country, 
									'postal_code' => $postal_code
								);
				$this->EE->db->insert('exp_membrr_address_book',$insert_array);
			}
			
			return TRUE;
		}
		
		function GetAddress ($member_id) {
			$this->EE->db->where('member_id',$member_id);
			$result = $this->EE->db->get('exp_membrr_address_book');
			
			if ($result->num_rows() > 0) {
				return $result->row_array();
			}
			else {
				return array(
						'first_name' => '', 
						'last_name' => '',
						'address' => '',
						'address_2' => '',
						'city' => '',
						'region' => '',
						'region_other' => '', 
						'country' => '',
						'postal_code' => ''
					);
			}
		}
		
		function GetPayments ($offset = 0, $limit = 50, $filters = false) {
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['subscription_id'])) {
					$this->EE->db->where('exp_membrr_payments.recurring_id',$filters['subscription_id']);
				}
				if (isset($filters['member_id'])) {
					$this->EE->db->where('exp_membrr_subscriptions.member_id',$filters['member_id']);
				}
				if (isset($filters['id'])) {
					$this->EE->db->where('exp_membrr_payments.charge_id',$filters['id']);
				}
			}
			
			$this->EE->db->select('exp_membrr_payments.*, exp_members.*, exp_membrr_plans.*,  exp_membrr_channel_posts.channel_entry_id AS `entry_id`, exp_channels.channel_name  AS `channel`', FALSE);
			$this->EE->db->join('exp_membrr_subscriptions','exp_membrr_subscriptions.recurring_id = exp_membrr_payments.recurring_id','left');
			$this->EE->db->join('exp_members','exp_membrr_subscriptions.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_membrr_plans','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
			$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
			$this->EE->db->join('exp_channels','exp_membrr_channel_posts.channel_id = exp_channels.channel_id','left');
			$this->EE->db->group_by('exp_membrr_payments.charge_id');
			$this->EE->db->order_by('exp_membrr_payments.date','DESC');
			$this->EE->db->limit($limit, $offset);
				      
			$result = $this->EE->db->get('exp_membrr_payments');
			
			$payments = array();
			
			foreach ($result->result_array() as $row) {
				$payments[] = array(
								'id' => $row['charge_id'],
								'recurring_id' => $row['recurring_id'],
								'user_screenname' => $row['screen_name'],
								'user_username' => $row['username'],
								'user_groupid' => $row['group_id'],
								'amount' => money_format("%!i",$row['amount']), 
								'plan_name' => $row['plan_name'],
								'plan_id' => $row['plan_id'],
								'plan_description' => $row['plan_description'],
								'date' => date('M j, Y h:i a',strtotime($row['date'])),
								'refunded' => $row['refunded'],
								'entry_id' => (empty($row['entry_id'])) ? FALSE : $row['entry_id'],
								'channel' => (empty($row['entry_id'])) ? FALSE : $row['channel_name']
							);
			}
			
			if (empty($payments)) {
				return false;
			}
			else {
				return $payments;
			}
		}
		
		/**
		* Get Subscriptions
		*
		* @param int $offset The offset for which to load subscriptions
		* @param array $filters Filter the results
		* @param int $filters['member_id'] The EE member ID
		* @param int $filters['id'] The subscription ID
		* @param int $filters['active'] Set to "1" to retrieve only active subscriptions and "0" only ended subs
		*/
		
		function GetSubscriptions ($offset = FALSE, $limit = 50, $filters = array()) {		
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['member_id'])) {
					$this->EE->db->where('exp_membrr_subscriptions.member_id',$filters['member_id']);
				}
				if (isset($filters['active'])) {
					if ($filters['active'] == '1')  {
						$this->EE->db->where('(exp_membrr_subscriptions.end_date = \'0000-00-00 00:00:00\' OR exp_membrr_subscriptions.end_date > NOW())',null,FALSE);
					}
					elseif ($filters['active'] == '0') {
						$this->EE->db->where('(exp_membrr_subscriptions.end_date != \'0000-00-00 00:00:00\' AND exp_membrr_subscriptions.end_date < NOW())',null,FALSE);
					}
				}
				if (isset($filters['id'])) {
					$this->EE->db->where('exp_membrr_subscriptions.recurring_id',$filters['id']);
				}
				if (isset($filters['plan_id'])) {
					$this->EE->db->where('exp_membrr_subscriptions.plan_id',$filters['plan_id']);
				}
				if (isset($filters['search'])) {
					$this->EE->db->like('exp_membrr_subscriptions.recurring_id',$filters['search']);
					$this->EE->db->or_like('exp_membrr_plans.plan_name',$filters['search']);
					$this->EE->db->or_like('exp_members.username',$filters['search']);
					$this->EE->db->or_like('exp_members.screen_name',$filters['search']);
					$this->EE->db->or_like('exp_members.email',$filters['search']);
					$this->EE->db->or_like('exp_membrr_subscriptions.subscription_price',$filters['search']);
				}
			}	
			
			$this->EE->db->select('exp_membrr_subscriptions.*, exp_membrr_subscriptions.recurring_id AS subscription_id, exp_membrr_payments.*, exp_members.*, exp_membrr_plans.*, exp_membrr_channel_posts.channel_entry_id AS entry_id, exp_channels.channel_name AS channel', FALSE);
			$this->EE->db->join('exp_membrr_payments','exp_membrr_subscriptions.recurring_id = exp_membrr_payments.recurring_id','left');
			$this->EE->db->join('exp_members','exp_membrr_subscriptions.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_membrr_plans','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
			$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
			$this->EE->db->join('exp_channels','exp_membrr_channel_posts.channel_id = exp_channels.channel_id','left');
			$this->EE->db->group_by('exp_membrr_subscriptions.recurring_id');
			$this->EE->db->order_by('exp_membrr_subscriptions.date_created','DESC');
			$this->EE->db->limit($limit, $offset);
			
			$result = $this->EE->db->get('exp_membrr_subscriptions');
			
			$subscriptions = array();
			
			foreach ($result->result_array() as $row) {
				$subscriptions[] = array(
								'id' => $row['subscription_id'],
								'member_id' => $row['member_id'],
								'user_screenname' => $row['screen_name'],
								'user_username' => $row['username'],
								'user_groupid' => $row['group_id'],
								'amount' => money_format("%!i",$row['subscription_price']),
								'plan_name' => $row['plan_name'],
								'plan_id' => $row['plan_id'],
								'plan_description' => $row['plan_description'],
								'entry_id' => (empty($row['entry_id'])) ? FALSE : $row['entry_id'],
								'channel' => (empty($row['entry_id'])) ? FALSE : $row['channel'],
								'date_created' => date('M j, Y h:i a',strtotime($row['date_created'])),
								'date_cancelled' => (!strstr($row['date_cancelled'],'0000-00-00')) ? date('M j, Y h:i a',strtotime($row['date_cancelled'])) : FALSE,
								'next_charge_date' => ($row['next_charge_date'] != '0000-00-00') ? date('M j, Y',strtotime($row['next_charge_date'])) : FALSE,
								'end_date' => ($row['end_date'] == '0000-00-00 00:00:00') ? FALSE : date('M j, Y h:i a',strtotime($row['end_date'])),
								'active' => $row['active'],
								'cancelled' => $row['cancelled'],
								'expired' => $row['expired']
							);
			}
			
			if (empty($subscriptions)) {
				return FALSE;
			}
			else {
				return $subscriptions;
			}
		}
		
		function GetSubscription ($subscription_id) {		
			$subscriptions = $this->GetSubscriptions(FALSE, 1, array('id' => $subscription_id));
	
			if (isset($subscriptions[0])) {
				return $subscriptions[0];
			}
			
			return FALSE;
		}	
	
		function GetPlans ($filters = array()) {
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['id'])) {
					$this->EE->db->where('exp_membrr_plans.plan_id',$filters['id']);
				}
				if (isset($filters['ids']) and is_array($filters['ids'])) {
					$count = 1;
					foreach ($filters['ids'] as $id) {
						$function = ($count == 1) ? 'where' : 'or_where';
						$this->EE->db->$function('exp_membrr_plans.plan_id',$id);
						$count++;
					}
				}
				if (isset($filters['active'])) {
					$this->EE->db->where('exp_membrr_plans.plan_active',$filters['active']);
				}
			}
			
			$this->EE->db->select('exp_membrr_plans.*, SUM(exp_membrr_subscriptions.active) AS num_active_subscribers', FALSE);
			$this->EE->db->join('exp_membrr_subscriptions','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
			$this->EE->db->where('exp_membrr_plans.plan_deleted','0');
			$this->EE->db->group_by('exp_membrr_plans.plan_id');
				      
			$result = $this->EE->db->get('exp_membrr_plans');
			
			$plans = array();
			
			foreach ($result->result_array() as $row) {
				if (empty($row['plan_id'])) {
					break;
				}
				
				$plans[] = array(
								'id' => $row['plan_id'],
								'api_id' => $row['api_plan_id'],
								'name' => $row['plan_name'],
								'description' => $row['plan_description'],
								'price' => money_format("%!i",$row['plan_price']),
								'initial_charge' => money_format("%!i",$row['plan_initial_charge']),
								'gateway' => $row['plan_gateway'],
								'interval' => $row['plan_interval'],
								'free_trial' => $row['plan_free_trial'],
								'occurrences' => $row['plan_occurrences'],
								'import_date' => $row['plan_import_date'],
								'for_sale' => $row['plan_active'],
								'redirect_url' => $row['plan_redirect_url'],
								'member_group' => $row['plan_member_group'],
								'member_group_expire' => $row['plan_member_group_expire'],
								'num_subscribers' => (empty($row['num_active_subscribers'])) ? '0' : $row['num_active_subscribers'],
								'deleted' => $row['plan_deleted']
							);
			}
			
			if (empty($plans)) {
				return false;
			}
			else {
				return $plans;
			}
		}
		
		function GetPlan ($id) {
			$plans = $this->GetPlans(array('id' => $id));
			
			if (isset($plans[0])) {
				return $plans[0];
			}
			
			return FALSE;
		}
		
		function DeletePlan ($plan_id) {
			$this->EE->db->update('exp_membrr_plans',array('plan_deleted' => '1','plan_active' => '0'),array('plan_id' => $plan_id));
			
			// cancel all subscriptions
			$subscribers = $this->GetSubscribersByPlan($plan_id);
			
			if ($subscribers) {
				foreach ($subscribers as $subscriber) {
					if (!$this->CancelSubscription($subscriber['recurring_id'])) {
						return false;
					}
				}
			}
			
			return true;
		}
		
		function CancelSubscription ($sub_id, $make_api_call = TRUE, $expired = FALSE) {
			if (!$subscription = $this->GetSubscription($sub_id) or $subscription['active'] == '0') {
				return FALSE;
			}
			
			$subscription['next_charge_date'] = date('Y-m-d',strtotime($subscription['next_charge_date']));
			$subscription['end_date'] = date('Y-m-d H:i:s',strtotime($subscription['end_date']));
			
			// calculate end_date
			if ($subscription['next_charge_date'] != '0000-00-00' and strtotime($subscription['next_charge_date']) > time()) {
				// there's a next charge date which won't be renewed, so we'll end it then
				// we must also account for their signup time
				$time_created = date('H:i:s',strtotime($subscription['date_created']));
				$end_date = $subscription['next_charge_date'] . ' ' . $time_created;
			}
			elseif ($subscription['end_date'] != '0000-00-00 00:00:00') {
				// there is a set end_date
				$end_date = $subscription['end_date'];
			}
			else {
				// for some reason, neither a next_charge_date or an end_date exist
				// let's end this now
				$end_date = date('Y-m-d H:i:s');
			}
			
			// nullify next charge
			$next_charge_date = '0000-00-00';
			
			// cancel subscription
		 	$update_array = array(
		 						'active' => '0',
		 						'end_date' => $end_date,
		 						'next_charge_date' => $next_charge_date,
		 						'date_cancelled' => date('Y-m-d H:i:s')
		 					);	
		 	// are we cancelling or expiring?
		 	if ($expired == TRUE) {
		 		$update_array['expired'] = '1';
		 	}
		 	else {
		 		$update_array['cancelled'] = '1';
		 	}
		 	
		 	$this->EE->db->update('exp_membrr_subscriptions',$update_array,array('recurring_id' => $subscription['id']));
			
			if ($make_api_call == true) {
				$config = $this->GetConfig();
				
				if (!class_exists('OpenGateway')) {
					require(dirname(__FILE__) . '/opengateway.php');
				}
				
				$connect_url = $config['api_url'] . '/api';
				$server = new OpenGateway;
				$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
				$server->SetMethod('CancelRecurring');
				$server->Param('recurring_id',$subscription['id']);
				$response = $server->Process();
				if (isset($response['error'])) {
					return FALSE;
				}
			}
			
			// call "membrr_cancel" hook with: member_id, subscription_id, plan_id, end_date
			if (isset($this->EE->extensions->extensions['membrr_cancel']))
			{
			    $this->EE->extensions->call_extension('membrr_cancel', $subscription['member_id'], $subscription['id'], $subscription['plan_id'], $subscription['end_date']);
			    if ($this->EE->extensions->end_script === TRUE) return;
			} 
					
			return TRUE;
		}
		
		function GetSubscribersByPlan ($plan_id) {		      
			$this->EE->db->where('plan_id',$plan_id);
			$result = $this->EE->db->get('exp_membrr_subscriptions');
			
			$subscribers = array();
			
			foreach ($result->result_array() as $row) {
				$subscribers[] = $row;
			}
			
			if (empty($subscribers)) {
				return FALSE;
			}
			else {
				return $subscribers;
			}
		}
		
		function GetConfig () {
			$result = $this->EE->db->get('exp_membrr_config');
			
			if ($result->num_rows() == 0) {
				return FALSE;
			}
			else {
				return $result->row_array();
			}
		}
		
		function CountRows ($table) {
			$this->EE->db->get($table);
			
			return $result->num_rows();
		}
		
		function get_channel_id ($numeric_id) {
	    	if (isset($this->cache[$numeric_id])) {
	    		return $this->cache[$numeric_id];
	    	}
	    	
	    	$this->EE->db->get('blog_name');
	    	$this->EE->db->where('channel_id',$numeric_id);
			$result = $this->EE->db->get('exp_channels');
	    	$row = $result->row_array();
	    	
	    	$this->cache[$numeric_id] = $row['blog_name'];
	    	
	    	return $row['blog_name'];
	    }
		
		function GetRegions () {
			return array(
					'AL' => 'Alabama',
					'AK' => 'Alaska',
					'AZ' => 'Arizona',
					'AR' => 'Arkansas',
					'CA' => 'California',
					'CO' => 'Colorado',
					'CT' => 'Connecticut',
					'DE' => 'Delaware',
					'DC' => 'District of Columbia',
					'FL' => 'Florida',
					'GA' => 'Georgia',
					'HI' => 'Hawaii',
					'ID' => 'Idaho',
					'IL' => 'Illinois',
					'IN' => 'Indiana',
					'IA' => 'Iowa',
					'KS' => 'Kansas',
					'KY' => 'Kentucky',
					'LA' => 'Louisiana',
					'ME' => 'Maine',
					'MD' => 'Maryland',
					'MA' => 'Massachusetts',
					'MI' => 'Michigan',
					'MN' => 'Minnesota',
					'MS' => 'Mississippi',
					'MO' => 'Missouri',
					'MT' => 'Montana',
					'NE' => 'Nebraska',
					'NV' => 'Nevada',
					'NH' => 'New Hampshire',
					'NJ' => 'New Jersey',
					'NM' => 'New Mexico',
					'NY' => 'New York',
					'NC' => 'North Carolina',
					'ND' => 'North Dakota',
					'OH' => 'Ohio',
					'OK' => 'Oklahoma',
					'OR' => 'Oregon',
					'PA' => 'Pennsylvania',
					'RI' => 'Rhode Island',
					'SC' => 'South Carolina',
					'SD' => 'South Dakota',
					'TN' => 'Tennessee',
					'TX' => 'Texas',
					'UT' => 'Utah',
					'VT' => 'Vermont',
					'VA' => 'Virginia',
					'WA' => 'Washington',
					'WV' => 'West Virginia',
					'WI' => 'Wisconsin',
					'WY' => 'Wyoming',
					'AB' => 'Alberta',
					'BC' => 'British Columbia',
					'MB' => 'Manitoba',
					'NB' => 'New Brunswick',
					'NL' => 'Newfoundland and Laborador',
					'NT' => 'Northwest Territories',
					'NS' => 'Nova Scotia',
					'NU' => 'Nunavut',
					'ON' => 'Ontario',
					'PE' => 'Prince Edward Island',
					'QC' => 'Quebec',
					'SK' => 'Saskatchewan',
					'YT' => 'Yukon'
				);
		}
		
		function GetCountries () {
	    	return array(
	    				'US' => 'United States',
	    				'CA' => 'Canada',
						'AF' => 'Afghanistan',
						'AX' => 'Aland Islands',
						'AL' => 'Albania',
						'DZ' => 'Algeria',
						'AS' => 'American Samoa',
						'AD' => 'Andorra',
						'AO' => 'Angola',
						'AI' => 'Anguilla',
						'AQ' => 'Antarctica',
						'AG' => 'Antigua and Barbuda',
						'AR' => 'Argentina',
						'AM' => 'Armenia',
						'AW' => 'Aruba',
						'AU' => 'Australia',
						'AT' => 'Austria',
						'AZ' => 'Azerbaijan',
						'BS' => 'Bahamas',
						'BH' => 'Bahrain',
						'BD' => 'Bangladesh',
						'BB' => 'Barbados',
						'BY' => 'Belarus',
						'BE' => 'Belgium',
						'BZ' => 'Belize',
						'BJ' => 'Benin',
						'BM' => 'Bermuda',
						'BT' => 'Bhutan',
						'BO' => 'Bolivia',
						'BA' => 'Bosnia and Herzegovina',
						'BW' => 'Botswana',
						'BV' => 'Bouvet Island',
						'BR' => 'Brazil',
						'IO' => 'British Indian Ocean Territory',
						'BN' => 'Brunei Darussalam',
						'BG' => 'Bulgaria',
						'BF' => 'Burkina Faso',
						'BI' => 'Burundi',
						'KH' => 'Cambodia',
						'CM' => 'Cameroon',
						'CV' => 'Cape Verde',
						'KY' => 'Cayman Islands',
						'CF' => 'Central African Republic',
						'TD' => 'Chad',
						'CL' => 'Chile',
						'CN' => 'China',
						'CX' => 'Christmas Island',
						'CC' => 'Cocos Keeling Islands',
						'CO' => 'Colombia',
						'KM' => 'Comoros',
						'CG' => 'Congo',
						'CD' => 'Congo, Democratic Republic of the',
						'CK' => 'Cook Islands',
						'CR' => 'Costa Rica',
						'CI' => 'C™te dIvoire',
						'HR' => 'Croatia',
						'CU' => 'Cuba',
						'CY' => 'Cyprus',
						'CZ' => 'Czech Republic',
						'DK' => 'Denmark',
						'DJ' => 'Djibouti',
						'DM' => 'Dominica',
						'DO' => 'Dominican Republic',
						'EC' => 'Ecuador',
						'EG' => 'Egypt',
						'SV' => 'El Salvador',
						'GQ' => 'Equatorial Guinea',
						'ER' => 'Eritrea',
						'EE' => 'Estonia',
						'ET' => 'Ethiopia',
						'FK' => 'Falkland Islands Malvinas',
						'FO' => 'Faroe Islands',
						'FJ' => 'Fiji',
						'FI' => 'Finland',
						'FR' => 'France',
						'GF' => 'French Guiana',
						'PF' => 'French Polynesia',
						'TF' => 'French Southern Territories',
						'GA' => 'Gabon',
						'GM' => 'Gambia',
						'GE' => 'Georgia',
						'DE' => 'Germany',
						'GH' => 'Ghana',
						'GI' => 'Gibraltar',
						'GR' => 'Greece',
						'GL' => 'Greenland',
						'GD' => 'Grenada',
						'GP' => 'Guadeloupe',
						'GU' => 'Guam',
						'GT' => 'Guatemala',
						'GG' => 'Guernsey',
						'GN' => 'Guinea',
						'GW' => 'Guinea-Bissau',
						'GY' => 'Guyana',
						'HT' => 'Haiti',
						'HM' => 'Heard Island and McDonald Islands',
						'VA' => 'Holy See Vatican City State',
						'HN' => 'Honduras',
						'HK' => 'Hong Kong',
						'HU' => 'Hungary',
						'IS' => 'Iceland',
						'IN' => 'India',
						'ID' => 'Indonesia',
						'IR' => 'Iran, Islamic Republic of',
						'IQ' => 'Iraq',
						'IE' => 'Ireland',
						'IM' => 'Isle of Man',
						'IL' => 'Israel',
						'IT' => 'Italy',
						'JM' => 'Jamaica',
						'JP' => 'Japan',
						'JE' => 'Jersey',
						'JO' => 'Jordan',
						'KZ' => 'Kazakhstan',
						'KE' => 'Kenya',
						'KI' => 'Kiribati',
						'KP' => 'Korea, Democratic People\'s Republic of',
						'KR' => 'Korea, Republic of',
						'KW' => 'Kuwait',
						'KG' => 'Kyrgyzstan',
						'LA' => 'Lao Peoples Democratic Republic',
						'LV' => 'Latvia',
						'LB' => 'Lebanon',
						'LS' => 'Lesotho',
						'LR' => 'Liberia',
						'LY' => 'Libyan Arab Jamahiriya',
						'LI' => 'Liechtenstein',
						'LT' => 'Lithuania',
						'LU' => 'Luxembourg',
						'MO' => 'Macao',
						'MK' => 'Macedonia, the former Yugoslav Republic of',
						'MG' => 'Madagascar',
						'MW' => 'Malawi',
						'MY' => 'Malaysia',
						'MV' => 'Maldives',
						'ML' => 'Mali',
						'MT' => 'Malta',
						'MH' => 'Marshall Islands',
						'MQ' => 'Martinique',
						'MR' => 'Mauritania',
						'MU' => 'Mauritius',
						'YT' => 'Mayotte',
						'MX' => 'Mexico',
						'FM' => 'Micronesia, Federated States of',
						'MD' => 'Moldova',
						'MC' => 'Monaco',
						'MN' => 'Mongolia',
						'ME' => 'Montenegro',
						'MS' => 'Montserrat',
						'MA' => 'Morocco',
						'MZ' => 'Mozambique',
						'MM' => 'Myanmar',
						'NA' => 'Namibia',
						'NR' => 'Nauru',
						'NP' => 'Nepal',
						'NL' => 'Netherlands',
						'AN' => 'Netherlands Antilles',
						'NC' => 'New Caledonia',
						'NZ' => 'New Zealand',
						'NI' => 'Nicaragua',
						'NE' => 'Niger',
						'NG' => 'Nigeria',
						'NU' => 'Niue',
						'NF' => 'Norfolk Island',
						'MP' => 'Northern Mariana Islands',
						'NO' => 'Norway',
						'OM' => 'Oman',
						'PK' => 'Pakistan',
						'PW' => 'Palau',
						'PS' => 'Palestinian Territory, Occupied',
						'PA' => 'Panama',
						'PG' => 'Papua New Guinea',
						'PY' => 'Paraguay',
						'PE' => 'Peru',
						'PH' => 'Philippines',
						'PN' => 'Pitcairn',
						'PL' => 'Poland',
						'PT' => 'Portugal',
						'PR' => 'Puerto Rico',
						'QA' => 'Qatar',
						'RE' => 'RŽunion',
						'RO' => 'Romania',
						'RU' => 'Russian Federation',
						'RW' => 'Rwanda',
						'BL' => 'Saint BarthŽlemy',
						'SH' => 'Saint Helena',
						'KN' => 'Saint Kitts and Nevis',
						'LC' => 'Saint Lucia',
						'MF' => 'Saint Martin French part',
						'PM' => 'Saint Pierre and Miquelon',
						'VC' => 'Saint Vincent and the Grenadines',
						'WS' => 'Samoa',
						'SM' => 'San Marino',
						'ST' => 'Sao Tome and Principe',
						'SA' => 'Saudi Arabia',
						'SN' => 'Senegal',
						'RS' => 'Serbia[5]',
						'SC' => 'Seychelles',
						'SL' => 'Sierra Leone',
						'SG' => 'Singapore',
						'SK' => 'Slovakia',
						'SI' => 'Slovenia',
						'SB' => 'Solomon Islands',
						'SO' => 'Somalia',
						'ZA' => 'South Africa',
						'GS' => 'South Georgia and the South Sandwich Islands',
						'ES' => 'Spain',
						'LK' => 'Sri Lanka',
						'SD' => 'Sudan',
						'SR' => 'Suriname',
						'SJ' => 'Svalbard and Jan Mayen',
						'SZ' => 'Swaziland',
						'SE' => 'Sweden',
						'CH' => 'Switzerland',
						'SY' => 'Syrian Arab Republic',
						'TW' => 'Taiwan, Province of China',
						'TJ' => 'Tajikistan',
						'TZ' => 'Tanzania, United Republic of',
						'TH' => 'Thailand',
						'TL' => 'Timor-Leste',
						'TG' => 'Togo',
						'TK' => 'Tokelau',
						'TO' => 'Tonga',
						'TT' => 'Trinidad and Tobago',
						'TN' => 'Tunisia',
						'TR' => 'Turkey',
						'TM' => 'Turkmenistan',
						'TC' => 'Turks and Caicos Islands',
						'TV' => 'Tuvalu',
						'UG' => 'Uganda',
						'UA' => 'Ukraine',
						'AE' => 'United Arab Emirates',
						'GB' => 'United Kingdom',
						'UM' => 'United States Minor Outlying Islands',
						'UY' => 'Uruguay',
						'UZ' => 'Uzbekistan',
						'VU' => 'Vanuatu',
						'VE' => 'Venezuela',
						'VN' => 'Viet Nam',
						'VG' => 'Virgin Islands, British',
						'VI' => 'Virgin Islands, U.S.',
						'WF' => 'Wallis and Futuna',
						'EH' => 'Western Sahara',
						'YE' => 'Yemen',
						'ZM' => 'Zambia',
						'ZW' => 'Zimbabwe'
					);
	
		}
	
	}
	
	if (!function_exists("money_format")) {
		function money_format($format, $number)
		{
		    $regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
		              '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
		    if (setlocale(LC_MONETARY, 0) == 'C') {
		        setlocale(LC_MONETARY, '');
		    }
		    $locale = localeconv();
		    preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
		    foreach ($matches as $fmatch) {
		        $value = floatval($number);
		        $flags = array(
		            'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
		                           $match[1] : ' ',
		            'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
		            'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
		                           $match[0] : '+',
		            'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
		            'isleft'    => preg_match('/\-/', $fmatch[1]) > 0
		        );
		        $width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
		        $left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
		        $right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
		        $conversion = $fmatch[5];
		
		        $positive = true;
		        if ($value < 0) {
		            $positive = false;
		            $value  *= -1;
		        }
		        $letter = $positive ? 'p' : 'n';
		
		        $prefix = $suffix = $cprefix = $csuffix = $signal = '';
		
		        $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
		        switch (true) {
		            case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
		                $prefix = $signal;
		                break;
		            case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
		                $suffix = $signal;
		                break;
		            case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
		                $cprefix = $signal;
		                break;
		            case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
		                $csuffix = $signal;
		                break;
		            case $flags['usesignal'] == '(':
		            case $locale["{$letter}_sign_posn"] == 0:
		                $prefix = '(';
		                $suffix = ')';
		                break;
		        }
		        if (!$flags['nosimbol']) {
		            $currency = $cprefix .
		                        ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
		                        $csuffix;
		        } else {
		            $currency = '';
		        }
		        $space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';
		
		        $value = number_format($value, $right, $locale['mon_decimal_point'],
		                 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
		        $value = @explode($locale['mon_decimal_point'], $value);
		
		        $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
		        if ($left > 0 && $left > $n) {
		            $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
		        }
		        $value = implode($locale['mon_decimal_point'], $value);
		        if ($locale["{$letter}_cs_precedes"]) {
		            $value = $prefix . $currency . $space . $value . $suffix;
		        } else {
		            $value = $prefix . $value . $space . $currency . $suffix;
		        }
		        if ($width > 0) {
		            $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
		                     STR_PAD_RIGHT : STR_PAD_LEFT);
		        }
		
		        $format = str_replace($fmatch[0], $value, $format);
		    }
		    return $format;
		} 
	}
}