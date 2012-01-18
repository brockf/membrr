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

//	----------------------------------
//	Begin class
//	----------------------------------

class Membrr_ext
{
	var $ext_class		= "Membrr_ext";
	var $name			= "Membrr";
	var $version		= "1.32";
	var $description	= "Part of the Membrr for EE module for subscription memberships.";
	var $settings_exist	= "n";
	var $docs_url		= "";
	var $membrr;
	var $settings 		= array();
		
    //	----------------------------------
    //	Constructor
    //	----------------------------------
    
    function Membrr_ext ($settings = '')
    {
    	// load Membrr_EE class
    	if (!class_exists('Membrr_EE')) {
			require(dirname(__FILE__) . '/class.membrr_ee.php');
		}
		$this->membrr = new Membrr_EE;
		
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	//	End constructor
	
    //	----------------------------------
    //	Activate
    //	----------------------------------
	
    function activate_extension()
	{
		$insert_vars = array(
						'extension_id' => '',
						'class'        => $this->ext_class,
						'method'       => 'saef_insert_membrr', // Record entry_id for subscription
						'hook'         => 'entry_submission_redirect',
						'settings'     => '',
						'priority'     => 10,
						'version'      => $this->version,
						'enabled'      => 'y'
					);
		$this->EE->db->insert('exp_extensions',$insert_vars);
		
		$data = array(
				        'class'     => $this->ext_class,
				        'method'    => 'update_email',
				        'hook'      => 'user_edit_end',
				        'settings'  => '',
				        'priority'  => 10,
				        'version'   => $this->version,
				        'enabled'   => 'y'
				    );
		
		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
				        'class'     => $this->ext_class,
				        'method'    => 'member_after_update',
				        'hook'      => 'member_after_update',
				        'settings'  => '',
				        'priority'  => 10,
				        'version'   => $this->version,
				        'enabled'   => 'y'
				    );
		
		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
				        'class'     => $this->ext_class,
				        'method'    => 'zoo_visitor_update_end',
				        'hook'      => 'zoo_visitor_update_end',
				        'settings'  => '',
				        'priority'  => 10,
				        'version'   => $this->version,
				        'enabled'   => 'y'
				    );
		
		$this->EE->db->insert('exp_extensions', $data);
	}
	
	//	End activate
	
    //	----------------------------------
    //	Upgrade
    //	----------------------------------
	
    function update_extension ($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < '1.3') {
			$data = array(
		        'class'     => $this->ext_class,
		        'method'    => 'update_email',
		        'hook'      => 'user_edit_end',
		        'settings'  => '',
		        'priority'  => 10,
		        'version'   => $this->version,
		        'enabled'   => 'y'
		    );
		
		    $this->EE->db->insert('exp_extensions', $data);
		}
		
		if ($current < '1.31') {
			$data = array(
		        'class'     => $this->ext_class,
		        'method'    => 'member_after_update',
		        'hook'      => 'member_after_update',
		        'settings'  => '',
		        'priority'  => 10,
		        'version'   => $this->version,
		        'enabled'   => 'y'
		    );
		
		    $this->EE->db->insert('exp_extensions', $data);
		}
		
		if ($current < '1.32') {
			$data = array(
		        'class'     => $this->ext_class,
		        'method'    => 'zoo_visitor_update_end',
		        'hook'      => 'zoo_visitor_update_end',
		        'settings'  => '',
		        'priority'  => 10,
		        'version'   => $this->version,
		        'enabled'   => 'y'
		    );
		
		    $this->EE->db->insert('exp_extensions', $data);
		}
		
		$this->EE->db->update('exp_extensions',array('version' => $this->version),array('class' => $this->ext_class));
	}
	
	// --------------------------------
	//  Disable Extension
	// --------------------------------
	
	function disable_extension()
	{
	    $this->EE->db->delete('exp_extensions',array('class' => $this->ext_class));
	}
    
    /*
	* saef_insert_membrr
	*
	* Links the post to a subscription for protection
	*
	* @return boolean TRUE upon success
	*/
    function saef_insert_membrr ($entry_id, $meta_data, $post_data, $cp_call, $orig_loc) {
    	// we don't care about CP posts
    	if ($cp_call == TRUE) {
    		return $orig_loc;
    	}
    
    	// we don't care if they aren't logged in
    	if (!$this->EE->session->userdata('member_id')) {
    		return $orig_loc;
    	}
    	
    	// are we editing?
    	$this->EE->db->where('channel_entry_id',$entry_id);
    	$result = $this->EE->db->get('exp_membrr_channel_posts');
    	
    	if ($result->num_rows() > 0) {
    		// we are editing
    		return $orig_loc;
    	}
    	
    	// are we protecting this?
    	if ($channel = $this->membrr->GetChannel($meta_data['channel_id'],'exp_membrr_channels.channel_id')) {
    		// yes we are, does this person have an active subscription?
    		
    		// we may be passed a specific subscription ID
    		$sub_id = (isset($_POST['subscription_id']) and !empty($_POST['subscription_id']) and is_numeric($_POST['subscription_id'])) ? $_POST['subscription_id'] : FALSE;
    		if (!$recurring_id = $this->membrr->GetSubscriptionForChannel($channel['id'], $this->EE->session->userdata('member_id'),$channel['plans'],$channel['posts'], $sub_id)) {
    			// nope, redirect to order form
    			
    			return $channel['order_form'];
    		}
    		else {
    			// link with $recurring_id
    			$this->EE->db->query('INSERT INTO `exp_membrr_channel_posts` (`channel_id`, `channel_entry_id`, `recurring_id`, `active`) VALUES (\'' . $channel['id'] . '\', \'' . $entry_id . '\', \'' . $recurring_id . '\', \'1\');');
    		}
    	}
    	
    	return $orig_loc;
    }
    
    /**
    * Hook: update_email
    *
    * Update a customer's record at OG when they edit their profile
	* with Solspace's User module.
	*/
    function update_email ($member_id, $member_data, $custom_data) 
	{
	  	$config = $this->membrr->GetConfig();
		
		if ($config['update_email'] == FALSE or !isset($member_data['email'])) {
			return FALSE;
		}
		
		if (!class_exists('OpenGateway')) {
			require(dirname(__FILE__) . '/opengateway.php');
		}
		
		$connect_url = $config['api_url'] . '/api';
		$server = new OpenGateway;
		$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
		
		$server->SetMethod('GetCustomers');
		$server->Param('internal_id', $member_id);
		$response = $server->Process();
		
		if ($response['total_results'] > 0) {	
			// there is already a customer record here
			$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
			
		  	$server->SetMethod('UpdateCustomer');
			$server->Param('customer_id',$customer['id']);
			$server->Param('email', $member_data['email']);
			$response = $server->Process();
		}
		
		return TRUE;
	}
	
	/**
	* member_after_update
	*
	* Handle this hook call and pass above.
	*/
	function member_after_update ($member_id, $member_data) {
		return $this->update_email($member_id, $member_data, array());
	}
	
	/**
	* zoo_visitor_update_end
	*
	* Handle this type of hook call and pass to update_email
	*/
	function zoo_visitor_update_end ($member_data, $member_id) {
		return $this->update_email($member_id, $member_data, array());
	}
}

//	End class