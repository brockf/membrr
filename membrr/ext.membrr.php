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

//	----------------------------------
//	Begin class
//	----------------------------------

class Membrr_ext
{
	var $ext_class		= "Membrr_ext";
	var $name			= "Membrr";
	var $version		= "1.0";
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
		//	----------------------------------
		//	SQL add field
		//	----------------------------------
		$insert_vars = array(
						'extension_id' => '',
						'class'        => $this->ext_class,
						'method'       => 'saef_form_membrr', // Redirect to Order Form?
						'hook'         => 'channel_standalone_form_start',
						'settings'     => '',
						'priority'     => 10,
						'version'      => $this->version,
						'enabled'      => 'y'
					);
		$this->EE->db->insert('exp_extensions',$insert_vars);
		
		$insert_vars = array(
						'extension_id' => '',
						'class'        => $this->ext_class,
						'method'       => 'saef_insert_membrr', // Record entry_id for subscription
						'hook'         => 'channel_standalone_insert_entry',
						'settings'     => '',
						'priority'     => 10,
						'version'      => $this->version,
						'enabled'      => 'y'
					);
		$this->EE->db->insert('exp_extensions',$insert_vars);
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
	* saef_form_membrr
	*
	* Redirect the user to the order form if they don't have an active subscription for this post
	*
	* @param string $return_form The entry form
	* @param string $catpcha The CAPTCHA
	* @param string $channel_id The numeric ID of the current channel
	* @return string The entry form if they are OK.  Redirects to order form if not OK.
	*/
    function saef_form_membrr ($return_form, $captcha, $channel_id) {
    	// are we protecting this?
    	if ($channel = $this->membrr->GetChannel($channel_id, 'channel_id')) {    		
    		if (!$this->EE->session->userdata('member_id')) {
	    		return $return_form;
	    	}
    		
    		/**
    		* Removed because we don't want to redirect when people are editing
    		
    		// yes we are, does this person have an active subscription?
    		if (!$this->membrr->GetSubscriptionForChannel($this->EE->session->userdata('member_id'),$channel['plans'],$channel['one_post'])) {
    			// nope, redirect to order form
    			header('Location: ' . $channel['order_form']);
    			die();
    		}
    		*/
    	}
    	
    	return $return_form;
    }
    
    /*
	* saef_insert_membrr
	*
	* Links the post to a subscription for protection
	*
	* @return boolean TRUE upon success
	*/
    function saef_insert_membrr () {
    	if (!$this->EE->session->userdata('member_id')) {
    		return FALSE;
    	}
    	
    	if (isset($_POST['entry_id'])) {
    		// we are editing
    		return FALSE;
    	}
    	if (!isset($_POST['channel_id']) or !is_numeric($_POST['channel_id'])) {
    		return FALSE;
    	}
    	
    	$_POST['channel_id'] = mysql_real_escape_string($_POST['channel_id']);
    	
    	// are we protecting this?
    	if ($channel = $this->membrr->GetChannel($_POST['channel_id'])) {
    		// yes we are, does this person have an active subscription?
    		
    		// we may be passed a specific subscription ID
    		$sub_id = (isset($_POST['subscription_id']) and !empty($_POST['subscription_id']) and is_numeric($_POST['subscription_id'])) ? $_POST['subscription_id'] : FALSE;
    		if (!$recurring_id = $this->membrr->GetSubscriptionForChannel($SESS->userdata['member_id'],$channel['plans'],$channel['one_post'], $sub_id)) {
    			// nope, redirect to order form
    			header('Location: ' . $channel['order_form']);
    			die();
    		}
    		else {
    			// link with $recurring_id
    			
    			// get next insert ID
    			$result = $this->EE->db->query("SHOW TABLE STATUS LIKE 'exp_channel_titles'");
				$row = $result->row_array();
				
				$insert_id = $row['Auto_increment'];
				
    			$insert = $this->EE->db->query('INSERT INTO `exp_membrr_channel_posts` (`channel_id`, `channel_entry_id`, `recurring_id`, `active`) VALUES (\'' . $channel['channel_id'] . '\', \'' . $insert_id . '\', \'' . $recurring_id . '\', \'1\');');
    		}
    	}
    	
    	return TRUE;
    }
}

//	End class