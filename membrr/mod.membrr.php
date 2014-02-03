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

/**
* Membrr Module
*
* Enables frontend template tags:
*   - {exp:membrr:update_form redirect_url="/test"}
*	- {exp:membrr:order_form redirect_url="/test"}
*	- {exp:membrr:billing_address}{/exp:membrr:billing_address}
*	- {exp:membrr:quick_order_form} e.g., {exp:membrr:quick_order_form button="Subscribe Now!" plan_id="X"}
*	- {exp:membrr:subscriptions}{/exp:membrr:subscriptions} e.g. {exp:membrr:subscriptions id="X" date_format="Y-m-d"}
*	- {exp:membrr:subscribed plan="X"}{/exp:membrr:subscribed}
*	- {exp:membrr:not_subscribed plan="X"}{/exp:membrr:not_subscribed}
*	- {exp:membrr:payments}{/exp:membrr:payments} e.g. {exp:membrr:payments id="X" subscription_id="X" offset="X" limit="X" date_format="Y-m-d"}
*	- {exp:membrr:plans}{/exp:membrr:plans} e.g., {exp:membrr:plans id="X" for_sale="1"}{/exp:membrr:plans}
*	- {exp:membrr:cancel id="X"}{/exp:membrr:cancel} (returns {if cancelled} and {if failed} to tagdata)
*	- {exp:membrr:has_subscription_for_channel channel="ad_posts"}<!-- HTML -->{/exp...channel} or {exp:membrr:has_subscription_for_channel channel="22"}<!-- HTML -->{/exp...channel}
*	- {exp:membrr:no_subscription_for_channel channel="ad_posts"}<!-- HTML -->{/exp...channel} or {exp:membrr:no_subscription_for_channel channel="22"}<!-- HTML -->{/exp...channel}
*	- {exp:membrr:receipt}{/exp:membrr:receipt}
*
* @author Electric Function, Inc.
* @package OpenGateway
*/

class Membrr {
	var $return_data	= '';
	var $membrr; // holds the Membrr_EE class
	var $EE; // holds the EE superobject
    private $cache = array();

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

    	if ($this->membrr->GetSubscriptionForChannel($channel['id'], $this->EE->session->userdata('member_id'),$channel['plans'],$channel['posts'])) {
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

    	if (!$this->membrr->GetSubscriptionForChannel($channel['id'], $this->EE->session->userdata['member_id'],$channel['plans'],$channel['posts'])) {
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
    * @param int|string plan A single plan_id or multiple plan_id's separated by pipes (optional, will check for any subscription if empty)
    *
    * @return string Tag data, if active plan of ID exists
    */
    function subscribed () {
    	$id = $this->EE->TMPL->fetch_param('plan');

    	$filters = array();

    	// Was a 'member_id' param passed in?
    	$member_id = $this->EE->TMPL->fetch_param('member_id');

    	// If no param existed, grab the current user's id.
    	if (empty($member_id))
    	{
    		$member_id = $this->EE->session->userdata('member_id');
    	}

		// Otherwise, we get out of here because
		// we don't have enough info to do.
		if (empty($member_id)) {
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}

		$filters['active'] = '1';

		$return = '';
		if (empty($id)) {
			$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);

			if (is_array($subscriptions) and !empty($subscriptions)) {
				$return = $this->EE->TMPL->tagdata;
			}
		}
		elseif (strpos($id, '|') !== FALSE) {
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

    	$filters = array();

    	// Was a 'member_id' param passed in?
    	$member_id = $this->EE->TMPL->fetch_param('member_id');

    	// If no param existed, grab the current user's id.
    	if (empty($member_id))
    	{
    		$member_id = $this->EE->session->userdata('member_id');
    	}

		if (empty($member_id)) {
			return 'User is not logged in.';
		}
		else {
			$filters['member_id'] = $member_id;
		}

		$filters['active'] = '1';

		$return = $this->EE->TMPL->tagdata;
		if (empty($id)) {
			$subscriptions = $this->membrr->GetSubscriptions(0,1,$filters);

			if (is_array($subscriptions) and !empty($subscriptions)) {
				$return = '';
			}
		}
		elseif (strpos($id, '|') !== FALSE) {
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
								'api_plan_id' => $plan['api_id'],
								'name' => $plan['name'],
								'description' => $plan['description'],
								'free_trial' => $plan['free_trial'],
								'interval' => $plan['interval'],
								'occurrences' => $plan['occurrences'],
								'price' => money_format("%!^i",$plan['price']),
								'initial_charge' => money_format("%!^i",$plan['initial_charge']),
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
    * Displays a receipt for the latest payment
    *
    * For each payment, replaces tags:
    *	- {charge_id}
    *	- {subscription_id} (if applicable)
    *	- {member_id} (if applicable)
    *	- {amount}
    *	- {date}
    *	- {billing_first_name}
    *	- {billing_last_name}
    *	- {billing_address}
    *	- {billing_address_2}
    * 	- {billing_city}
    *	- {billing_region}
    *	- {billing_country}
    *	- {billing_postal_code}
    *	- {billing_company}
    *	- {billing_phone}
    *
    * ... and, if it's a subscription...
    *
    *	- {next_charge_date}
    *	- {plan_name}
    *
    * Conditionals
    *	- All above tags
    *
    * e.g.  {exp:membrr:receipt date_format="M d, Y"}
    *
    * @param string $date_format The PHP format of dates
    *
    * @return string The latest payment, if available, for that user
	*/
	function receipt () {
		$charge_id = $this->EE->input->cookie('membrr_charge_id');

		if (empty($charge_id)) {
			return '<!-- no receipt available -->';
		}

		$filters = array(
						'id' => $charge_id
					);

		$payments = $this->membrr->GetPayments(0, 1, $filters);

		if (empty($payments)) {
			// no payments matching parameters
			return '<!-- invalid receipt ID -->';
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
						'member_id' => $payment['member_id'],
						'billing_first_name' => $payment['first_name'],
						'billing_last_name' => $payment['last_name'],
						'billing_address' => $payment['address'],
						'billing_address_2' => $payment['address_2'],
						'billing_city' => $payment['city'],
						'billing_region' => (!empty($payment['region_other'])) ? $payment['region_other'] : $payment['region'],
						'billing_country' => $payment['country'],
						'billing_postal_code' => $payment['postal_code'],
						'billing_company' => $payment['company'],
						'billing_phone' => $payment['phone']
					);

			if (!empty($payment['recurring_id'])) {
				$subscription = $this->membrr->GetSubscription($payment['recurring_id']);

				if ($this->EE->TMPL->fetch_param('date_format')) {
					$subscription['next_charge_date'] = (!empty($subscription['next_charge_date'])) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['next_charge_date'])) : FALSE;
				}

				$variables[0] = array_merge($variables[0], array(
															'plan_name' => $subscription['plan_name'],
															'next_charge_date' => $subscription['next_charge_date']
													));
			}

			// swap in the variables
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);

			// add to return HTML
			$return .= $sub_return;

			unset($sub_return);
		}

		$this->return_data = $return;

		return $this->return_data;
	}

