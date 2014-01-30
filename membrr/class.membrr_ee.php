<?php

/**
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

		// this variable, when set to TRUE, will make all PayPal EC subscriptions
		// renew on the same day each month.  It won't work with other gateways.
		public $same_day_every_month = FALSE;

		/**
		* Constructor
		*
		* Deal with expirations, load EE superobject
		*
		* @return void
		*/
		function __construct () {
			$this->EE =& get_instance();

			// deal with channel protection expirations
			if ($this->EE->db->table_exists('exp_membrr_subscriptions')) {
				$this->EE->db->where('(`end_date` <= NOW() and `end_date` != \'0000-00-00 00:00:00\') AND `exp_membrr_channel_posts`.`post_id` IS NOT NULL AND `exp_membrr_channel_posts`.`active` = \'1\'',NULL,FALSE);
				$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_channel_posts.recurring_id = exp_membrr_subscriptions.recurring_id','LEFT');
				$query = $this->EE->db->get('exp_membrr_subscriptions');

				foreach ($query->result_array() AS $row) {
					// we need to change the status of the post

					// get info on channel protection
					$channel = $this->GetChannel($row['channel_id'], 'exp_membrr_channels.channel_id');

					// update the status
					$this->EE->db->update('exp_channel_titles',array('status' => $channel['status_name']), array('entry_id' => $row['channel_entry_id']));

					// delete the link
					$this->EE->db->update('exp_membrr_channel_posts',array('active' => '0'), array('post_id' => $row['post_id']));
				}
			}

			// deal with normal expirations and member usergroup moves
			if ($this->EE->db->table_exists('exp_membrr_subscriptions')) {
				$this->EE->db->select('exp_membrr_subscriptions.member_id');
				$this->EE->db->select('exp_membrr_subscriptions.recurring_id');
				$this->EE->db->select('exp_membrr_subscriptions.renewed_recurring_id');
				$this->EE->db->select('exp_membrr_subscriptions.plan_id');
				$this->EE->db->select('exp_membrr_plans.plan_member_group_expire');
				$this->EE->db->select('exp_membrr_plans.plan_member_group');
				$this->EE->db->where('(`end_date` <= NOW() and `end_date` != \'0000-00-00 00:00:00\')',NULL,FALSE);
				$this->EE->db->where('exp_membrr_subscriptions.expiry_processed','0');
				$this->EE->db->join('exp_membrr_plans','exp_membrr_plans.plan_id = exp_membrr_subscriptions.plan_id','LEFT');
				$query = $this->EE->db->get('exp_membrr_subscriptions');

				foreach ($query->result_array() AS $row) {
					$perform_expiration = TRUE;
					// is there an active plan that promotes the user to this group?
					$filters = array(
									'member_id' => $row['member_id'],
									'active' => '1'
								);
					$subscriptions = $this->GetSubscriptions(0, 50, $filters);
					if (!empty($subscriptions)) {
						foreach ($subscriptions as $sub) {
							$plan_result = $this->EE->db->select('plan_member_group')
									  ->where('plan_id',$sub['plan_id'])
									  ->get('exp_membrr_plans');

							if ($plan_result->num_rows() > 0) {
								if ($plan_result->row()->plan_member_group != 0) {
									$perform_expiration = FALSE;
								}
							}
						}
					}

					if ($perform_expiration == TRUE) {
						if ($row['plan_member_group_expire'] > 0) {
							// move to a new usergroup?
							$this->EE->db->where('member_id',$row['member_id']);
							$this->EE->db->where('group_id !=','1');
							$this->EE->db->update('exp_members',array('group_id' => $row['plan_member_group_expire']));
						}

						// call "membrr_expire" hook with: member_id, subscription_id, plan_id
						if ($this->EE->extensions->active_hook('membrr_expire') == TRUE)
						{
						    $this->EE->extensions->call('membrr_expire', $row['member_id'], $row['recurring_id'], $row['plan_id']);
						    if ($this->EE->extensions->end_script === TRUE) return;
						}
					}

					// this expiry is processed
					$this->EE->db->where('recurring_id',$row['recurring_id']);
					$this->EE->db->update('exp_membrr_subscriptions',array('expiry_processed' => '1'));
				}
			}
		}

		/**
		* Subscribe
		*
		* This is the heart of the Membrr engine, an essentially a wrapper for OpenGateway's Recur API Call.
		*
		* @param int $plan_id
		* @param int $member_id
		* @param array $credit_card
		* @param array $customer
		* @param string/boolean $end_date
		* @param float/boolean $first_charge
		* @param float/boolean $recurring_charge
		* @param string/boolean $cancel_url
		* @param string/boolean $return_url
		* @param int/boolean $gateway_id
		* @param int/boolean $renew_subscription
		* @param string $coupon
		*
		* @return array Response from OpenGateway
		*/
		function Subscribe ($plan_id, $member_id, $credit_card, $customer, $end_date = FALSE, $first_charge = FALSE, $recurring_charge = FALSE, $cancel_url = '', $return_url = '', $gateway_id = FALSE, $renew_subscription = FALSE, $coupon = FALSE) {
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

				// let's try to auto-generate a name based if there isn't one there
				if (empty($customer['first_name']) and empty($customer['last_name'])) {
					// do we have a credit card name to generate from?
					if (isset($credit_card['name']) and !empty($credit_card['name'])) {
						$names = explode(' ', $credit_card['name']);

						$customer['first_name'] = $names[0];
						$customer['last_name'] = end($names);
					}
				}

				$recur->Customer($customer['first_name'],$customer['last_name'],$customer['company'],$customer['address'],$customer['address_2'],$customer['city'],$customer['region'],$customer['country'],$customer['postal_code'],$customer['phone'],$customer['email']);
			}

			// coupon?
			if ($coupon != FALSE) {
				// may be using PayPal, so we should store this
				$this->EE->functions->set_cookie('membrr_coupon', $coupon, 86400);

				$recur->Coupon($coupon);
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

			// are we renewing an existing subscription?
			if (!empty($renew_subscription)) {
				// is sub active?
				$old_sub = $this->GetSubscription($renew_subscription);

				if ($old_sub['active'] == '1') {
					$recur->Param('renew',$renew_subscription);

					if ($plan['renewal_extend_from_end'] === TRUE) {
						// we will delay the start of the new subscription until the end of this one

						$old_sub = $this->GetSubscription($renew_subscription);

						// calculate real end date
						$old_end_date = $this->_calculate_end_date($old_sub['end_date'], $old_sub['next_charge_date'], $old_sub['date_created']);

						// convert to timestamp for calcs
						$old_end_date = strtotime($old_end_date);

						// postpone the start date of this new subscription from that end_date
						$difference_in_days = ($old_end_date - time()) / (60*60*24);

						$recur->Param('start_date', date('Y-m-d', $old_end_date), 'recur');

						$plan['free_trial'] = $difference_in_days;
					}
					else {
						// this plan will cancel immediately
						// let's make CancelSubscription do this
						$this->EE->db->update('exp_membrr_subscriptions', array('next_charge_date' => '0000-00-00', 'end_date' => date('Y-m-d H:i:s')), array('recurring_id' => $old_sub['id']));
					}
				}
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

			 	// if we are renewing, we will append this to the $return_url so that we can cancel old subscriptions
			 	// and update channel entries to the new recurring_id for external gateways like PayPal EC
			 	if (!empty($renew_subscription)) {
			 		$return_url .= '&renew_recurring_id=' . $renew_subscription;
			 	}

			 	// sometimes, with query strings, we get index.php?/?ACT=26...
			 	$return_url = str_replace('?/?','?', $return_url);
			}
			$recur->Param('return_url',htmlspecialchars($return_url));
			if (empty($cancel_url)) {
				$cancel_url = $this->EE->functions->fetch_current_uri();
			}
			$recur->Param('cancel_url',htmlspecialchars($cancel_url));

			// call "membrr_pre_subscribe" hook with: $recur, $member_id, $plan_id, $recurring_charge, $first_charge, $end_date

			if ($this->EE->extensions->active_hook('membrr_pre_subscribe') == TRUE)
			{
				$this->EE->extensions->call('membrr_pre_subscribe', $recur, $member_id, $plan_id, $recurring_charge, $first_charge, $end_date);
			    if ($this->EE->extensions->end_script === TRUE) return FALSE;
			}

			$response = $recur->Charge();

			if (isset($response['response_code']) and $response['response_code'] == '100') {
				// success!
				
				// calculate payment amount
				$recur_payment = $response['recur_amount'];
				$payment = $response['amount'];
				$free_trial = $response['free_trial'];
				$start_date = $response['start_date'];

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
						$time_to_calculate_end = time();

						if (!empty($renew_subscription)) {
							$subscription = $this->GetSubscription($renew_subscription);
							$old_end_date = $this->_calculate_end_date($subscription['end_date'], $subscription['next_charge_date'], $subscription['date_created']);

							$time_to_calculate_end = strtotime($old_end_date);
							
							// we don't want to take on the end dates from previously expired subscriptions
							if ($time_to_calculate_end < time()) {
								$time_to_calculate_end = time();
							}
						}

						$end_date = date('Y-m-d H:i:s',$time_to_calculate_end + ($free_trial * 86400) + ($plan['occurrences'] * $plan['interval'] * 86400));
					}
				}

				// calculate next charge date
				if (!empty($free_trial)) {
					$next_charge_date = time() + ($free_trial * 86400);
				}
				elseif (!empty($start_date) and strtotime($start_date) > (time() + 84600)) {
					$next_charge_date = strtotime($start_date);
				}
				else {
					if ($this->same_day_every_month == TRUE and $plan['interval'] % 30 === 0) {
						$months = $plan['interval'] / 30;
						$plural = ($months > 1) ? 's' : '';
						$next_charge_date = strtotime('today + ' . $months . ' month' . $plural);
					} else {
						$next_charge_date = strtotime('today + ' . $plan['interval'] . ' days');
					}
				}

				if ((date('Y-m-d',$next_charge_date) == date('Y-m-d',strtotime($end_date)) or $next_charge_date > strtotime($end_date)) and $end_date != '0000-00-00 00:00:00') {
					$next_charge_date = '0000-00-00';
				}
				else {
					$next_charge_date = date('Y-m-d',$next_charge_date);
				}

				$this->RecordSubscription($response['recurring_id'], $member_id, $plan_id, $next_charge_date, $end_date, $recur_payment, $coupon, $credit_card);

				if (empty($free_trial) and isset($response['charge_id'])) {
					// create payment record
					$this->RecordPayment($response['recurring_id'], $response['charge_id'], $payment);
				}
				
				// perform renewing subscription maintenance
				if (!empty($renew_subscription)) {
					$this->RenewalMaintenance($renew_subscription, $response['recurring_id']);
				}
			}

			return $response;
		}

		/**
		* Update Expiry Date
		*
		* Modify the expiration (end) date of a subscription
		*
		* @param int $subscription_id
		* @param date $new_expiry
		*
		* @return boolean
		*/
		function UpdateExpiryDate ($subscription_id, $new_expiry) {
			if (strtotime($new_expiry) < time()) {
				return FALSE;
			}

			// get subscription
			$subscription = $this->GetSubscription($subscription_id);

			if (empty($subscription)) {
				return FALSE;
			}

			// format
			$new_expiry = date('Y-m-d', strtotime($new_expiry));

			// update locally
			$this->EE->db->update('exp_membrr_subscriptions',array('end_date' => $new_expiry), array('recurring_id' => $subscription_id));

			// connect to OG
			$config = $this->GetConfig();
			$connect_url = $config['api_url'] . '/api';
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('UpdateRecurring');
			$opengateway->Param('recurring_id', $subscription_id);
			$opengateway->Param('end_date', $new_expiry);

			$response = $opengateway->Process();

			if (!isset($response['error'])) {
				return TRUE;
			}
			else {
				// revert
				$this->EE->db->update('exp_membrr_subscriptions',array('end_date' => $subscription['end_date']), array('recurring_id' => $subscription_id));

				return FALSE;
			}
		}

		/**
		* Update Credit Card
		*
		* @param int $subscription_id
		* @param array $credit_card with keys 'number', 'name', 'expiry_year', 'expiry_month', 'security_code'
		* @param int $plan_id (optional, only if changing plans)
		*
		* @return boolean
		*/
		function UpdateCC ($subscription_id, $credit_card, $plan_id = FALSE) {
			$config = $this->GetConfig();

			if (!class_exists('Opengateway')) {
				require(dirname(__FILE__) . '/opengateway.php');
			}

			// connect to OG
			$connect_url = $config['api_url'] . '/api';
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('UpdateCreditCard');
			$opengateway->Param('recurring_id', $subscription_id);

			// credit card
			$opengateway->Param('card_num', $credit_card['number'], 'credit_card');
			$opengateway->Param('name', $credit_card['name'], 'credit_card');
			$opengateway->Param('exp_month', $credit_card['expiry_month'], 'credit_card');
			$opengateway->Param('exp_year', $credit_card['expiry_year'], 'credit_card');
			$opengateway->Param('cvv', $credit_card['security_code'], 'credit_card');

			// plan?
			$subscription = $this->GetSubscription($subscription_id);

			if (!empty($plan_id)) {
				$new_plan = $this->GetPlan($plan_id);

				if (empty($new_plan)) {
					return FALSE;
				}

				if ($new_plan['id'] != $subscription['plan_id']) {
					$opengateway->Param('plan_id', $new_plan['api_id']);
					$set_new_plan = TRUE;
				}
			}

			$response = $opengateway->Process();

			if (isset($response['response_code']) and $response['response_code'] == '104') {
				// we have to update all local subscription ID references
				$this->EE->db->update('exp_membrr_subscriptions',array('recurring_id' => $response['recurring_id']),array('recurring_id' => $subscription_id));
				$this->EE->db->update('exp_membrr_subscriptions',array('renewed_recurring_id' => $response['recurring_id']),array('renewed_recurring_id' => $subscription_id));
				$this->EE->db->update('exp_membrr_payments',array('recurring_id' => $response['recurring_id']),array('recurring_id' => $subscription_id));
				$this->EE->db->update('exp_membrr_channel_posts',array('recurring_id' => $response['recurring_id']),array('recurring_id' => $subscription_id));

				if (isset($set_new_plan) and !empty($set_new_plan)) {
					$this->EE->db->update('exp_membrr_subscriptions',array('plan_id' => $new_plan['id']),array('recurring_id' => $response['recurring_id']));
				}

				// update credit card
				if (is_array($credit_card) and isset($credit_card['number']) and !empty($credit_card['number'])) {
					$credit_card['number'] = trim(preg_replace('/[^0-9]/i','',$credit_card['number']));
					$card_last_four = substr($credit_card['number'], -4, 4);
				}
				else {
					$card_last_four = '';
				}

				if (strlen($card_last_four) != 4) {
					$card_last_four = '';
				}

				$this->EE->db->update('exp_membrr_subscriptions',array('card_last_four' => $card_last_four),array('recurring_id' => $response['recurring_id']));

				$member_id = $subscription['member_id'];
				$new_recurring_id = $response['recurring_id'];
				$old_recurring_id = $subscription_id;

				// call "membrr_update_cc" hook with: $member_id, $old_recurring_id, $new_recurring_id

				if ($this->EE->extensions->active_hook('membrr_update_cc') == TRUE)
				{
					$this->EE->extensions->call('membrr_update_cc', $member_id, $old_recurring_id, $new_recurring_id);
				    if ($this->EE->extensions->end_script === TRUE) return FALSE;
				}
			}

			return $response;
		}

		/**
		* Renewal Maintenance
		*
		* Link renewals to original subscriptions
		*
		* @param int $old_subscription
		* @param int $new_subscription
		*
		* @return boolean
		*/
		function RenewalMaintenance ($old_subscription, $new_subscription) {
			// we should also cancel the old subscription
			// cancel the existing subscription
			$this->CancelSubscription($old_subscription, FALSE, FALSE, TRUE);

			// mark as renewed
			$this->EE->db->update('exp_membrr_subscriptions', array('renewed_recurring_id' => $new_subscription), array('recurring_id' => $old_subscription));

			// mark new sub as renewal
			$this->EE->db->update('exp_membrr_subscriptions', array('renewal' => '1'), array('recurring_id' => $new_subscription));
			
			// if this subscription is linked to weblog posts and we're renewing, let's update those
			$result = $this->EE->db->where('recurring_id',$old_subscription)
								   ->get('exp_membrr_channel_posts');

			if ($result->num_rows() > 0) {
				$this->EE->db->update('exp_membrr_channel_posts', array(
														'recurring_id' => $new_subscription,
														'active' => '1'
														), array('recurring_id' => $old_subscription));
			}

			return TRUE;
		}

		/**
		* Record Subscription
		*
		* @param int $recurring_id
		* @param int $member_id
		* @param int $plan_id
		* @param date $next_charge_date
		* @param date $end_date
		* @param float $payment
		* @param string|boolean $coupon
		* @param array $credit_card
		*
		* @return boolean
		*/
		function RecordSubscription ($recurring_id, $member_id, $plan_id, $next_charge_date, $end_date, $payment, $coupon = FALSE, $credit_card = array()) {
			// get last 4 CC numbers
			if (is_array($credit_card) and isset($credit_card['number']) and !empty($credit_card['number'])) {
				$credit_card['number'] = trim(preg_replace('/[^0-9]/i','',$credit_card['number']));
				$card_last_four = substr($credit_card['number'], -4, 4);
			}
			else {
				$card_last_four = '';
			}

			if (strlen($card_last_four) != 4) {
				$card_last_four = '';
			}

			// create subscription record
			$insert_array = array(
								'recurring_id' => $recurring_id,
								'member_id' => $member_id,
								'plan_id' => $plan_id,
								'subscription_price' => $payment,
								'date_created' => date('Y-m-d H:i:s'),
								'date_cancelled' => '0000-00-00 00:00:00',
								'next_charge_date' => $next_charge_date,
								'card_last_four' => $card_last_four,
								'end_date' => $end_date,
								'expired' => '0',
								'cancelled' => '0',
								'active' => '1',
								'renewed_recurring_id' => '0',
								'expiry_processed' => '0',
								'coupon' => (!empty($coupon)) ? $coupon : ''
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

			if ($this->EE->extensions->active_hook('membrr_subscribe') == TRUE)
			{
			    $this->EE->extensions->call('membrr_subscribe', $member_id, $recurring_id, $plan_id, $end_date);
			    if ($this->EE->extensions->end_script === TRUE) return $response;
			}

			return TRUE;
		}

		/**
		* Record Payment
		*
		* @param int $subscription_id
		* @param int $charge_id
		* @param float $amount
		*
		* @return boolean
		*/
		function RecordPayment ($subscription_id, $charge_id, $amount) {
			$insert_array = array(
								'charge_id' => $charge_id,
								'recurring_id' => $subscription_id,
								'amount' => $amount,
								'date' => date('Y-m-d H:i:s'),
								'refunded' => '0'
							);

			$this->EE->db->insert('exp_membrr_payments',$insert_array);

			if ($this->EE->extensions->active_hook('membrr_payment') == TRUE) {
    			 $subscription = $this->GetSubscription($subscription_id);

    			 $this->EE->extensions->call('membrr_payment', $subscription['member_id'], $subscription_id, $subscription['plan_id'], $charge_id, $subscription['next_charge_date'], $amount);
    			 if ($this->EE->extensions->end_script === TRUE) return $response;
			}

			// set receipt cookie
			$this->EE->functions->set_cookie('membrr_charge_id', $charge_id, 86400);

			return TRUE;
		}

		/**
		* Refund Payment
		*
		* @param int $charge_id
		*
		* @return array
		*/
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

		/**
		* Set Next Charge
		*
		* @param int $subscription_id The subscription ID #
		* @param string $next_charge_date YYYY-MM-DD format of the next charge date
		*
		* @return boolean
		*/
		function SetNextCharge ($subscription_id, $next_charge_date) {
			$this->EE->db->update('exp_membrr_subscriptions',array('next_charge_date' => $next_charge_date),array('recurring_id' => $subscription_id));

			return TRUE;
		}

		/**
		* Get Channels
		*
		* Load array with protected channels
		*
		* @return array
		*/
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
								'posts' => $row['posts'],
								'plans' => explode('|',$row['plans'])
								);
			}

			return $channels;
		}

		/**
		* Get Channel
		*
		* Load information about a specific protected channel
		*
		* @param int $id
		* @param string $field (the field to match the ID to)
		*
		* @return array
		*/
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
							'posts' => $row['posts'],
							'plans' => explode('|',$row['plans'])
							);

			return $channel;
		}

		/**
		* Delete Channel
		*
		* Unprotect a channel
		*
		* @param int $id
		*
		* @return boolean
		*/
		function DeleteChannel ($id) {
			$this->EE->db->delete('exp_membrr_channels',array('protect_channel_id' => $id));

			return TRUE;
		}

		/**
		* Get Subscription for Channel
		*
		* Retrieve a subscription_id for a protected channel, if available
		*
		* @param int $channel_id
		* @param int $user
		* @param array $plans
		* @param int $posts How many posts can be linked to one sub?
		* @param int $sub_id Only try and match a specific sub
		*
		* @return int $recurring_id
		*/
		function GetSubscriptionForChannel ($channel_id, $user, $plans, $posts, $sub_id = FALSE) {
			$plans_query = '\'';

			if (is_null($plans))
			{
				return false;
			}

			foreach ($plans as $plan) {
				$plans_query .= $plan . '\', \'';
			}

			$plans_query = rtrim($plans_query, ', \'');

			$plans_query .= '\'';

			// are we looking for a specific sub?
			if ($sub_id != FALSE) {
				$this->EE->db->where('exp_membrr_subscriptions.recurring_id',$sub_id);
			}

			// get all subscriptions that match this channel
			$this->EE->db->select('recurring_id')
						 ->where('member_id',$user)
							 ->where('`plan_id` IN (' . $plans_query . ')',null,FALSE)
						 ->where('(`end_date` = \'0000-00-00 00:00:00\' OR `end_date` > NOW())',null,FALSE)
						 ->order_by('date_created','ASC');
			$result = $this->EE->db->get('exp_membrr_subscriptions');

			if ($result->num_rows() == 0) {
				return FALSE;
			}
			else {
				foreach ($result->result_array() as $subscription) {
					// if this is an unlimited # of posts per subscription, we just return
					// the first sub
					if ($posts == '0') {
						return $subscription['recurring_id'];
					}

					$this->EE->db->select('recurring_id')
								 ->where('recurring_id',$subscription['recurring_id'])
								 ->where('exp_membrr_channel_posts.channel_id',$channel_id)
								 ->where('active','1')
								 ->join('exp_channel_data','exp_channel_data.entry_id = exp_membrr_channel_posts.channel_entry_id','inner');

					$result = $this->EE->db->get('exp_membrr_channel_posts');

					if ($result->num_rows() < $posts) {
						return $subscription['recurring_id'];
					}

					// we must not have found a good subscription
					return FALSE;
				}
			}
		}

		/**
		* Update Address
		*
		* Update the address_book table and send an updated address to OG to update the customer's record
		*
		* @param int $member_id
		* @param string $first_name
		* @param string $last_name
		* @param string $street_address
		* @param string $address_2
		* @param string $city
		* @param string $region
		* @param string $region_other
		* @param string $country
		* @param string $postal_code
		* @param string $company (optional)
		* @param string $phone (optional)
		* @param string $email (optional)
		*
		* @return boolean
		*/
		function UpdateAddress ($member_id, $first_name, $last_name, $street_address, $address_2, $city, $region, $region_other, $country, $postal_code, $company = '', $phone = '', $email = '') {
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
									'postal_code' => $postal_code,
									'company' => $company,
									'phone' => $phone
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
					$server->Param('company',$company);
					$server->Param('phone',$phone);

					if (!empty($email)) {
						$server->Param('email', $email);
					}

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
									'postal_code' => $postal_code,
									'company' => $company,
									'phone' => $phone
								);
				$this->EE->db->insert('exp_membrr_address_book',$insert_array);
			}

			return TRUE;
		}

		/**
		* Get Address
		*
		* Retrieve the address from the local address book
		*
		* @param int $member_id
		*
		* @return array
		*/
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
						'postal_code' => '',
						'company' => '',
						'phone' => ''
					);
			}
		}

		/**
		* Get Payments
		*
		* Retrieve payment records matching filters
		*
		* @param int $offset
		* @param int $limit
		* @param int $filters['subscription_id']
		* @param int $filters['member_id']
		* @param int $filters['id']
		*
		* @return array
		*/
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

			$this->EE->db->select('exp_membrr_payments.*, exp_membrr_address_book.*,  exp_members.*, exp_membrr_plans.*,  exp_membrr_channel_posts.channel_entry_id AS `entry_id`, exp_channels.channel_name  AS `channel`', FALSE);
			$this->EE->db->join('exp_membrr_subscriptions','exp_membrr_subscriptions.recurring_id = exp_membrr_payments.recurring_id','left');
			$this->EE->db->join('exp_members','exp_membrr_subscriptions.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_membrr_plans','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
			$this->EE->db->join('exp_membrr_address_book','exp_membrr_address_book.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
			$this->EE->db->join('exp_membrr_channels','exp_membrr_channel_posts.channel_id = exp_membrr_channels.protect_channel_id','left');
			$this->EE->db->join('exp_channels','exp_membrr_channels.channel_id = exp_channels.channel_id','left');
			$this->EE->db->group_by('exp_membrr_payments.charge_id');
			$this->EE->db->order_by('exp_membrr_payments.date','DESC');
			$this->EE->db->where('exp_membrr_payments.charge_id >','0');
			$this->EE->db->limit($limit, $offset);

			$result = $this->EE->db->get('exp_membrr_payments');

			$payments = array();

			foreach ($result->result_array() as $row) {
				$payments[] = array(
								'id' => $row['charge_id'],
								'recurring_id' => $row['recurring_id'],
								'member_id' => $row['member_id'],
								'user_screenname' => $row['screen_name'],
								'user_username' => $row['username'],
								'user_groupid' => $row['group_id'],
								'amount' => money_format("%!^i",$row['amount']),
								'plan_name' => $row['plan_name'],
								'plan_id' => $row['plan_id'],
								'plan_description' => $row['plan_description'],
								'date' => $this->EE->localize->format_date('%M %j, %Y %g:%s %a', strtotime($row['date'])),
								'refunded' => $row['refunded'],
								'entry_id' => (empty($row['entry_id'])) ? FALSE : $row['entry_id'],
								'channel' => (empty($row['entry_id'])) ? FALSE : $row['channel'],
								'first_name' => $row['first_name'],
								'last_name' => $row['last_name'],
								'address' => $row['address'],
								'address_2' => $row['address_2'],
								'city' => $row['city'],
								'region' => $row['region'],
								'region_other' => $row['region_other'],
								'country' => $row['country'],
								'postal_code' => $row['postal_code'],
								'company' => $row['company'],
								'phone' => $row['phone']
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
		* Retrieve array of subscriptions matching filters
		*
		* @param int $offset The offset for which to load subscriptions
		* @param array $filters Filter the results
		* @param int $filters['member_id'] The EE member ID
		* @param int $filters['id'] The subscription ID
		* @param int $filters['active'] Set to "1" to retrieve only active subscriptions and "0" only ended subs
		* @param int $filters['plan_id'] The plan ID
		* @param string $filters['search'] searches usernames, screen names, emails, prices, and plan names
		*
		* @return array
		*/
		function GetSubscriptions ($offset = FALSE, $limit = 50, $filters = array()) {
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['member_id']) && !empty($filters['member_id'])) {
					$this->EE->db->where('exp_membrr_subscriptions.member_id',$filters['member_id']);
				}
				else if (isset($filters['member_id']) && empty($filters['member_id']))
				{
					return array();
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

			$this->EE->db->select('exp_membrr_subscriptions.*, exp_membrr_subscriptions.recurring_id AS subscription_id, exp_members.*, exp_membrr_plans.*, exp_membrr_channel_posts.channel_entry_id AS entry_id, exp_channels.channel_name AS channel', FALSE);
			$this->EE->db->join('exp_members','exp_membrr_subscriptions.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_membrr_plans','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
			$this->EE->db->join('exp_membrr_channel_posts','exp_membrr_subscriptions.recurring_id = exp_membrr_channel_posts.recurring_id','left');
			$this->EE->db->join('exp_membrr_channels','exp_membrr_channel_posts.channel_id = exp_membrr_channels.protect_channel_id','left');
			$this->EE->db->join('exp_channels','exp_membrr_channels.channel_id = exp_channels.channel_id','left');
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
								'amount' => money_format("%!^i",$row['subscription_price']),
								'card_last_four' => $row['card_last_four'],
								'plan_name' => $row['plan_name'],
								'plan_id' => $row['plan_id'],
								'plan_description' => $row['plan_description'],
								'entry_id' => (empty($row['entry_id'])) ? FALSE : $row['entry_id'],
								'channel' => (empty($row['entry_id'])) ? FALSE : $row['channel'],
								'date_created' => $this->EE->localize->format_date('%M %j, %Y %g:%s %a', strtotime($row['date_created'])),
								'date_cancelled' => (!strstr($row['date_cancelled'],'0000-00-00')) ? $this->EE->localize->format_date('%M %j, %Y %g:%s %a', strtotime($row['date_cancelled'])) : FALSE,
								'next_charge_date' => ($row['next_charge_date'] != '0000-00-00') ? $this->EE->localize->format_date('%M %j, %Y', strtotime($row['next_charge_date'])) : FALSE,
								'end_date' => ($row['end_date'] == '0000-00-00 00:00:00') ? FALSE : $this->EE->localize->format_date('%M %j, %Y %g:%s %a', strtotime($row['end_date'])),
								'active' => ($row['active'] == '1') ? '1' : '0',
								'cancelled' => $row['cancelled'],
								'expired' => $row['expired'],
								'renewed' => (empty($row['renewed_recurring_id'])) ? FALSE : TRUE,
								'renewed_recurring_id' => $row['renewed_recurring_id'],
								'coupon' => $row['coupon']
							);
			}

			if (empty($subscriptions)) {
				return FALSE;
			}
			else {
				return $subscriptions;
			}
		}

		/**
		* Get Subscription
		*
		* @param int $subscription_id
		*
		* @return array
		*/
		function GetSubscription ($subscription_id) {
			$subscriptions = $this->GetSubscriptions(FALSE, 1, array('id' => $subscription_id));

			if (isset($subscriptions[0])) {
				return $subscriptions[0];
			}

			return FALSE;
		}

		/**
		* Get Plans
		*
		* Retrieve an array of plans
		*
		* @param int $filters['id']
		* @param array $filters['ids']
		* @param int $filters['active']
		*
		* @return array
		*/
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
								'price' => money_format("%!^i",$row['plan_price']),
								'initial_charge' => money_format("%!^i",$row['plan_initial_charge']),
								'gateway' => $row['plan_gateway'],
								'interval' => $row['plan_interval'],
								'free_trial' => $row['plan_free_trial'],
								'occurrences' => $row['plan_occurrences'],
								'import_date' => $row['plan_import_date'],
								'for_sale' => $row['plan_active'],
								'redirect_url' => $row['plan_redirect_url'],
								'renewal_extend_from_end' => (empty($row['plan_renewal_extend_from_end'])) ? FALSE : TRUE,
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

		/**
		* Get Plan
		*
		* @param int $id
		*
		* @return array
		*/
		function GetPlan ($id) {
			$plans = $this->GetPlans(array('id' => $id));

			if (isset($plans[0])) {
				return $plans[0];
			}

			return FALSE;
		}

		/**
		* Delete Plan
		*
		* Delete a plan, and cancel all of its subscriptions
		*
		* @param int $plan_id
		*
		* @return boolean
		*/
		function DeletePlan ($plan_id) {
			$this->EE->db->update('exp_membrr_plans',array('plan_deleted' => '1','plan_active' => '0'),array('plan_id' => $plan_id));

			// cancel all subscriptions
			$subscribers = $this->GetSubscribersByPlan($plan_id);

			if ($subscribers) {
				foreach ($subscribers as $subscriber) {
					if (!$this->CancelSubscription($subscriber['recurring_id'])) {
						return FALSE;
					}
				}
			}

			return true;
		}

		/**
		* Cancel Subscription
		*
		* @param int $sub_id
		* @param boolean $make_api_call (default: FALSE) Shall we tell OG?
		* @param boolean $expired (default: FALSE) Is this expiring?
		*
		* @return boolean
		*/
		function CancelSubscription ($sub_id, $make_api_call = TRUE, $expired = FALSE, $renewed = FALSE) {
			if (!$subscription = $this->GetSubscription($sub_id)) {
				return FALSE;
			}

			// calculate end_date
			$end_date = $this->_calculate_end_date($subscription['end_date'], $subscription['next_charge_date'], $subscription['date_created']);

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
		 	elseif ($expired == FALSE and $renewed == FALSE) {
		 		$update_array['cancelled'] = '1';
		 	}

		 	$this->EE->db->update('exp_membrr_subscriptions',$update_array,array('recurring_id' => $subscription['id']));

		 	// call "membrr_cancel" hook with: member_id, subscription_id, plan_id, end_date
			if ($this->EE->extensions->active_hook('membrr_before_cancel') == TRUE)
			{
			    $this->EE->extensions->call('membrr_before_cancel', $subscription['member_id'], $subscription['id'], $subscription['plan_id'], $subscription['end_date']);
			    if ($this->EE->extensions->end_script === TRUE) return;
			}

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
			if ($this->EE->extensions->active_hook('membrr_cancel') == TRUE)
			{
			    $this->EE->extensions->call('membrr_cancel', $subscription['member_id'], $subscription['id'], $subscription['plan_id'], $subscription['end_date']);
			    if ($this->EE->extensions->end_script === TRUE) return;
			}

			return TRUE;
		}

		/**
		* Calculate End Date
		*
		* Calculate the end date for a subscription based on its current state
		*
		* @param datetime $end_date
		* @param date $next_charge_date
		* @param datetime $start_date
		*
		* @return datetime $end_date
		*/
		function _calculate_end_date ($end_date, $next_charge_date, $start_date) {
			$next_charge_date = date('Y-m-d',strtotime($next_charge_date));
			$end_date = date('Y-m-d H:i:s',strtotime($end_date));

			if ($next_charge_date != '0000-00-00' and (strtotime($next_charge_date) + (60*60*24)) > time()) {
				// there's a next charge date which won't be renewed, so we'll end it then
				// we must also account for their signup time
				$time_created = date('H:i:s',strtotime($start_date));
				$end_date = $next_charge_date . ' ' . $time_created;
			}
			elseif ($end_date != '0000-00-00 00:00:00' and (strtotime($end_date) + (60*60*24)) > time()) {
				// there is a set end_date
				$end_date = $end_date;
			}
			else {
				// for some reason, neither a next_charge_date or an end_date exist
				// let's end this now
				$end_date = date('Y-m-d H:i:s');
			}

			return $end_date;
		}

		/**
		* Get Subscribers by Plan
		*
		* Retrive subscriptions based on the plan_id filter
		*
		* @param int $plan_id
		*
		* @return array
		*/
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

		/**
		* End Now
		*
		* Mark a subscription as ending right now, so that the expiry processing will occur in the constructor
		*
		* @param int $recurring_id
		*
		* @return boolean
		*/
		function EndNow ($recurring_id) {
			$this->EE->db->update('exp_membrr_subscriptions', array('end_date' => date('Y-m-d H:i:s')), array('recurring_id' => $recurring_id));

			return TRUE;
		}

		/**
		* Save Data
		*
		* Save some temporary data
		*
		* @param string $data
		*
		* @return int $id
		*/
		function SaveTempData ($string) {
			$this->EE->db->insert('exp_membrr_temp', array('temp_data' => $string));

			return $this->EE->db->insert_id();
		}

		/**
		* Get Data
		*
		* Retrieve some data
		*
		* @param int $id
		*
		* @return string $data
		*/
		function GetTempData ($id) {
			$result = $this->EE->db->where('temp_id', $id)
								   ->get('exp_membrr_temp');

			if ($result->num_rows() !== 1) {
				return FALSE;
			}
			else {
				return $result->row()->temp_data;
			}
		}

		/**
		* Get Config
		*
		* Retrieve the names/values in the config table
		*
		* @return array
		*/
		function GetConfig () {
			$result = $this->EE->db->get('exp_membrr_config');

			if ($result->num_rows() == 0) {
				return FALSE;
			}
			else {
				$settings = $result->row_array();

				$settings['update_email'] = (empty($settings['update_email'])) ? FALSE : TRUE;
				$settings['use_captcha'] = (empty($settings['use_captcha'])) ? FALSE : TRUE;

				return $settings;
			}
		}

		/**
		* Get Regions
		*
		* Return an array of all regions and their shortcodes
		*
		* @return array
		*/
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

		/**
		* Get Countries
		*
		* Return an array of all countries and their shortcodes
		*
		* @return array
		*/
		function GetCountries () {
	    	$countries = $this->EE->db->where('available','1')->get('exp_membrr_countries');

	    	$return = array();

	    	foreach ($countries->result_array() as $country) {
	    		$return[$country['iso2']] = $country['name'];
	    	}

	    	return $return;
		}
	}
}

/**
* Define money_format
*/
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