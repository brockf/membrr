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

/**
* Membrr Module
*
* Enables frontend template tags:
*	- {exp:membrr:order_form}
*	- {exp:membrr:quick_order_form} e.g., {exp:membrr:quick_order_form button="Subscribe Now!" plan_id="X"}
*	- {exp:membrr:subscriptions}{/exp:membrr:subscriptions} e.g. {exp:membrr:subscriptions id="X" date_format="Y-m-d"}
*	- {exp:membrr:subscribed plan="X"}{/exp:membrr:subscribed}
*	- {exp:membrr:not_subscribed plan="X"}{/exp:membrr:not_subscribed}
*	- {exp:membrr:payments}{/exp:membrr:payments} e.g. {exp:membrr:payments id="X" subscription_id="X" offset="X" limit="X" date_format="Y-m-d"}
*	- {exp:membrr:plans}{/exp:membrr:plans} e.g., {exp:membrr:plans id="X" for_sale="1"}{/exp:membrr:plans}
*	- {exp:membrr:cancel id="X"}{/exp:membrr:cancel} (returns {if cancelled} and {if failed} to tagdata)
*	- {exp:membrr:has_subscription_for_channel channel="ad_posts"}<!-- HTML -->{/exp...channel} or {exp:membrr:has_subscription_for_channel channel="22"}<!-- HTML -->{/exp...channel} 
*	- {exp:membrr:no_subscription_for_channel channel="ad_posts"}<!-- HTML -->{/exp...channel} or {exp:membrr:no_subscription_for_channel channel="22"}<!-- HTML -->{/exp...channel}
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/

class Membrr {
	var $return_data	= ''; 
	var $membrr; // holds the Membrr_EE class
	var $EE; // holds the EE superobject

    // -------------------------------------
    //  Constructor
    // -------------------------------------

    function Membrr()
    {
		// initialize
		if (!class_exists('Membrr_EE')) {
			require(dirname(__FILE__) . '/class.membrr_ee.php');
		}
		$this->membrr = new Membrr_EE;
		
		$this->EE =& get_instance();
		
		$this->EE->lang->loadfile('membrr');
		
		$this->return_data = "You must reference a specific function of the plugin, ie. {exp:membrr:function_to_call}";
    }
    
    /*
    * Print error
    *
    * @param string $error The text of the error
    * @return string HTML-formatted error text
    */
    function error ($error) {
    	return '<p class="error">' . $error . '</p>';
    }
    
    /**
    * Do we have an available subscription for a channel?
    *
    * @param int/string Channel ID or name
    *
    * @returns string Embedded HTML if yes, nothing if no
    */
    function has_subscription_for_channel () {    	
    	$channel = $this->EE->TMPL->fetch_param('channel');
    	
    	if (empty($channel)) {
    		return 'You are missing the required "channel" parameter to specify which active plan to check for.';
    	}
    	
    	if (!$this->EE->session->userdata('member_id')) {
    		return 'User is not logged in.';
    	}
    	
    	// we'll look by ID if they gave us a number, else by channel name
    	$field = (is_numeric($channel)) ? 'channel_id' : 'channel_name';
    	
    	$channel = $this->membrr->GetChannel($channel, $field);
    	
    	if ($this->membrr->GetSubscriptionForChannel($this->EE->session->userdata('member_id'),$channel['plans'],$channel['one_post'])) {
    		$return = $this->EE->TMPL->tagdata;
    	}
    	else {
    		$return = '';
    	}
    	
    	$this->return_data = $return;
		
		return $return;
    }
    
    /**
    * Do we NOT have an available subscription for a channel?
    *
    * @param int/string Channel ID or name
    *
    * @returns string Embedded HTML if yes, nothing if no
    */
    function no_subscription_for_channel () {    	
    	$channel = $this->EE->TMPL->fetch_param('channel');
    	
    	if (empty($channel)) {
    		return 'You are missing the required "channel" parameter to specify which active plan to check for';
    	}
    	
    	if (!$this->EE->session->userdata('member_id')) {
    		return 'User is not logged in.';
    	}
    	
    	// we'll look by ID if they gave us a number, else by channel name
    	$field = (is_numeric($channel)) ? 'channel_id' : 'channel_name';
    	
    	$channel = $this->membrr->GetChannel($channel, $field);
    	
    	if (!$this->membrr->GetSubscriptionForChannel($this->EE->session->userdata['member_id'],$channel['plans'],$channel['one_post'])) {
    		$return = $this->EE->TMPL->tagdata;
    	}
    	else {
    		$return = '';
    	}
    	
    	$this->return_data = $return;
		
		return $return;
    }
    
    /**
    * Cancels an active subscription
    *
    * @return boolean TRUE upon success, FALSE upon failure
    */
    function cancel () {
    	$id = $this->EE->TMPL->fetch_param('id');
    	
    	if (empty($id)) {
    		return 'You are missing the required "id" parameter to specify which subscription to cancel.';
    	}
    	
    	// can this person cancel this sub?
    	$filters = array(
    					'member_id' => $this->EE->session->userdata('member_id'),
    					'active' => '1',
    					'id' => $id
    				);
    	$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);
    	
    	$owns_sub = FALSE;
    	foreach ((array)$subscriptions as $subscription) {
    		if ($subscription['id'] == $id) {
    			$owns_sub = TRUE;
    		}
    	}
    	
    	$cancelled_sub = FALSE;
    	
    	if ($owns_sub == TRUE and $this->membrr->CancelSubscription($id) == TRUE) {
    		$cancelled_sub = TRUE;
    	}
    	
    	// prep conditionals
		$conditionals = array();
		
		$conditionals['cancelled'] = ($cancelled_sub == TRUE) ? TRUE : FALSE;
		$conditionals['failed'] = ($cancelled_sub == FALSE) ? TRUE : FALSE;
				
		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $conditionals);
		
		$this->return_data = $this->EE->TMPL->tagdata;
		
		return $this->return_data;
    }
    
    /**
    * Returns HTML between tags if user has the active plan
    *
    * @param int|string plan A single plan_id or multiple plan_id's separated by pipes
    *
    * @return string Tag data, if active plan of ID exists
    */
    function subscribed () {    	
    	$id = $this->EE->TMPL->fetch_param('plan');
    	
    	if (empty($id)) {
    		return 'You are missing the required "plan" parameter to specify which active plan to check for';
    	}
    	
    	$filters = array();
    	
    	$member_id = $this->EE->session->userdata('member_id');
		
		if (empty($member_id)) { 
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}
		
		$filters['active'] = '1';
		
		$return = '';
		if (strpos($id, '|') !== FALSE) {
			// we have an array of plan ID's
			$ids = explode('|', trim($id));
			
			foreach ($ids as $id) {
				$filters['plan_id'] = $id;
				
				$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);
    	
		    	if (is_array($subscriptions) and !empty($subscriptions)) {
		    		$return = $this->EE->TMPL->tagdata;
		    	}
			}	
		}
		else {
			$filters['plan_id'] = $id;
				
			$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);
	
	    	if (is_array($subscriptions) and !empty($subscriptions)) {
	    		$return = $this->EE->TMPL->tagdata;
	    	}
		}
		
		$this->return_data = $return;
		return $return;
    }
    
    /**
    * Returns HTML between tags if user does not have the active plan
    *
    * @return string Tag data, if active plan of ID exists
    */
    function not_subscribed () {    	
    	$id = $this->EE->TMPL->fetch_param('plan');
    	
    	if (empty($id)) {
    		return 'You are missing the required "plan" parameter to specify which active plan to check for';
    	}
    	
    	$filters = array();
    	
    	$member_id = $this->EE->session->userdata('member_id');
		
		if (empty($member_id)) { 
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}
		
		$filters['active'] = '1';
		
		$return = $this->EE->TMPL->tagdata;
		if (strpos($id, '|') !== FALSE) {
			// we have an array of plan ID's
			$ids = explode('|', trim($id));
			
			foreach ($ids as $id) {
				$filters['plan_id'] = $id;
				
				$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);
    	
		    	if (is_array($subscriptions) and !empty($subscriptions)) {
		    		$return = '';
		    	}
			}	
		}
		else {
			$filters['plan_id'] = $id;
				
			$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);
	
	    	if (is_array($subscriptions) and !empty($subscriptions)) {
	    		$return = '';
	    	}
		}
		
		$this->return_data = $return;
		return $return;
    }
    
    /**
    * Displays plans
    * 
    * For each plan, replaces tags:
    *	- {plan_id}
    *	- {name}
    *	- {description}
    *	- {interval}
    *	- {free_trial}
    *	- {occurrences}
    *	- {price}
    *	- {total_subscribers}
    *
    * Conditionals
    *	- All above tags
    *
    * e.g.  {exp:membrr:plans id="X" for_sale="1"}
    *
    * @param int $id Retrieve only this plan's details
    * @param int $for_sale Set to "0" to retrieve all plans (Default: Only for sale plans)
    * @param string $ids A string of ID's to pull information about multiple plans (e.g., 1001|1002|10003)
    *
    * @return string Each plan in the format of the HTML between the plugin tags.
	*/
	
	function plans () {		
		$filters = array();
		
		if ($this->EE->TMPL->fetch_param('id') and !strstr($this->EE->TMPL->fetch_param('id'), '|')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}
		elseif ($this->EE->TMPL->fetch_param('id')) {
			$filters['ids'] = explode('|',$this->EE->TMPL->fetch_param('id'));
		}
		
		if ($this->EE->TMPL->fetch_param('for_sale')) {
			$filters['for_sale'] = $this->EE->TMPL->fetch_param('active');
		}
		else {
			$filters['active'] = '1';
		}
		
		$plans = $this->membrr->GetPlans($filters);
		
		if (empty($plans)) {
			// no plans matching parameters
			return '';
		}
		
		$return = '';
		
		foreach ($plans as $plan) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;
			
			$variables[0] = array(
								'plan_id' => $plan['id'],
								'name' => $plan['name'],
								'description' => $plan['description'],
								'free_trial' => $plan['free_trial'],
								'interval' => $plan['interval'],
								'occurrences' => $plan['occurrences'],
								'price' => money_format("%!i",$plan['price']),
								'total_subscribers' => $plan['num_subscribers']
							);
			
			// swap in the variables
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);
			
			// add to return HTML
			$return .= $sub_return;
			
			unset($sub_return);
		}
		
		$this->return_data = $return;
		
		return $return;
	}
	
	/**
    * Displays payments for the logged in user
    * 
    * For each payment, replaces tags:
    *	- {charge_id}
    *	- {subscription_id}
    *	- {amount}
    *	- {date}
    *	- {plan_name}
    *	- {plan_id}
    *	- {channel} (if exists)
    *	- {entry_id} (if exists)
    *
    * Conditionals
    *	- All above tags
    *
    * e.g.  {exp:membrr:payments id="X" subscription_id="X" offset="X" limit="X" date_format="Y-m-d"}
    *
    * @param string $date_format The PHP format of dates
    * @param int $id Retrieve only this payment's details
    * @param int $offset Offset results by this number
    * @param int $limit Retrieve only this many results
    * @param int $subscription_id Retrieve only payments related to this subscription
    * @return string Each payment in the format of the HTML between the plugin tags.
	*/
	
	function payments () {
		$member_id = $this->EE->session->userdata('member_id');
		
		$filters = array();
		
		if (empty($member_id)) { 
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}
		
		if ($this->EE->TMPL->fetch_param('id')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}
		
		if ($this->EE->TMPL->fetch_param('subscription_id')) {
			$filters['subscription_id'] = $this->EE->TMPL->fetch_param('subscription_id');
		}
		
		if ($this->EE->TMPL->fetch_param('offset')) {
			$offset = $this->EE->TMPL->fetch_param('offset');
		}
		else {
			$offset = 0;
		}
		
		if ($this->EE->TMPL->fetch_param('limit')) {
			$limit = $this->EE->TMPL->fetch_param('limit');
		}
		else {
			$limit = 50;
		}
		
		$payments = $this->membrr->GetPayments(0, $limit, $filters);
		
		if (empty($payments)) {
			// no payments matching parameters
			return '';
		}
		
		$return = '';
		
		foreach ($payments as $payment) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;
			
			if ($this->EE->TMPL->fetch_param('date_format')) {
				$payment['date'] = (!empty($payment['date'])) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($payment['date'])) : FALSE;
			}
			
			$variables = array();
			$variables[0] = array(
						'charge_id' =>  $payment['id'],
						'subscription_id' =>  $payment['recurring_id'],
						'amount' =>  $payment['amount'],
						'date' => $payment['date'],
						'plan_name' => $payment['plan_name'],
						'plan_id' => $payment['plan_id'],
						'plan_description' => $payment['plan_description'],
						'channel' => $payment['channel'],
						'entry_id' => $payment['entry_id']
							);
			
			// swap in the variables
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);
			
			// add to return HTML
			$return .= $sub_return;
			
			unset($sub_return);
		}
		
		$this->return_data = $return;
		
		return $return;
	}
    
    /**
    * Displays subscription(s) for the logged in user
    * 
    * For each subscription, replaces tags:
    *	- {subscription_id}
    *	- {recurring_fee}
    *	- {date_created}
    *	- {date_cancelled} (if exists)
    *	- {next_charge_date} (if exists)
    *	- {end_date} (if exists)
    *	- {plan_name}
    *	- {plan_id}
    *	- {channel} (if exists)
    *	- {entry_id} (if exists)
    *
    * Conditionals:
    *	- All above tag
    *   - {if active}{/if}   (still auto-recurring)
    *   - {if user_cancelled}{/if}  (the user cancelled this actively)
    *   - {if expired}{/if}   (it expired)
    *
    * @param string $date_format The PHP format of dates
    * @param int $inactive Set to "1" to retrieve ended subscriptions
    * @param int $id Set to the subscription ID to retrieve only that subscription
    * @return string Each subscription in the format of the HTML between the plugin tags.
	*/
	
	function subscriptions () {		
		$member_id = $this->EE->session->userdata('member_id');
		
		$filters = array();
		
		if (empty($member_id)) { 
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}
		
		if ($this->EE->TMPL->fetch_param('inactive') == '1') {
			$filters['active'] = '0';
		}
		
		if ($this->EE->TMPL->fetch_param('id')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}
		
		$limit = ($this->EE->TMPL->fetch_param('limit')) ? $this->EE->TMPL->fetch_param('limit') : 100;
		
		$subscriptions = $this->membrr->GetSubscriptions(0,$limit,$filters);
		
		if (empty($subscriptions)) {
			// no subscriptions matching parameters
			return '';
		}
		
		$return = '';
		
		foreach ($subscriptions as $subscription) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;
			
			if ($this->EE->TMPL->fetch_param('date_format')) {
				$subscription['date_created'] = date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['date_created']));
				$subscription['date_cancelled'] = ($subscription['date_cancelled'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['date_cancelled'])) : FALSE;
				$subscription['next_charge_date'] = ($subscription['next_charge_date'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['next_charge_date'])) : FALSE;
				$subscription['end_date'] = ($subscription['end_date'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['end_date'])) : FALSE;
			}
			
			$variables = array();
			$variables[0] = array(
							'subscription_id' => $subscription['id'],
							'recurring_fee' => $subscription['amount'],
							'date_created' => $subscription['date_created'],
							'date_cancelled' => $subscription['date_cancelled'],
							'next_charge_date' => $subscription['next_charge_date'],
							'end_date' => $subscription['end_date'],
							'plan_name' => $subscription['plan_name'],
							'plan_description' => $subscription['plan_description'],
							'plan_id' => $subscription['plan_id'],
							'channel' => $subscription['channel'],
							'entry_id' => $subscription['entry_id']
						);
			
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);
						
			// prep conditionals
			$conditionals = array();
			
			// put all data in conditionals so they can use {if subscription_id == "4343"} etc.
			$conditionals['active'] = ($subscription['active'] == '1') ? TRUE : FALSE;
			// user_cancelled is deprecated
			$conditionals['user_cancelled'] = ($subscription['cancelled'] == '1') ? TRUE : FALSE;
			$conditionals['cancelled'] = ($subscription['cancelled'] == '1') ? TRUE : FALSE;
			$conditionals['renewed'] = ($subscription['renewed'] == TRUE) ? TRUE : FALSE;
			$conditionals['expired'] = ($subscription['expired'] == '1') ? TRUE : FALSE;
			
			$sub_return = $this->EE->functions->prep_conditionals($sub_return, $conditionals);
			
			// add to return HTML
			$return .= $sub_return;
			
			unset($sub_return);
		}
		
		$this->return_data = $return;
		
		return $return;
	}
	
	/**
    * Displays a customizable update credit card form
    *
    * @param int $subscription_id
    * @param string $redirect_url
    *
    * Requires upon POST submission: "subscription_id", logged in user, credit card fields
    *
    * Note: You should validate form fields client side to avoid unnecessary API calls.
    *
    * Submission requires:
    *	user be logged in
    *	subscription_id
	*	cc_number
	*	cc_name
	*	cc_expiry_month
	*	cc_expiry_year
	*   cc_cvv2
	*	membrr_update_form (hidden field) == 1
	*	if sending a region, use "region" for North American regions and "region_other" for non-NA regions
    *
    * @returns all form related data:
    *
    *	first_name
	*	last_name
	*	address
	*	address_2
	*	city
	*	region
	*	region_other
	*	country
	*	postal_code
	*	email
	*	region_options
	*	region_raw_options (array of regions)
	*	country_options
	*	country_raw_options (array of countries)
	*	cc_expiry_month_options
	*	cc_expiry_year_options
	*	errors
	*	form_action (the current URL)
	*	form_method (POST)
	*	subscription_id of the subscription to be updated
    */
    function update_form () {
    	$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');
			    	
    	// user must be logged in
    	if ($this->EE->session->userdata('member_id') == '' or $this->EE->session->userdata('member_id') == '0') {
    		return 'Membrr for EE2 **WARNING** This user is not logged in.  This form should be seen by only logged in members.';
    	}
    	
    	if (!$this->EE->TMPL->fetch_param('subscription_id')) {
    		return 'You are missing the required "subscription_id" parameter.';
    	}
    	
    	if (!$this->EE->TMPL->fetch_param('redirect_url')) {
    		return 'You are missing the required "redirect_url" parameter.  This is the URL to send the user to after they have updated their subscription.';
    	}
    	
    	// store all errors in here
    	$errors = array();
    	
    	// get subscription and validate member ownership
    	$subscription_id = ($this->EE->input->post('subscription_id')) ? $this->EE->input->post('subscription_id') : $this->EE->TMPL->fetch_param('subscription_id');
    	$subscription = $this->membrr->GetSubscription($subscription_id);
    	
    	if (empty($subscription) or $subscription['member_id'] != $this->EE->session->userdata('member_id')) {
    		return 'Invalid subscription ID.';
    	}
    	
		// handle potential form submission
		if ($this->EE->input->post('membrr_update_form')) {
			// validate email if it is there
			if ($this->EE->input->post('email')) {
				$this->EE->form_validation->set_rules('email','lang:membrr_order_form_customer_email','trim|valid_email');
			}
			// and credit card...
			$this->EE->form_validation->set_rules('cc_number','lang:membrr_order_form_cc_number','trim|numeric');
			$this->EE->form_validation->set_rules('cc_name','lang:membrr_order_form_cc_name','trim');
			$this->EE->form_validation->set_rules('cc_expiry_month','lang:membrr_order_form_cc_expiry_month','trim|numeric');
			$this->EE->form_validation->set_rules('cc_expiry_year','lang:membrr_order_form_cc_expiry_year','trim|numeric');
			
			if ($this->EE->session->userdata('member_id') and $this->EE->form_validation->run() !== FALSE) {								
				$member_id = $this->EE->session->userdata('member_id');
				
				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
			    $member = $member->row_array();
				
				// update address book
				if ($this->EE->input->post('address')) {
					$this->membrr->UpdateAddress($member_id,
												 $this->EE->input->post('first_name'),
												 $this->EE->input->post('last_name'),
												 $this->EE->input->post('address'),
												 $this->EE->input->post('address_2'),
												 $this->EE->input->post('city'),
												 $this->EE->input->post('region'),
												 $this->EE->input->post('region_other'),
												 $this->EE->input->post('country'),
												 $this->EE->input->post('postal_code')
												);
				}
								
				// prep arrays to send to Membrr_EE class	
				$credit_card = array(
									'number' => $this->EE->input->post('cc_number'),
									'name' => $this->EE->input->post('cc_name'),
									'expiry_month' => $this->EE->input->post('cc_expiry_month'),
									'expiry_year' => $this->EE->input->post('cc_expiry_year'),
									'security_code' => $this->EE->input->post('cc_cvv2')
								);
			
				$response = $this->membrr->UpdateCC($subscription['id'], $credit_card);
							
				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] != '104') {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					// redirect to URL
					header('Location: ' . $this->EE->TMPL->fetch_param('redirect_url'));
					die();
				}
			}	
		}
		
		if (validation_errors()) {
			// neat little hack to get an array of errors
			$form_errors = validation_errors('', '[|]');
			$form_errors = explode('[|]',$form_errors);
			
			foreach ($form_errors as $form_error) {
				$errors[] = $form_error;
			}
		}
    	
    	// get content of templates
    	$sub_return = $this->EE->TMPL->tagdata;
			
		// get customer information
		$address = $this->membrr->GetAddress($this->EE->session->userdata('member_id'));
		
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
	    $member = $member->row_array();
		
		$variables = array(
						'first_name' => ($this->EE->input->post('first_name')) ? $this->EE->input->post('first_name') : $address['first_name'], 
						'last_name' => ($this->EE->input->post('last_name')) ? $this->EE->input->post('last_name') : $address['last_name'],
						'address' => ($this->EE->input->post('address')) ? $this->EE->input->post('address') : $address['address'],
						'address_2' => ($this->EE->input->post('address_2')) ? $this->EE->input->post('address_2') : $address['address_2'],
						'city' => ($this->EE->input->post('city')) ? $this->EE->input->post('city') : $address['city'],
						'region' => ($this->EE->input->post('region')) ? $this->EE->input->post('region') : $address['region'],
						'region_other' => ($this->EE->input->post('region_other')) ? $this->EE->input->post('region_other') : $address['region_other'], 
						'country' => ($this->EE->input->post('country')) ? $this->EE->input->post('country') : $address['country'],
						'postal_code' => ($this->EE->input->post('postal_code')) ? $this->EE->input->post('postal_code') : $address['postal_code'],
						'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email']
					);
					
		// subscription_id
		$variables['subscription_id'] = $subscription['id'];
					
		// prep credit card fields
		
		// prep expiry month options
		$months = '';
		for ($i = 1; $i <= 12; $i++) {
	       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
	       $month_text = date('M',strtotime('2010-' . $month . '-01'));
	       
	       $months .= '<option value="' . $month . '">' . $month . ' - ' . $month_text . '</option>' . "\n";
	    }
	    
	    $variables['cc_expiry_month_options'] = $months;
	    
	    // prep same for years
	    
	    $years = '';
		$now = date('Y');
		$future = $now + 10;
		for ($i = $now; $i <= $future; $i++) {
			$years .= '<option value="' . $i . '">' . $i . '</option>';
		}
	    
	    $variables['cc_expiry_year_options'] = $years;
	    
	    // prep regions
	    $regions = $this->membrr->GetRegions();
		
		if ($this->EE->input->post('region')) {
			$customer_region = $this->EE->input->post('region');
		}
		elseif (isset($address['region'])) {
			$customer_region = $address['region'];
		}
		else {
			$customer_region = '';
		}
		
		$return = '';
		foreach ($regions as $region_code => $region) {
			$selected = ($customer_region == $region_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $region_code . '"' . $selected . '>' . $region . '</option>';
		}
		
		$region_options = $return;
		
		$variables['region_options'] = $region_options;
		reset($regions);
		$variables['region_raw_options'] = $regions;
							
		// field: customer country
		$countries = $this->membrr->GetCountries();
		
		if ($this->EE->input->post('country')) {
			$customer_country = $this->EE->input->post('country');
		}
		elseif (isset($address['country'])) {
			$customer_country = $address['country'];
		}
		else {
			$customer_country = '';
		}
		
		$return = '';
		foreach ($countries as $country_code => $country) {
			$selected = ($customer_country == $country_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $country_code . '"' . $selected . '>' . $country . '</option>';
		}

		$country_options = $return;
		
		$variables['country_options'] = $country_options;
		reset($countries);
		$variables['country_raw_options'] = $countries;
		
		// prep form action
	    $variables['form_action'] = ($_SERVER["SERVER_PORT"] == "443") ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
	    $variables['form_method'] = 'POST';
	    
	    // prep errors
	    $variables['errors_array'] = $errors;
	    
	    $error_string = '';
	    foreach ($errors as $error) {
	    	$error_string .= '<div>' . $error . '</div>';
	    }
	    $variables['errors'] = $error_string;
	    
	    // parse the tag content with our new variables
	    $var_data = array();
	    $var_data[0] = $variables;
			
		$sub_return = $this->EE->TMPL->parse_variables($sub_return, $var_data);
    	
    	$this->return_data = $sub_return;
    	
    	return $sub_return;
    }
	
	/**
    * Displays a customizable order form
    *
    * Requires upon POST submission: "plan_id", logged in user, Customer information fields
    *
    * Note: You should validate form fields client side to avoid unnecessary API calls.  You should use {exp:membrr:plans}
    * to get a list of plans if you want the user to select their plan.
    *
    * Submission requires:
    *	user be logged in
    *	plan_id
	*	cc_number (if not PayPal and subscription not free)
	*	cc_name (if not PayPal and subscription not free)
	*	cc_expiry_month (if not PayPal and subscription not free)
	*	cc_expiry_year (if not PayPal and subscription not free)
	*   cc_cvv2 (if not PayPal and subscription not free)
	*	membrr_order_form (hidden field) == 1
	*	if sending a region, use "region" for North American regions and "region_other" for non-NA regions
    *
    * @returns all form related data:
    *
    *	first_name
	*	last_name
	*	address
	*	address_2
	*	city
	*	region
	*	region_other
	*	country
	*	postal_code
	*	email
	*	region_options
	*	region_raw_options (array of regions)
	*	country_options
	*	country_raw_options (array of countries)
	*	gateway_options
	*	gateway_raw_optiosn (array of gateways)
	*	cc_expiry_month_options
	*	cc_expiry_year_options
	*	errors_array
	*	errors
	*	form_action (the current URL)
	*	form_method (POST)
	*
    */
    function order_form () {
    	$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');
			    	
    	// user must be logged in
    	if ($this->EE->session->userdata('member_id') == '' or $this->EE->session->userdata('member_id') == '0') {
    		return 'Membrr for EE2 **WARNING** This user is not logged in.  This form should be seen by only logged in members.';
    	}
    	
    	// store all errors in here
    	$errors = array();
    	
		// handle potential form submission
		if ($this->EE->input->post('membrr_order_form')) {
			$plan_id = $this->EE->input->post('plan_id');
			$plan = $this->membrr->GetPlan($plan_id);
			if ((float)$plan['price'] == 0) {
				$free_plan = TRUE;
			}
			else {
				$free_plan = FALSE;
			}
			
			// there's a submission
			$this->EE->form_validation->set_rules('plan_id','lang:membrr_order_form_select_plan','trim|required');
			
			// validate email if it is there
			if ($this->EE->input->post('email')) {
				$this->EE->form_validation->set_rules('email','lang:membrr_order_form_customer_email','trim|valid_email');
			}
			// and credit card if they are there
			if ($this->EE->input->post('cc_number')) {
				$this->EE->form_validation->set_rules('cc_number','lang:membrr_order_form_cc_number','trim|numeric');
			}
			if ($this->EE->input->post('cc_expiry_month')) {
				$this->EE->form_validation->set_rules('cc_expiry_month','lang:membrr_order_form_cc_expiry_month','trim|numeric');
			}
			if ($this->EE->input->post('cc_expiry_year')) {
				$this->EE->form_validation->set_rules('cc_expiry_year','lang:membrr_order_form_cc_expiry_year','trim|numeric');
			}
			
			// validate renewal subscription if we have one
			if ($this->EE->input->post('renew')) {
				$renewed_subscription = $this->membrr->GetSubscription($this->EE->input->post('renew'));
				
				if (empty($renewed_subscription)) {
					$errors[] = 'The subscription you are trying to renew does not exist.';
				}
				elseif ($renewed_subscription['member_id'] != $this->EE->session->userdata('member_id')) {
					$errors[] = 'You are trying to renew a subscription that is not yours.';
				}
				else {
					// looks good, let's mark this as a renewal
					$renew_subscription = $renewed_subscription['id'];
				}
			}
			else {
				$renew_subscription = FALSE;
			}
			
			$plan = (is_numeric($this->EE->input->post('plan_id'))) ? $this->membrr->GetPlan($this->EE->input->post('plan_id')) : FALSE;
			
			if ($this->EE->form_validation->run() != FALSE and $this->EE->session->userdata('member_id') and !empty($plan) and empty($errors)) {								
				$plan_id = $plan['id'];
				$member_id = $this->EE->session->userdata('member_id');
				
				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
			    $member = $member->row_array();
				
				// update address book
				if ($this->EE->input->post('address')) {
					$this->membrr->UpdateAddress($member_id,
												 $this->EE->input->post('first_name'),
												 $this->EE->input->post('last_name'),
												 $this->EE->input->post('address'),
												 $this->EE->input->post('address_2'),
												 $this->EE->input->post('city'),
												 $this->EE->input->post('region'),
												 $this->EE->input->post('region_other'),
												 $this->EE->input->post('country'),
												 $this->EE->input->post('postal_code')
												);
				}
								
				// prep arrays to send to Membrr_EE class	
				if ($this->EE->input->post('free') != '1' and $this->EE->input->post('cc_number') and $free_plan == FALSE) {			
					$credit_card = array(
										'number' => $this->EE->input->post('cc_number'),
										'name' => $this->EE->input->post('cc_name'),
										'expiry_month' => $this->EE->input->post('cc_expiry_month'),
										'expiry_year' => $this->EE->input->post('cc_expiry_year'),
										'security_code' => $this->EE->input->post('cc_cvv2')
									);
				}
				elseif ($this->EE->input->post('free') or $free_plan == TRUE) {
					// use dummy CC info for free subscription
					$credit_card = array(
										'number' => '0000000000000000',
										'name' => $member['screen_name'],
										'expiry_month' => date('m'), // this month
										'expiry_year' => (1 + date('Y')), // 1 year in future
										'security_code' => '000'
									);
				}
				else {
					$credit_card = array();
				}
				
				$customer = array(
								 'first_name' => $this->EE->input->post('first_name'),
								 'last_name' => $this->EE->input->post('last_name'),
								 'address' => $this->EE->input->post('address'),
								 'address_2' => $this->EE->input->post('address_2'),
								 'city' => $this->EE->input->post('city'),
								 'region' => ($this->EE->input->post('region_other') and $this->EE->input->post('region_other') != '') ? $this->EE->input->post('region_other') : $this->EE->input->post('region'),
								 'country' => $this->EE->input->post('country'),
								 'postal_code' => $this->EE->input->post('postal_code'),
								 'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email']
							);
							
				$gateway_id = ($this->EE->input->post('gateway') and $this->EE->input->post('gateway') != '' and $this->EE->input->post('gateway') != '0') ? $this->EE->input->post('gateway') : FALSE;
				
				$coupon = ($this->EE->input->post('coupon')) ? $this->EE->input->post('coupon') : FALSE;
							
				$response = $this->membrr->Subscribe($plan_id, $member_id, $credit_card, $customer, FALSE, FALSE, FALSE, '', '', $gateway_id, $renew_subscription, $coupon);
							
				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] == '2') {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					// redirect to URL
					if (!empty($plan['redirect_url'])) {
						header('Location: ' . $plan['redirect_url']);
						die();
					}
				}
			}	
		}
		
		if (validation_errors()) {
			// neat little hack to get an array of errors
			$form_errors = validation_errors('', '[|]');
			$form_errors = explode('[|]',$form_errors);
			
			foreach ($form_errors as $form_error) {
				$errors[] = $form_error;
			}
		}
    	
    	// get content of templates
    	$sub_return = $this->EE->TMPL->tagdata;
			
		// get customer information
		$address = $this->membrr->GetAddress($this->EE->session->userdata('member_id'));
		
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
	    $member = $member->row_array();
		
		$variables = array(
						'first_name' => ($this->EE->input->post('first_name')) ? $this->EE->input->post('first_name') : $address['first_name'], 
						'last_name' => ($this->EE->input->post('last_name')) ? $this->EE->input->post('last_name') : $address['last_name'],
						'address' => ($this->EE->input->post('address')) ? $this->EE->input->post('address') : $address['address'],
						'address_2' => ($this->EE->input->post('address_2')) ? $this->EE->input->post('address_2') : $address['address_2'],
						'city' => ($this->EE->input->post('city')) ? $this->EE->input->post('city') : $address['city'],
						'region' => ($this->EE->input->post('region')) ? $this->EE->input->post('region') : $address['region'],
						'region_other' => ($this->EE->input->post('region_other')) ? $this->EE->input->post('region_other') : $address['region_other'], 
						'country' => ($this->EE->input->post('country')) ? $this->EE->input->post('country') : $address['country'],
						'postal_code' => ($this->EE->input->post('postal_code')) ? $this->EE->input->post('postal_code') : $address['postal_code'],
						'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email']
					);
					
		// prep credit card fields
		
		// prep expiry month options
		$months = '';
		for ($i = 1; $i <= 12; $i++) {
	       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
	       $month_text = date('M',strtotime('2010-' . $month . '-01'));
	       
	       $months .= '<option value="' . $month . '">' . $month . ' - ' . $month_text . '</option>' . "\n";
	    }
	    
	    $variables['cc_expiry_month_options'] = $months;
	    
	    // prep same for years
	    
	    $years = '';
		$now = date('Y');
		$future = $now + 10;
		for ($i = $now; $i <= $future; $i++) {
			$years .= '<option value="' . $i . '">' . $i . '</option>';
		}
	    
	    $variables['cc_expiry_year_options'] = $years;
	    
	    // prep regions
	    $regions = $this->membrr->GetRegions();
		
		if ($this->EE->input->post('region')) {
			$customer_region = $this->EE->input->post('region');
		}
		elseif (isset($address['region'])) {
			$customer_region = $address['region'];
		}
		else {
			$customer_region = '';
		}
		
		$return = '';
		foreach ($regions as $region_code => $region) {
			$selected = ($customer_region == $region_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $region_code . '"' . $selected . '>' . $region . '</option>';
		}
		
		$region_options = $return;
		
		$variables['region_options'] = $region_options;
		reset($regions);
		$variables['region_raw_options'] = $regions;
							
		// field: customer country
		$countries = $this->membrr->GetCountries();
		
		if ($this->EE->input->post('country')) {
			$customer_country = $this->EE->input->post('country');
		}
		elseif (isset($address['country'])) {
			$customer_country = $address['country'];
		}
		else {
			$customer_country = '';
		}
		
		$return = '';
		foreach ($countries as $country_code => $country) {
			$selected = ($customer_country == $country_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $country_code . '"' . $selected . '>' . $country . '</option>';
		}

		$country_options = $return;
		
		$variables['country_options'] = $country_options;
		reset($countries);
		$variables['country_raw_options'] = $countries;
		
		// prep gateway options
		require(dirname(__FILE__) . '/opengateway.php');
		$this->server = new OpenGateway;
		$this->config = $this->membrr->GetConfig();
		$this->server->Authenticate($this->config['api_id'], $this->config['secret_key'], $this->config['api_url'] . '/api');
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();
		
		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;
		
		// hold our list of available options
		$gateway_raw_options = array();
		
		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_raw_options[$gateway['id']] = $gateway['gateway'];
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];
			$gateway_raw_options[$gateway['id']] = $gateway['gateway'];
		}
		
		$gateway_options = '';
		foreach ($gateway_raw_options as $gateway_id => $gateway_name) {
			$gateway_options .= '<option value="' . $gateway_id . '">' . $gateway_name . '</option>';
		}
		
		$variables['gateway_options'] = $gateway_options;
		reset($gateway_raw_options);
		$variables['gateway_raw_options'] = $gateway_raw_options;
	    
	    // prep form action
	    $variables['form_action'] = ($_SERVER["SERVER_PORT"] == "443") ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
	    $variables['form_method'] = 'POST';
	    
	    // prep errors
	    $variables['errors_array'] = $errors;
	    
	    $error_string = '';
	    foreach ($errors as $error) {
	    	$error_string .= '<div>' . $error . '</div>';
	    }
	    $variables['errors'] = $error_string;
	    
	    // parse the tag content with our new variables
	    $var_data = array();
	    $var_data[0] = $variables;
			
		$sub_return = $this->EE->TMPL->parse_variables($sub_return, $var_data);
    	
    	$this->return_data = $sub_return;
    	
    	return $sub_return;
    }
    
    /**
    * Displays a subscription order form with all of the basic elements
    *
    * @param int $plan_id A single plan ID
    * @param string $form_id The form ID
    * @param string $ul_class A class for the UL form elements
    * @returns string An order form, that will submit to itself and create new orders.  If processing an order,
    *				  and there are no errors, it will redirect to the URL specified in the control panel.
    */
    function quick_order_form () {    	
    	// user must be logged in
    	if ($this->EE->session->userdata('member_id') == '' or $this->EE->session->userdata('member_id') == '0') {
    		return '<h2>Membrr for ExpressionEngine Error:</h2>
    		<p>User must be logged in to see this order form.  This form tag should be protected like:</p>
    				<pre>' . htmlspecialchars('{if logged_in}
	{exp:membrr:order_form}
{/if}
{if logged_out}
	{exp:member:login_form return="site/index"}
	
	<p><label>Username</label><br />
	<input type="text" name="username" value="" maxlength="32" class="input" size="25" /></p>
	
	<p><label>Password</label><br />
	<input type="password" name="password" value="" maxlength="32" class="input" size="25" /></p>
	
	{if auto_login}
	<p><input class="checkbox" type="checkbox" name="auto_login" value="1" /> Auto-login on future visits</p>
	{/if}
	
	<p><input class="checkbox" type="checkbox" name="anon" value="1" checked="checked" /> Show my name in the online users list</p>
	
	<p><input type="submit" name="submit" value="Submit" /></p>
	
	<p><a href="{path="member/forgot_password"}">Forgot your password?</a></p>
	
	{/exp:member:login_form}
{/if}') . '</pre>';
    					
    		die();
    	}
    	
		// handle potential form submission
		if ($this->EE->input->post('membrr_order_form')) {
			// there's a submission
			$plan_id = $this->EE->input->post('plan_id');
			$plan = $this->membrr->GetPlan($plan_id);
			if ((float)$plan['price'] == 0) {
				$free_plan = TRUE;
			}
			else {
				$free_plan = FALSE;
			}
			
			// field validation pattern
			$fields = array(
							'plan_id|Plan ID' => 'empty|numeric|trim',
							'customer_first_name|First Name' => 'empty',
							'customer_last_name|Last Name' => 'empty',
							'customer_address|Address' => 'empty',
							'customer_address_2|Address Line 2' => '',
							'customer_city|City' => 'empty|trim',
							'customer_country|Country' => 'empty',
							'customer_postal_code|Postal Code' => 'empty',
							'customer_region|Region' => '',
							'customer_region_other|Region' => '',
							'customer_email|Email Address' => 'empty|email|trim'
						);
						
			if ($this->EE->input->post('free') != '1' and $free_plan == FALSE) {
				$fields2 = array(
							'cc_number|Credit Card Number' => 'empty|numeric|trim',
							'cc_name|Credit Card Name' => 'empty|trim',
							'cc_cvv2|Credit Card CVV2' => '',
							'cc_expiry_month|Expiry Month' => 'empty|numeric',
							'cc_expiry_year|Expiry Year' => 'empty|numeric'
						);
						
				$fields = array_merge($fields, $fields2);
			}
						
			$errors = array();
			$values = array();			
			foreach ($fields as $field_name => $validators) {
				list($field_name,$display_name) = explode('|',$field_name);
				
				$values[$field_name] = $this->EE->input->post($field_name, 'POST');
			
				$validators = explode('|',$validators);
				
				// trim where necessary
				if (in_array('trim',$validators)) {
					$values[$field_name] = trim($values[$field_name]);
				}
				
				// check empty
				if (in_array('empty',$validators) and $values[$field_name] == '') {
					$errors[] = $this->EE->lang->line('membrr_order_form_required_fields') . $display_name;
				}
				
				// check numeric
				if (in_array('numeric',$validators) and !is_numeric($values[$field_name])) {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_numeric') . $display_name;
				}
			}
			
			if (empty($errors)) {								
				$plan_id = $values['plan_id'];
				$member_id = $this->EE->session->userdata('member_id');
				
				// update address book
				$this->membrr->UpdateAddress($member_id,$values['customer_first_name'],$values['customer_last_name'],$values['customer_address'],$values['customer_address_2'],$values['customer_city'],$values['customer_region'],$values['customer_region_other'],$values['customer_country'],$values['customer_postal_code']);
								
				// prep arrays to send to Membrr_EE class	
				if ($this->EE->input->post('free') != '1' and $free_plan == FALSE) {			
					$credit_card = array(
										'number' => $values['cc_number'],
										'name' => $values['cc_name'],
										'expiry_month' => $values['cc_expiry_month'],
										'expiry_year' => $values['cc_expiry_year'],
										'security_code' => $values['cc_cvv2']
									);
				}
				else {
					// use dummy CC info for free subscription
					$credit_card = array(
										'number' => '0000000000000000',
										'name' => $values['customer_first_name'] . ' ' . $values['customer_last_name'],
										'expiry_month' => date('m'), // this month
										'expiry_year' => (1 + date('Y')), // 1 year in future
										'security_code' => '000' // free
									);
				}
				
				$customer = array(
								'first_name' => $values['customer_first_name'],
								'last_name' => $values['customer_last_name'],
								'address' => $values['customer_address'],
								'address_2' => $values['customer_address_2'],
								'city' => $values['customer_city'],
								'region' => (!empty($values['customer_region_other'])) ? $values['customer_region_other'] : $values['customer_region'],
								'country' => $values['customer_country'],
								'postal_code' => $values['customer_postal_code'],
								'email' => $values['customer_email']
							);
							
				$coupon = ($this->EE->input->post('coupon')) ? $this->EE->input->post('coupon') : FALSE;
							
				$response = $this->membrr->Subscribe($plan_id, $member_id, $credit_card, $customer, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, $coupon);
				
				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] == '2') {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					// redirect to URL
					$plan = $this->membrr->GetPlan($plan_id);
					
					if (!empty($plan['redirect_url'])) {
						header('Location: ' . $plan['redirect_url']);
						die();
					}
				}
			}
		}
    	
    	// build order form
    	$data = array(
    					'hidden_fields' => array('membrr_order_form' => 'TRUE'),
						'action'		=> ($_SERVER["SERVER_PORT"] == "443") ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri(),
						'id'			=> ($this->EE->TMPL->fetch_param('form_id')) ? $this->EE->TMPL->fetch_param('form_id') : '',
						'method' 		=> 'POST'
					);
						
        $return = $this->EE->functions->form_declaration($data); 
        
        // export errors
        if (!empty($errors)) {
        	$return .= '<div class="errors">';
        	foreach ($errors as $error) {
        		$return .= '<p>' . $error . '</p>';
        	}
        	$return .= '</div>';
        }
        
        $ul_class = ($this->EE->TMPL->fetch_param('ul_class')) ? $this->EE->TMPL->fetch_param('ul_class') : '';
        
        $return .= '<ul class="' . $ul_class . '">';
        
        // get config
        $config = $this->membrr->GetConfig();
        
        $return .= '<fieldset>
						<legend>' . $this->EE->lang->line('membrr_order_form_plan') . '</legend>';
    	
    	// field: plan select / display name
    	if (!$this->EE->TMPL->fetch_param('plan_id')) {
			$plans = $this->membrr->GetPlans(array('active' => '1'));
			
			if (!$plans) {
				return $this->error($this->EE->lang->line('membrr_error_no_plans'));
			}
			
			$return .= '<ul class="' . $ul_class . '">
						<li class="label">
							<label for="plan_id">' . $this->EE->lang->line('membrr_order_form_select_plan') . '</label>
						</li>
						<li class="field">
							<select id="plan_id" name="plan_id">';
			
			foreach ($plans as $key => $plan) {
				$return .= '<option value="' . $plan['id'] . '">' . $plan['name'] . ' (' . $config['currency_symbol'] . $plan['price'] . ' every ' . $plan['interval'] . ' days)</option>';
			}
			
			$return .= '</select></li>';
		}
		else {
			$plan = $this->membrr->GetPlan($this->EE->TMPL->fetch_param('plan_id'));
			
			$return .= '<ul class="' . $ul_class . '">
						<li class="plan">
							' . $plan['name'] . ' (' .$config['currency_symbol'] . $plan['price'] . ' every ' . $plan['interval'] . ' days)
							<input type="hidden" name="plan_id" value="' . $plan['id'] . '" />
						</li>';
		}
		
		$return .= '</ul></fieldset>';
		
		// only show CC if not Free!
		if (!$this->EE->TMPL->fetch_param('plan_id') or !isset($plan) or !is_array($plan) or money_format("%!i",$plan['price']) != '0.00') {
			$return .= '<fieldset>
							<legend>' . $this->EE->lang->line('membrr_order_form_credit_card') . '</legend>
							<ul class="' . $ul_class . '">';
			
			// field: credit card number
			$return .= '<li class="label">
							<label for="cc_number">' . $this->EE->lang->line('membrr_order_form_cc_number') . '</label>
						</li>
						<li class="field">
							<input type="text" id="cc_number" name="cc_number" maxlength="50" value="" />
						</li>';
					
			// field: credit card name	
			$cc_name = ($this->EE->input->post('cc_name')) ? $this->EE->input->post('cc_name') : '';
			$return .= '<li class="label">
							<label for="cc_name">' . $this->EE->lang->line('membrr_order_form_cc_name') . '</label>
						</li>
						<li class="field">
							<input type="text" id="cc_name" name="cc_name" maxlength="100" value="' . $cc_name . '" />
						</li>';
															
			// field: credit card expiry date	
			$return .= '<li class="label">
							<label for="cc_expiry_month">' . $this->EE->lang->line('membrr_order_form_cc_expiry') . '</label>
						</li>
						<li class="field">
							<select id="cc_expiry_month" name="cc_expiry_month">';
							
			// automatically figure out months
			for ($i = 1; $i <= 12; $i++) {
		       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
		       $month_text = date('M',strtotime('2010-' . $month . '-01'));
		       
		       $return .= '<option value="' . $month . '">' . $month . ' - ' . $month_text . '</option>';
		    }
		    
		    $return .= '</select>&nbsp;&nbsp;';
						
			$return .= '<select id="cc_expiry_year" name="cc_expiry_year">';
			
			// automatically figure out years
			$now = date('Y');
			$future = $now + 15;
			for ($i = $now; $i <= $future; $i++) {
				$return .= '<option value="' . $i . '">' . $i . '</option>';
			}
			
			$return .= '</select></li>';
						
			// field: credit card security code	
			$return .= '<li class="label">
							<label for="cc_cvv2">' . $this->EE->lang->line('membrr_order_form_cc_cvv2') . '</label>
						</li>
						<li class="field">
							<input type="text" id="cc_cvv2" name="cc_cvv2" maxlength="100" value="" />
						</li>';
						
			$return .= '</ul></fieldset>';
		}
		else {
			// we won't require CC information to be valid if this is here
			$return .= '<input type="hidden" name="free" value="1" />';
		}
		
		$return .= '<fieldset>
						<legend>' . $this->EE->lang->line('membrr_order_form_customer_info') . '</legend>
						<ul class="' . $ul_class . '">';
						
		// customer information
		$address = $this->membrr->GetAddress($this->EE->session->userdata('member_id'));
		
		// field: customer first name
		if ($this->EE->input->post('customer_first_name')) {
			$customer_first_name = $this->EE->input->post('customer_first_name');
		}
		elseif (isset($address['first_name'])) {
			$customer_first_name = $address['first_name'];
		}
		else {
			$customer_first_name = '';
		}
		$return .= '<li class="label">
						<label for="customer_first_name">' . $this->EE->lang->line('membrr_order_form_customer_first_name') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_first_name" name="customer_first_name" maxlength="100" value="' . $customer_first_name . '" />
					</li>';
					
		// field: customer last name
		if ($this->EE->input->post('customer_last_name')) {
			$customer_last_name = $this->EE->input->post('customer_last_name');
		}
		elseif (isset($address['last_name'])) {
			$customer_last_name = $address['last_name'];
		}
		else {
			$customer_last_name = '';
		}
		$return .= '<li class="label">
						<label for="customer_last_name">' . $this->EE->lang->line('membrr_order_form_customer_last_name') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_last_name" name="customer_last_name" maxlength="100" value="' . $customer_last_name . '" />
					</li>';
					
		// field: customer address
		if ($this->EE->input->post('customer_address')) {
			$customer_address = $this->EE->input->post('customer_address');
		}
		elseif (isset($address['address'])) {
			$customer_address = $address['address'];
		}
		else {
			$customer_address = '';
		}
		$return .= '<li class="label">
						<label for="customer_address">' . $this->EE->lang->line('membrr_order_form_customer_address') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_address" name="customer_address" maxlength="100" value="' . $customer_address . '" />
					</li>';
					
		// field: customer address, line 2
		if ($this->EE->input->post('customer_address_2')) {
			$customer_address_2 = $this->EE->input->post('customer_address_2');
		}
		elseif (isset($address['address_2'])) {
			$customer_address_2 = $address['address_2'];
		}
		else {
			$customer_address_2 = '';
		}
		$return .= '<li class="label">
						<label for="customer_address_2">' . $this->EE->lang->line('membrr_order_form_customer_address_2') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_address_2" name="customer_address_2" maxlength="100" value="' . $customer_address_2 . '" />
					</li>';
					
		// field: customer city
		if ($this->EE->input->post('customer_city')) {
			$customer_city = $this->EE->input->post('customer_city');
		}
		elseif (isset($address['city'])) {
			$customer_city = $address['city'];
		}
		else {
			$customer_city = '';
		}
		$return .= '<li class="label">
						<label for="customer_city">' . $this->EE->lang->line('membrr_order_form_customer_city') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_city" name="customer_city" maxlength="100" value="' . $customer_city . '" />
					</li>';
					
		// field: customer region
		$return .= '<li class="label">
						<label for="customer_region">' . $this->EE->lang->line('membrr_order_form_customer_region') . '</label>
					</li>
					<li class="field">';
				
		// only show the select if they haven't already completed the form
		$return .= '<select id="customer_region" name="customer_region">
							<option value=""></option>';
							
		$regions = $this->membrr->GetRegions();
		
		if ($this->EE->input->post('customer_region')) {
			$customer_region = $this->EE->input->post('customer_region');
		}
		elseif (isset($address['region'])) {
			$customer_region = $address['region'];
		}
		else {
			$customer_region = '';
		}
		
		foreach ($regions as $region_code => $region) {
			$selected = ($customer_region == $region_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $region_code . '"' . $selected . '>' . $region . '</option>';
		}
		
		if ($this->EE->input->post('customer_region_other')) {
			$customer_region_other = $this->EE->input->post('customer_region_other');
		}
		elseif (isset($address['region_other'])) {
			$customer_region_other = $address['region_other'];
		}
		else {
			$customer_region_other = '';
		}
		
		$selected = ($customer_region == '') ? ' selected="selected"' : '';
		
		$return .= '<option value=""' . $selected .'>Other (specify to the right)</option>
						</select>
						&nbsp;&nbsp;';
				
		$return .= '<input type="text" id="customer_region_other" name="customer_region_other" value="' . $customer_region_other . '" />
				</li>';
					
		// field: customer country
		$countries = $this->membrr->GetCountries();
		
		if ($this->EE->input->post('customer_country')) {
			$customer_country = $this->EE->input->post('customer_country');
		}
		elseif (isset($address['country'])) {
			$customer_country = $address['country'];
		}
		else {
			$customer_country = '';
		}
		
		$return .= '<li class="label">
						<label for="customer_country">' . $this->EE->lang->line('membrr_order_form_customer_country') . '</label>
					</li>
					<li class="field">
						<select name="customer_country" id="customer_country">';
		foreach ($countries as $country_code => $country) {
			$selected = (!empty($customer_country) and $customer_country == $country_code)  ? ' selected="selected" ' : '';
			
			$return .= '<option value="' . $country_code . '"' . $selected . '>' . $country . '</option>';
		}
		
		$return .= '</select></li>';
						
		// field: customer postal code
					
		if ($this->EE->input->post('customer_postal_code')) {
			$customer_postal_code = $this->EE->input->post('customer_postal_code');
		}
		elseif (isset($address['postal_code'])) {
			$customer_postal_code = $address['postal_code'];
		}
		else {
			$customer_postal_code = '';
		}
		$return .= '<li class="label">
						<label for="customer_postal_code">' . $this->EE->lang->line('membrr_order_form_customer_postal_code') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_postal_code" name="customer_postal_code" maxlength="100" value="' . $customer_postal_code . '" />
					</li>';
					
		// field: customer email address
					
		$customer_email = ($this->EE->input->post('customer_email')) ? $this->EE->input->post('customer_email') : $member_id = $this->EE->session->userdata('email');
		$return .= '<li class="label">
						<label for="customer_email">' . $this->EE->lang->line('membrr_order_form_customer_email') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_email" name="customer_email" maxlength="100" value="' . $customer_email . '" />
					</li>';
					
		$return .= '</ul></fieldset>';
		
		$button_value = ($this->EE->TMPL->fetch_param('button')) ? $this->EE->TMPL->fetch_param('button') : 'Subscribe Now';
		
		$return .=	'<fieldset class="subscribe">
						<legend>Subscribe</legend>
						<input type="submit" name="process_subscription" value="' . $button_value . '" />
					</fieldset>
					</form>';
		
		return $return;
    }
    
    /*
    * POST Notification Handler
    *
    * Handles POST notifications from the Membrr/OpenGateway billing server.
    */
    
    function post_notify () {
    	// get Membrr config
    	$config = $this->membrr->GetConfig();
    	
    	// connect to API
    	require_once(dirname(__FILE__) . '/opengateway.php');
		$connect_url = $config['api_url'] . '/api';
		$server = new OpenGateway;
		$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
    	
    	// first, we'll check for external payment API redirects
    	if ($this->EE->input->get('member') and is_numeric($this->EE->input->get('member')) and is_numeric($this->EE->input->get('plan_id'))) {
    		if ($plan = $this->membrr->GetPlan($this->EE->input->get('plan_id')))	{
	    		// get customer ID
	    		$server->SetMethod('GetCustomers');
	    		$server->Param('internal_id',$this->EE->input->get('member'));
	    		$response = $server->Process();
	    		$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
	    		
	    		if (empty($customer)) {
	    			die('Invalid customer record.');
	    		}
	    		
	    		$server->SetMethod('GetRecurrings');
	    		$server->Param('customer_id',$customer['id']);
	    		$server->Param('plan_id',$plan['api_id']);
	    		$response = $server->Process();
	    		
	    		if (isset($response['recurrings']['recurring'][0])) {
					$recurrings = $response['recurrings']['recurring'];
				}
				elseif (isset($response['recurrings'])) {
					$recurrings = array();
					$recurrings[] = $response['recurrings']['recurring'];
				}
				else {
					$recurrings = array();
				}
				
				// is there a new recurring charge for this client?
				foreach ($recurrings as $recurring) {
					if (!$this->membrr->GetSubscription($recurring['id'])) {
						// we have a new charge!
						$end_date = date('Y-m-d H:i:s',strtotime($recurring['end_date']));
						$next_charge_date = date('Y-m-d H:i:s',strtotime($recurring['next_charge_date']));
						
						// get the first charge
						$server->SetMethod('GetCharges');
						$server->Param('recurring_id',$recurring['id']);
						$charge = $server->Process();
						
						// charge should be an array, but there shouldn't be multiple charges!
						if (!empty($charge) and is_array($charge) and !isset($charge['charges']['charge'][0])) {
							$charge = $charge['charges']['charge'];
							$payment = $charge['amount'];
							$this->membrr->RecordPayment($recurring['id'], $charge['id'], $payment);
						}
						
						$this->membrr->RecordSubscription($recurring['id'], $this->EE->input->get('member'), $plan['id'], $next_charge_date, $end_date, $recurring['amount']);
						
						// redirect
						header('Location: ' . $plan['redirect_url']);
						die();
					}
				}
			}
    	}
    	
    	// is the secret key OK?  ie. is this a legitimate call?
		if ($this->EE->input->post('secret_key') != $config['secret_key']) {
			die('Invalid secret key.');
		}
		
		if (!$this->EE->input->post('customer_id') or !$this->EE->input->post('recurring_id')) {
			die('Insufficient data.');
		}
		
		// get customer data from server
		$server->SetMethod('GetCustomer');
		$server->Param('customer_id',$this->EE->input->post('customer_id'));
		$response = $server->Process();
		
		if (!is_array($response) or !isset($response['customer'])) {
			die('Error retrieving customer data.');
		}
		else {
			$customer = $response['customer'];
		}
		
		// get subscription data locally
		$subscription = $this->membrr->GetSubscription($this->EE->input->post('recurring_id'));
	
		if (!$subscription) {
			die('Error retrieving subscription data locally.');
		}
		
		if ($this->EE->input->post('action') == 'recurring_charge') {		
			if (!$this->EE->input->post('charge_id')) {
				die('No charge ID.');
			}
			
			if (is_array($this->membrr->GetPayments(0,1,array('id' => $this->EE->input->post('charge_id'))))) {
		 		die('Charge already recorded.');
		 	}
		 	
			$server->SetMethod('GetCharge');
			$server->Param('charge_id',$this->EE->input->post('charge_id'));
			$charge = $server->Process();
			
			$charge = $charge['charge'];
		 	
		 	// record charge
			$this->membrr->RecordPayment($this->EE->input->post('recurring_id'), $this->EE->input->post('charge_id'), $charge['amount']);
								  			      
			// update next charge date
			$plan = $this->membrr->GetPlan($subscription['plan_id']);
			
			$next_charge_date = strtotime('now + ' . $plan['interval'] . ' days');
			if (!empty($subscription['end_date']) and (strtotime($subscription['end_date']) < $next_charge_date)) {	
				// there won't be a next charge
				// subscription will expire beforehand
				$next_charge_date = '0000-00-00';
			}
			else {
				$next_charge_date = date('Y-m-d',$next_charge_date);
			}
			
			$this->membrr->SetNextCharge($this->EE->input->post('recurring_id'),$next_charge_date);					        
		}
		elseif ($this->EE->input->post('action') == 'recurring_cancel') {
			if ($subscription['active'] == '0') {
		 		die('Already cancelled.');
		 	}
		 	
		 	$this->membrr->CancelSubscription($subscription['id'],FALSE);
		}
		elseif ($this->EE->input->post('action') == 'recurring_expire') {
			if ($subscription['active'] == '0') {
		 		die('Already expired.');
		 	}
		 	
		 	$this->membrr->CancelSubscription($subscription['id'],FALSE,TRUE);
		}
    }
}