	/**
	* Billing Address
	*
	* Returns the latest billing address for the logged-in user
	*
	* @return billing address fields
	*/
	function billing_address () {
		$tagdata = $this->EE->TMPL->tagdata;

		$address = $this->membrr->GetAddress($this->EE->session->userdata('member_id'));

		$variables = array();
		$variables[0] = $address;

		$tagdata = $this->EE->TMPL->parse_variables($tagdata, $variables);

		$this->return_data = $tagdata;

		return $this->return_data;
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
		$member_id = ($this->EE->TMPL->fetch_param('member_id')) ? $this->EE->TMPL->fetch_param('member_id') : $this->EE->session->userdata('member_id');

		$filters = array();

		if (empty($member_id)) {
			return 'User is not logged in and you have not passed a "member_id" parameter.';
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
						'entry_id' => $payment['entry_id'],
						'refunded' => (empty($payment['refunded'])) ? FALSE : TRUE
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
		$member_id = ($this->EE->TMPL->fetch_param('member_id')) ? $this->EE->TMPL->fetch_param('member_id') : $this->EE->session->userdata('member_id');

		$filters = array();

		if (empty($member_id)) {
			return 'User is not logged in and you have not passed a "member_id" parameter.';
		}
		else {
			$filters['member_id'] = $member_id;
		}

		if ($this->EE->TMPL->fetch_param('inactive') == '1') {
			$filters['active'] = '0';
		}

		if ($this->EE->TMPL->fetch_param('status') == 'active') {
			$filters['active'] = '1';
		}
		elseif ($this->EE->TMPL->fetch_param('status') == 'inactive') {
			$filters['active'] = '0';
		}

		if ($this->EE->TMPL->fetch_param('id')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}

		$limit = ($this->EE->TMPL->fetch_param('limit')) ? $this->EE->TMPL->fetch_param('limit') : 100;

        $cache_name = md5(__CLASS__ . __METHOD__ . serialize($filters) . $limit);

        // Has this call been cached?
        if (!isset($this->cache[$cache_name]))
        {
    		$subscriptions = $this->membrr->GetSubscriptions(0,$limit,$filters);

    		if (empty($subscriptions)) {
    			// no subscriptions matching parameters
    			return $this->EE->TMPL->no_results();
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
    							'card_last_four' => $subscription['card_last_four'],
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

            $this->cache[$cache_name] = $return;
        }
        else
        {
            $return = $this->cache[$cache_name];
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
	*	company
	*	phone
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
			// validate email if it is there, or if we're creating an account
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
												 $this->EE->input->post('postal_code'),
												 $this->EE->input->post('company'),
												 $this->EE->input->post('phone'),
												 $this->EE->input->post('email')
												);

					// update email
					if ($this->EE->input->post('email')) {
						$this->EE->db->update('exp_members', array('email' => $this->EE->input->post('email')), array('member_id' => $member['member_id']));
					}
				}

				// prep arrays to send to Membrr_EE class
				$credit_card = array(
									'number' => $this->EE->input->post('cc_number'),
									'name' => $this->EE->input->post('cc_name'),
									'expiry_month' => $this->EE->input->post('cc_expiry_month'),
									'expiry_year' => $this->EE->input->post('cc_expiry_year'),
									'security_code' => $this->EE->input->post('cc_cvv2')
								);

				$plan_id = ($this->EE->input->post('plan_id')) ? $this->EE->input->post('plan_id') : FALSE;

				$response = $this->membrr->UpdateCC($subscription['id'], $credit_card, $plan_id);

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
				$errors[] = strip_tags($form_error);
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
						'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
						'company' => ($this->EE->input->post('company')) ? $this->EE->input->post('company') : $address['company'],
						'phone' => ($this->EE->input->post('phone')) ? $this->EE->input->post('phone') : $address['phone']
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
	    $variables['form_action'] = ($this->is_ssl()) ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
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
		
		if (!defined('XID_SECURE_HASH')) {
			define('XID_SECURE_HASH',$this->EE->security->generate_xid());
		}
		
		// EE 2.7 has made XID's mandatory in all requests... so let's hack it in there
		$sub_return = str_replace('</form>', '<input type="hidden" name="XID" value="' . XID_SECURE_HASH . '" /></form>', $sub_return);

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
	*	company
	*	phone
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

		// load configuration
		$this->config = $this->membrr->GetConfig();

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

			// validate email if it is there, or if we're creating an account
			if ($this->EE->input->post('email') or $this->EE->input->post('password')) {
				$this->EE->form_validation->set_rules('email','lang:membrr_order_form_customer_email','trim|required|valid_email');
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

			// check CAPTCHA
			if ($this->config['use_captcha'] == TRUE) {
				// only show to logged out users, or if we show CAPTCHA to everyone
				if ($this->EE->config->item('captcha_require_members') == 'y' OR $this->EE->session->userdata['member_id'] == 0) {
					$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

					if ($query->row('count') == 0)
					{
						$errors[] = 'The CAPTCHA text you entered was incorrect.  Please try again.';
					}
					else {
						// they got it right...
						// continue...
					}

					// clear old CAPTCHA
					$this->EE->db->query("DELETE FROM exp_captcha WHERE (ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
				}
			}

			if ($this->EE->input->post('password')) {
				$password = preg_replace('#\s#i','',$this->EE->input->post('password'));

				// does password meet requirements?
				if (strlen($password) < $this->EE->config->item('pw_min_len')) {
					$errors[] = 'Your password must be at least '. $pml = $this->EE->config->item('pw_min_len') .' characters in length.';
				}

				// check if passwords match, if there are two passwords
				if (isset($_POST['password2']) and $this->EE->input->post('password2') != $password) {
					$errors[] = 'Your passwords do not match.';
				}

				// set screen_name, username, and email from email
				$email = $this->EE->input->post('email');
				$username = $this->EE->input->post('email');

				// set random unique_id
				$unique_id = md5(time() + rand(10,1000));

				// override screen_name?
				if ($this->EE->input->post('username')) {
					$username = $this->EE->input->post('username');
				}

				// Set the screen name here so that if they provided a username
				// we can use that, otherwise, it's equal to the email address.
				$screen_name = $username;

				// override username?
				if ($this->EE->input->post('screen_name')) {
					$screen_name = $this->EE->input->post('screen_name');
				}

				// check email/username uniqueness
				$result = $this->EE->db->where('email',$email)
									   ->get('exp_members');

				if ($result->num_rows() > 0) {
					$errors[] = 'Your email is already registered to an account.  Please login to your account if you have already registered.';
				}

				if ($username != $email) {
					$result = $this->EE->db->where('username',$username)
										   ->get('exp_members');

					if ($result->num_rows() > 0) {
						$errors[] = 'Your username is already being used and must be unique.  Please select another.';
					}
				}

				if (strlen($username) <= 4 && $username !== $email)
				{
					$errors[] = 'Your username must be at longer than 4 characters. Please select another.';
				}

				if ($screen_name != $email && $screen_name != $username) {
					$result = $this->EE->db->where('screen_name',$username)
										   ->get('exp_members');

					if ($result->num_rows() > 0) {
						$errors[] = 'Your screen name is already being used and must be unique.  Please select another.';
					}
				}

				if (empty($errors)) {
					// attempt to create an account, put an error if $errors[] if failed
					$member_data = array(
										'group_id' => $this->EE->config->item('default_member_group'),
										'language' => $this->EE->config->item('language'),
										'timezone' => $this->EE->config->item('server_timezone'),
										'time_format' => $this->EE->config->item('time_format'),
										//'daylight_savings' => $this->EE->config->item('daylight_savings'),
										'ip_address' => $this->EE->input->ip_address(),
										'join_date' => $this->EE->localize->now,
										'email' => $email,
										'unique_id' => $unique_id,
										'username' => $username,
										'screen_name' => $screen_name,
										'password' => sha1($password)
									);

                    // EE versions prior to 2.6 required the daylight_savings link
                    if (version_compare(APP_VER, '2.6.0', '<'))
                    {
                        $member_data['daylight_savings'] = $this->EE->config->item('time_format');
                    }

					// Handle other, less used, fields
					if ($this->EE->input->post('url')) $member_data['url'] = $this->EE->input->post('url');
					if ($this->EE->input->post('location')) $member_data['location'] = $this->EE->input->post('location');
					if ($this->EE->input->post('occupation')) $member_data['occupation'] = $this->EE->input->post('occupation');
					if ($this->EE->input->post('interests')) $member_data['url'] = $this->EE->input->post('interests');
					if ($this->EE->input->post('bday_d')) $member_data['bday_d'] = $this->EE->input->post('bday_d');
					if ($this->EE->input->post('bday_m')) $member_data['bday_m'] = $this->EE->input->post('bday_m');
					if ($this->EE->input->post('bday_y')) $member_data['bday_y'] = $this->EE->input->post('bday_y');
					if ($this->EE->input->post('bio')) $member_data['bio'] = $this->EE->input->post('bio');

					$this->EE->load->model('member_model');
					$member_id = $this->EE->member_model->create_member($member_data);

					// handle custom fields passed in POST
					$result = $this->EE->db->get('exp_member_fields');
					$fields = array();
					if ($result->num_rows() > 0) {
						foreach ($result->result_array() as $field) {
							$fields[$field['m_field_name']] = 'm_field_id_' . $field['m_field_id'];
						}

						$update_fields = array();

						foreach ($fields as $name => $column) {
							$update_fields[$column] = ($this->EE->input->post($name)) ? $this->EE->input->post($name) : '';
						}

						$this->EE->member_model->update_member_data($member_id, $update_fields);
					}
					// end custom fields

					// call member_member_register hook
					$edata = $this->EE->extensions->call('member_member_register', $member_data, $member_id);
					if ($this->EE->extensions->end_script === TRUE) return;

					if (empty($member_id)) {
						$errors[] = 'Member account could not be created.';
					}

					$member_created = TRUE;
				}
			}
			else {
				if (!$this->EE->session->userdata('member_id')) {
					$errors[] = 'Please complete all required fields.';
					$member_id = FALSE;
					$member_created = FALSE;
				}
				else {
					$member_id = $this->EE->session->userdata('member_id');
					$member_created = FALSE;
				}
			}

			$plan = (is_numeric($this->EE->input->post('plan_id'))) ? $this->membrr->GetPlan($this->EE->input->post('plan_id')) : FALSE;

			if (empty($plan)) {
				$errors[] = 'Invalid plan ID selected.';
			}

			if ($this->EE->form_validation->run() != FALSE and isset($member_id) and !empty($member_id) and !empty($plan) and empty($errors)) {
				$plan_id = $plan['id'];

				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($member_id);
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
												 $this->EE->input->post('postal_code'),
												 $this->EE->input->post('company'),
												 $this->EE->input->post('phone')
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
								 'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
								 'company' => $this->EE->input->post('company'),
								 'phone' => $this->EE->input->post('phone')
							);

				$gateway_id = ($this->EE->input->post('gateway') and $this->EE->input->post('gateway') != '' and $this->EE->input->post('gateway') != '0') ? $this->EE->input->post('gateway') : FALSE;

				$coupon = ($this->EE->input->post('coupon')) ? $this->EE->input->post('coupon') : FALSE;

				// we log the user in before passing them off, so that PayPal Standard stays logged in
				if ($member_created == TRUE) {
					// let's log the user in
					$this->EE->session->userdata['ip_address'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
					$this->EE->session->userdata['user_agent'] = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

					$expire = (60*60*24); // 1 day expire

					$this->EE->functions->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);

					// we have to check for these variables because EE2.2 removed them
					if (isset($this->EE->session->c_uniqueid)) {
				        $this->EE->functions->set_cookie($this->EE->session->c_uniqueid , $unique_id, $expire);
				    }
				    if (isset($this->EE->session->c_password)) {
				    	$this->EE->functions->set_cookie($this->EE->session->c_password , sha1($password),  $expire);
				    }

					$this->EE->session->create_new_session($member_id);
					$this->EE->session->userdata['username']  = $username;
				}

				// they may be getting passed to external checkout... so let's save the redirect_url
				$this->EE->functions->set_cookie('membrr_redirect_url', $this->EE->TMPL->fetch_param('redirect_url'), 86400);

				// subscribe!
				$response = $this->membrr->Subscribe($plan_id, $member_id, $credit_card, $customer, FALSE, FALSE, FALSE, '', '', $gateway_id, $renew_subscription, $coupon);

				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';

					// delete the member we just created if we created one
					if ($member_created == TRUE) {
						$this->EE->db->delete('exp_members', array('member_id' => $member_id));
					}
				}
				elseif ($response['response_code'] == '2') {
					$errors[] = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';

					// delete the member we just created if we created one
					if ($member_created == TRUE) {
						$this->EE->db->delete('exp_members', array('member_id' => $member_id));

						// log them out
						if (isset($this->EE->session) and method_exists($this->EE->session, 'destroy')) {
							$this->EE->session->destroy();
						}
					}
				}
				else {
					// success!

					// redirect to URL
					if ($this->EE->TMPL->fetch_param('redirect_url')) {
						$redirect_url = $this->EE->TMPL->fetch_param('redirect_url');
					}
					elseif (!empty($plan['redirect_url'])) {
						$redirect_url = $plan['redirect_url'];
					}

					header('Location: ' . $redirect_url);
					die();
				}
			}
			else {
				// delete the member we just created if we created one
				if (isset($member_created) and $member_created == TRUE) {
					$this->EE->db->delete('exp_members', array('member_id' => $member_id));

					// log them out
					if (isset($this->EE->session) and method_exists($this->EE->session, 'destroy')) {
						$this->EE->session->destroy();
					}
				}
			}
		}

		$variables = array();

		// get content of templates
    	$sub_return = $this->EE->TMPL->tagdata;

		// have we been passed $variables already?
		if ($this->EE->session->flashdata('membrr_variables')) {
			$temp_data = $this->membrr->GetTempData($this->EE->session->flashdata('membrr_variables'));

			$temp_data = @unserialize($temp_data);

			if (is_array($temp_data)) {
				$variables = $temp_data;
			}
		}

		if (empty($variables)) {
			if (validation_errors()) {
				// neat little hack to get an array of errors
				$form_errors = validation_errors('', '[|]');
				$form_errors = explode('[|]',$form_errors);

				foreach ($form_errors as $form_error) {
					$errors[] = strip_tags($form_error);
				}
			}

			// prep errors
		    $variables['errors_array'] = $errors;

		    $error_string = '';
		    foreach ($errors as $error) {
		    	$error_string .= '<div>' . $error . '</div>';
		    }
		    $variables['errors'] = $error_string;

			if ($this->EE->session->userdata('member_id')) {
				// get customer information
				$address = $this->membrr->GetAddress($this->EE->session->userdata('member_id'));

				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
			    $member = $member->row_array();
			}
			else {
				$address = array(
								'first_name' => '',
								'last_name' => '',
								'address' => '',
								'address_2' => '',
								'city' => '',
								'region' => '',
								'region_other' => '',
								'country' => '',
								'postal_code' => '',
								'email' => '',
								'company' => '',
								'phone' => ''
							);

				$member = array(
								'email' => ''
							);
			}

			$variables = array_merge($variables, array(
							'first_name' => ($this->EE->input->post('first_name')) ? $this->EE->input->post('first_name') : $address['first_name'],
							'last_name' => ($this->EE->input->post('last_name')) ? $this->EE->input->post('last_name') : $address['last_name'],
							'address' => ($this->EE->input->post('address')) ? $this->EE->input->post('address') : $address['address'],
							'address_2' => ($this->EE->input->post('address_2')) ? $this->EE->input->post('address_2') : $address['address_2'],
							'city' => ($this->EE->input->post('city')) ? $this->EE->input->post('city') : $address['city'],
							'region' => ($this->EE->input->post('region')) ? $this->EE->input->post('region') : $address['region'],
							'region_other' => ($this->EE->input->post('region_other')) ? $this->EE->input->post('region_other') : $address['region_other'],
							'country' => ($this->EE->input->post('country')) ? $this->EE->input->post('country') : $address['country'],
							'postal_code' => ($this->EE->input->post('postal_code')) ? $this->EE->input->post('postal_code') : $address['postal_code'],
							'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
							'company' => ($this->EE->input->post('company')) ? $this->EE->input->post('company') : $address['company'],
							'phone' => ($this->EE->input->post('phone')) ? $this->EE->input->post('phone') : $address['phone'],
							'username' => ($this->EE->input->post('username')) ? $this->EE->input->post('username') : '',
							'screen_name' => ($this->EE->input->post('screen_name')) ? $this->EE->input->post('screen_name') : '',
//							'cc_number'	=> ($this->EE->input->post('cc_number')) ? $this->EE->input->post('cc_number') : '',
//							'cc_name'	=> ($this->EE->input->post('cc_name')) ? $this->EE->input->post('cc_name') : '',
//							'cc_cvv2'	=> ($this->EE->input->post('cc_cvv2')) ? $this->EE->input->post('cc_cvv2') : '',
						));


			// Make our plan_id available for repopulating
			// a select_value
			if (!empty($plan))
			{
				$variables['membrr_plan_id'] = $plan['id'];
			}
			else
			{
				$variables['membrr_plan_id'] = '';
			}


			// pre-populate member custom fields
			$result = $this->EE->db->get('exp_member_fields');
			$fields = array();
			if ($result->num_rows() > 0) {
				foreach ($result->result_array() as $field) {
					$variables[$field['m_field_name']] = ($this->EE->input->post($field['m_field_name'])) ? $this->EE->input->post($field['m_field_name']) : '';
				}
			}


			// prep credit card fields

			// prep expiry month options
			$months = '';
			for ($i = 1; $i <= 12; $i++) {
		       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
		       $month_text = date('M',strtotime('2010-' . $month . '-01'));

		       $selected = ($this->EE->input->post('cc_expiry_month') and $this->EE->input->post('cc_expiry_month') == $month) ? ' selected="selected"' : '';

		       $months .= '<option value="' . $month . '"' . $selected . '>' . $month . ' - ' . $month_text . '</option>' . "\n";
		    }

		    $variables['cc_expiry_month_options'] = $months;

		    // prep same for years

		    $years = '';
			$now = date('Y');
			$future = $now + 10;
			for ($i = $now; $i <= $future; $i++) {
				$selected = ($this->EE->input->post('cc_expiry_year') and $this->EE->input->post('cc_expiry_year') == $i) ? ' selected="selected"' : '';

				$years .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
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

			// If the form was just submitted, we actually don't want to just display it again.
		    // This is because, if a user account was just created, EE thinks the user is logged in (but
		    // we just deleted their account, so they really aren't!)
		    // Let's save all the $variables we just prepped and reload the page
		    if ($this->EE->input->post('membrr_order_form')) {
		    	$this->EE->load->helper('url');

		    	// save data
		    	$temp_data_id = $this->membrr->SaveTempData(serialize($variables));

			    $this->EE->session->set_flashdata('membrr_variables', $temp_data_id);

			    // Check for 'error_redirect' variable. This will allow
			    // the user better control over redirects for AJAX submittals.
			    $rurl = $this->EE->TMPL->fetch_param('error_redirect_url');

			    if (!empty($rurl))
			    {
				    $url = $rurl;
			    } else
			    {
				    $url = current_url();
			    }

			    if ($this->is_ssl()) {
			    	$url = str_replace('http://','https://',$url);
			    }

			    header('Location: ' . $url);
			    die();
			}
		}

		// prep gateway options
		require(dirname(__FILE__) . '/opengateway.php');
		$this->server = new OpenGateway;
		$this->server->Authenticate($this->config['api_id'], $this->config['secret_key'], $this->config['api_url'] . '/api');
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_raw_options = array();

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_raw_options[] = array('id' => $gateway['id'], 'name' => $gateway['gateway']);
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];
			$gateway_raw_options[] = array('id' => $gateway['id'], 'name' => $gateway['gateway']);
		}

