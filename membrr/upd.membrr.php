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

class Membrr_upd {
	var $version = '1.31';
	var $EE;
	
	function Membrr_upd () {
		$this->EE =& get_instance();
		
		return TRUE;
	}
	
	function update ($current = '') {
		if ($current < '1.0.2') {
			$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `expiry_processed` TINYINT NOT NULL AFTER `active`');
			$this->EE->db->query('ALTER TABLE `exp_membrr_config` DROP COLUMN `notification_url`');
			
			$insert_array = array(
								'class' => 'Membrr',
								'method' => 'post_notify'
							);
							
			$this->EE->db->insert('exp_actions',$insert_array);
		}
		
		if ($current < '1.0.3') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` MODIFY COLUMN `end_date` DATETIME');
        }
        
        if ($current < '1.0.5') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_payments` ADD COLUMN `refunded` TINYINT NOT NULL AFTER `date`');
        }
        
        if ($current < '1.0.6') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_plans` ADD COLUMN `plan_initial_charge` FLOAT AFTER `plan_price`');
        }
        
        if ($current < '1.0.8') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_plans` ADD COLUMN `plan_gateway` INT(11) AFTER `plan_initial_charge`');
        }
        
        if ($current < '1.0.91') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_channel_posts` ADD COLUMN `channel_post_date` DATETIME NOT NULL');
        }
        
        if ($current < '1.2') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `renewed_recurring_id` INT(11) AFTER `active`');
        }
	
		return TRUE;
	}
	
	function install () {
		$sql = array();       
        
        $sql[] = "INSERT INTO `exp_modules` (module_id, 
                                           module_name, 
                                           module_version, 
                                           has_cp_backend) 
                                           VALUES 
                                           ('', 
                                           'Membrr', 
                                           '" . $this->version . "', 
                                           'y')";
                                           
        $sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_config` (
				  `api_url` varchar(250) NOT NULL,
				  `api_id` varchar(80) NOT NULL,
				  `secret_key` varchar(80) NOT NULL,
				  `currency_symbol` varchar(10) NOT NULL,
				  `gateway` varchar(25) NOT NULL,
				  PRIMARY KEY  (`api_url`)
				  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
				  
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_plans` (
				  `plan_id` int(11) auto_increment ,
				  `api_plan_id` int(11) NOT NULL,
				  `plan_name` varchar(250) NOT NULL,
				  `plan_description` text NOT NULL,
				  `plan_free_trial` int(11) NOT NULL,
				  `plan_occurrences` int(11) NOT NULL,
				  `plan_price` float NOT NULL,
				  `plan_initial_charge` float,
				  `plan_gateway` int(11) NOT NULL,
				  `plan_interval` int(11) NOT NULL,
				  `plan_import_date` datetime NOT NULL,
				  `plan_redirect_url` varchar(250) NOT NULL,
				  `plan_member_group` int(11) NOT NULL,
				  `plan_member_group_expire` int(11) NOT NULL,
				  `plan_active` tinyint(4) NOT NULL,
				  `plan_deleted` tinyint(4) NOT NULL,
				  PRIMARY KEY  (`plan_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;";
				
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_subscriptions` (
				 `recurring_id` int(11) PRIMARY KEY ,
				 `member_id` INT NOT NULL ,
				 `plan_id` INT NOT NULL ,
				 `subscription_price` float NOT NULL,
				 `date_created` DATETIME NOT NULL ,
				 `date_cancelled` DATETIME NOT NULL ,
				 `end_date` DATETIME NOT NULL ,
				 `next_charge_date` DATE NOT NULL ,
				 `expired` TINYINT NOT NULL ,
				 `cancelled` TINYINT NOT NULL ,
				 `active` TINYINT NOT NULL ,
				 `renewed_recurring_id` INT(11) NOT NULL,
				 `expiry_processed` TINYINT NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=latin1 ;";
				 
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_payments` (
				 `payment_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `charge_id` INT NOT NULL ,
				 `recurring_id` INT NOT NULL ,
				 `amount` float NOT NULL ,
				 `date` DATETIME NOT NULL,
				 `refunded` TINYINT NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;";
				 
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_address_book` (
				 `address_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `member_id` INT NOT NULL,
				 `first_name` varchar(250) NOT NULL,
				 `last_name` varchar(250) NOT NULL,
				 `address` varchar(250) NOT NULL,
				 `address_2` varchar(250) NOT NULL,
				 `city` varchar(250) NOT NULL,
				 `region` varchar(250) NOT NULL,
				 `region_other` varchar(250) NOT NULL,
				 `country` varchar(250) NOT NULL,
				 `postal_code` varchar(250) NOT NULL 
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";
				 
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_channels` (
				 `protect_channel_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `channel_id` int(11) NOT NULL,
				 `plans` varchar(250) NOT NULL,
				 `one_post` INT NOT NULL,
				 `expiration_status` INT NOT NULL,
				 `order_form` text NOT NULL 
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";
				 
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_channel_posts` (
				 `post_id` INT AUTO_INCREMENT PRIMARY KEY,
				 `channel_id` int(11) NOT NULL,
				 `channel_entry_id` INT NOT NULL,
				 `recurring_id` INT NOT NULL,
				 `active` TINYINT NOT NULL,
				 `channel_post_date` DATETIME NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";
    
    	$sql[] = "INSERT INTO `exp_actions` (action_id, 
                                           class, 
                                           method) 
                                           VALUES 
                                           ('', 
                                           'Membrr',
                                           'post_notify')";
                                           
        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }
        
        return TRUE;
	}
	
	function uninstall () {
		$sql = array();       
        
        $sql[] = "DELETE FROM `exp_modules` WHERE `module_name`='Membrr'";
        $sql[] = "DROP TABLE `exp_membrr_plans`";
        $sql[] = "DROP TABLE `exp_membrr_config`";
        $sql[] = "DROP TABLE `exp_membrr_subscriptions`";
        $sql[] = "DROP TABLE `exp_membrr_address_book`";
        $sql[] = "DROP TABLE `exp_membrr_payments`";
        $sql[] = "DROP TABLE `exp_membrr_channels`";
        $sql[] = "DROP TABLE `exp_membrr_channel_posts`";
        $sql[] = 'DELETE FROM `exp_actions` WHERE `class` = \'Membrr\'';
    
        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }
        
        return TRUE;
	}
}