		$gateway_options = '';
		foreach ($gateway_raw_options as $gateway) {
			$gateway_options .= '<option value="' . $gateway['id'] . '">' . $gateway['name'] . '</option>';
		}

		$variables['gateway_options'] = $gateway_options;
		reset($gateway_raw_options);
		$variables['gateway_raw_options'] = $gateway_raw_options;

	    // require a CAPTCHA
	   	if ($this->config['use_captcha'] == TRUE) {
	   		// only show to logged out users?
	   		if ($this->EE->config->item('captcha_require_members') == 'y' OR $this->EE->session->userdata['member_id'] == 0) {
		    	$variables['captcha'] = $this->EE->functions->create_captcha();

		    	if (empty($variables['captcha'])) {
		    		return 'Error generating CAPTCHA.  Please verify CAPTCHA settings in Admin > Security &amp; Privacy > CAPTCHA Preferences or disable CAPTCHA in Membrr > Settings.';
		    	}
		    }
		    else {
		    	$variables['captcha'] = FALSE;
		    }
	    }
	    else {
	    	$variables['captcha'] = FALSE;
	    }

	    // prep form action
	    $variables['form_action'] = ($this->is_ssl()) ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
	    $variables['form_method'] = 'POST';

	    // parse the tag content with our new variables
	    $var_data = array();
	    $var_data[0] = $variables;

		$sub_return = $this->EE->TMPL->parse_variables($sub_return, $var_data);
		
		// EE 2.7 has made XID's mandatory in all requests... so let's hack it in there
		if (!defined('XID_SECURE_HASH')) {
			define('XID_SECURE_HASH',$this->EE->security->generate_xid());
		}
		$sub_return = str_replace('</form>', '<input type="hidden" name="XID" value="' . XID_SECURE_HASH . '" /></form>', $sub_return);

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
							'customer_email|Email Address' => 'empty|email|trim',
							'customer_company|Company' => '',
							'customer_phone|Phone' => ''
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
				$this->membrr->UpdateAddress($member_id,$values['customer_first_name'],$values['customer_last_name'],$values['customer_address'],$values['customer_address_2'],$values['customer_city'],$values['customer_region'],$values['customer_region_other'],$values['customer_country'],$values['customer_postal_code'],$values['customer_company'],$values['customer_phone']);

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
								'email' => $values['customer_email'],
								'company' => $values['customer_company'],
								'phone' => $values['customer_phone']
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
						'action'		=> ($this->is_ssl()) ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri(),
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
		if (!$this->EE->TMPL->fetch_param('plan_id') or !isset($plan) or !is_array($plan) or money_format("%!^i",$plan['price']) != '0.00') {
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

		// field: customer company
		if ($this->EE->input->post('customer_company')) {
			$customer_company = $this->EE->input->post('customer_company');
		}
		elseif (isset($address['company'])) {
			$customer_company = $address['company'];
		}
		else {
			$customer_company = '';
		}
		$return .= '<li class="label">
						<label for="customer_company">' . $this->EE->lang->line('membrr_order_form_customer_company') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_company" name="customer_company" value="' . $customer_company . '" />
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

		$customer_email = ($this->EE->input->post('customer_email')) ? $this->EE->input->post('customer_email') : $this->EE->session->userdata('email');
		$return .= '<li class="label">
						<label for="customer_email">' . $this->EE->lang->line('membrr_order_form_customer_email') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_email" name="customer_email" value="' . $customer_email . '" />
					</li>';

		// field: customer phone

		if ($this->EE->input->post('customer_phone')) {
			$customer_phone = $this->EE->input->post('customer_phone');
		}
		elseif (isset($address['phone'])) {
			$customer_phone = $address['phone'];
		}
		else {
			$customer_phone = '';
		}
		$return .= '<li class="label">
						<label for="customer_phone">' . $this->EE->lang->line('membrr_order_form_customer_phone') . '</label>
					</li>
					<li class="field">
						<input type="text" id="customer_phone" name="customer_phone" value="' . $customer_phone . '" />
					</li>';

		$return .= '</ul></fieldset>';

		$button_value = ($this->EE->TMPL->fetch_param('button')) ? $this->EE->TMPL->fetch_param('button') : 'Subscribe Now';
		
		if (!defined('XID_SECURE_HASH')) {
			define('XID_SECURE_HASH',$this->EE->security->generate_xid());
		}

		$return .=	'<fieldset class="subscribe">
						<legend>Subscribe</legend>
						<input type="submit" name="process_subscription" value="' . $button_value . '" />
					</fieldset>
					
					<input type="hidden" name="XID" value="' . XID_SECURE_HASH . '" />
					</form>';
					
					

		return $return;
    }

    function is_ssl () {
    	if ($_SERVER['SERVER_PORT'] == 443) {
    		return TRUE;
    	}

    	if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
    		return TRUE;
    	}

    	if (isset($_SERVER['HTTP_X_FORWARDED_PORT']) and $_SERVER['HTTP_X_FORWARDED_PORT'] == 443) {
    		return TRUE;
    	}

    	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    		return TRUE;
    	}

    	return FALSE;
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

						// do we need to perform some maintenance for renewals?
						if ($this->EE->input->get('renew_recurring_id')) {
							// validate old subscription
							$result = $this->EE->db->where('member_id', $this->EE->input->get('member'))
												   ->where('recurring_id', $this->EE->input->get('renew_recurring_id'))
												   ->get('exp_membrr_subscriptions');

							if ($result->num_rows() > 0) {
								$this->membrr->RenewalMaintenance($this->EE->input->get('renew_recurring_id'), $recurring['id']);
							}
						}

						$coupon = $this->EE->input->cookie('membrr_coupon');

						$this->membrr->RecordSubscription($recurring['id'], $this->EE->input->get('member'), $plan['id'], $next_charge_date, $end_date, $recurring['amount'], $coupon);

						// get the first charge
						$server->SetMethod('GetCharges');
						$server->Param('recurring_id',$recurring['id']);
						$charge = $server->Process();

						// if there was an initial payment, charge should be an array, but there shouldn't be multiple charges!
						if (!empty($charge) and isset($charge['charges']) and is_array($charge['charges']) and !isset($charge['charges']['charge'][0])) {
							$charge = $charge['charges']['charge'];
							$payment = $charge['amount'];
							$this->membrr->RecordPayment($recurring['id'], $charge['id'], $payment);
						}

						// redirect
						$cookie = $this->EE->input->cookie('membrr_redirect_url');

						if (!empty($cookie)) {
							$redirect_url = $cookie;
						}
						else {
							$redirect_url = $plan['redirect_url'];
						}

						header('Location: ' . $redirect_url);
						die();
					}
				}
			}
    	}

    	// is the secret key OK?  ie. is this a legitimate call?
		if ($this->EE->input->get_post('secret_key') != $config['secret_key']) {
			die('Invalid secret key.');
		}

		if (!$this->EE->input->get_post('customer_id') or !$this->EE->input->get_post('recurring_id')) {
			die('Insufficient data.');
		}

		// get customer data from server
		$server->SetMethod('GetCustomer');
		$server->Param('customer_id',$this->EE->input->get_post('customer_id'));
		$response = $server->Process();

		if (!is_array($response) or !isset($response['customer'])) {
			die('Error retrieving customer data.');
		}
		else {
			$customer = $response['customer'];
		}

		// get subscription data locally
		$subscription = $this->membrr->GetSubscription($this->EE->input->get_post('recurring_id'));

		if (!$subscription) {
			die('Error retrieving subscription data locally.');
		}

		if ($this->EE->input->get_post('action') == 'recurring_charge') {
			if (!$this->EE->input->get_post('charge_id')) {
				die('No charge ID.');
			}

			if (is_array($this->membrr->GetPayments(0,1,array('id' => $this->EE->input->get_post('charge_id'))))) {
		 		die('Charge already recorded.');
		 	}

			$server->SetMethod('GetCharge');
			$server->Param('charge_id',$this->EE->input->get_post('charge_id'));
			$charge = $server->Process();

			$charge = $charge['charge'];

		 	// record charge
			$this->membrr->RecordPayment($this->EE->input->get_post('recurring_id'), $this->EE->input->get_post('charge_id'), $charge['amount']);

			// update next charge date
			$plan = $this->membrr->GetPlan($subscription['plan_id']);

			if ($this->membrr->same_day_every_month == TRUE and $plan['interval'] % 30 === 0) {
				$months = $plan['interval'] / 30;
				$plural = ($months > 1) ? 's' : '';
				$next_charge_date = strtotime('today + ' . $months . ' month' . $plural);
			} else {
				$next_charge_date = strtotime('now + ' . $plan['interval'] . ' days');
			}

			if (!empty($subscription['end_date']) and (strtotime($subscription['end_date']) < $next_charge_date)) {
				// there won't be a next charge
				// subscription will expire beforehand
				$next_charge_date = '0000-00-00';
			}
			else {
				$next_charge_date = date('Y-m-d',$next_charge_date);
			}

			$this->membrr->SetNextCharge($this->EE->input->get_post('recurring_id'),$next_charge_date);
		}
		elseif ($this->EE->input->get_post('action') == 'recurring_cancel') {
			$this->membrr->CancelSubscription($subscription['id'],FALSE);
		}
		elseif ($this->EE->input->get_post('action') == 'recurring_expire' or $this->EE->input->get_post('action') == 'recurring_fail') {
			$this->membrr->CancelSubscription($subscription['id'],FALSE,TRUE);
		}
    }
}
