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

class Membrr_mcp {
	var $membrr; // Membrr_EE Class
	var $EE;	 // EE SuperObject
	var $server; // OpenGateway
	var $per_page = 50;

	function Membrr_mcp () {
		// load EE superobject
		$this->EE =& get_instance();

		// load Membrr_EE class
		require(dirname(__FILE__) . '/class.membrr_ee.php');
		$this->membrr = new Membrr_EE;

		// load config
		$this->config = $this->membrr->GetConfig();

		// load OpenGateway
		if (isset($this->config['api_url'])) {
			require(dirname(__FILE__) . '/opengateway.php');
			$this->server = new OpenGateway;

			$this->server->Authenticate($this->config['api_id'], $this->config['secret_key'], $this->config['api_url'] . '/api');
		}

        // if the Membrr extension isn't active, that's an issue
        $this->EE->db->select('extension_id');
        $this->EE->db->where('class','Membrr_extension');
        $this->EE->db->where('enabled','y');
		$ext = $this->EE->db->get('exp_extensions');

        if ($ext->num_rows() == 0) {
        	// TODO
        	// Display an extension error!
        }

        // check the post_notify Action ID is set
        $this->EE->db->select('action_id');
		$this->EE->db->where('class','Membrr');
		$this->EE->db->where('method','post_notify');
		$result = $this->EE->db->get('exp_actions');

		if ($result->num_rows() == 0) {
			// there was a bug in the installer that left this blank for several people
			// so we will insert it now
			$insert_array = array(
								'class' => 'Membrr',
								'method' => 'post_notify'
							);
			$this->EE->db->insert('exp_actions',$insert_array);

			die(show_error('There was an error in your Membrr configuration concerning the notification URLs between OpenGateway and Membrr.  This has been fixed.  However, you will need to <a href="' . $this->cp_url('sync') . '">run the Sync to Update feature to complete the fix</a>.'));
		}

        // prep navigation
        $this->EE->cp->set_right_nav(array(
        					'membrr_dashboard' => $this->cp_url(),
        					'membrr_create_subscription' => $this->cp_url('add_subscription'),
        					'membrr_subscriptions' => $this->cp_url('subscriptions'),
        					'membrr_payments' => $this->cp_url('payments'),
        					'membrr_channel_protector' => $this->cp_url('channels'),
        					'membrr_plans' => $this->cp_url('plans'),
        					'membrr_settings' => $this->cp_url('settings'),
        					'membrr_sync' => $this->cp_url('sync')
        				));

        // set breadcrumb for the entire module
        $this->EE->cp->set_breadcrumb($this->cp_url(), $this->EE->lang->line('membrr_module_name'));

        // load required libraries
        $this->EE->load->library('table');

        // load CSS
        $this->EE->cp->add_to_head('<style type="text/css" media="screen">
        								div.membrr_box {
        									border: 1px solid #ccc;
        									background-color: #fff;
        									padding: 10px;
        									margin: 10px;
        									line-height: 1.4em;
        								}

        								div.membrr_error {
        									border: 1px solid #aa0303;
        									background-color: #aa0303;
        									color: #fff;
        									font-weight: bold;
        									padding: 10px;
        									margin: 10px;
        									line-height: 1.4em;
        								}

        								ul.membrr {
        									list-style-type: square;
        									margin-left: 25px;
        									margin-top: 10px;
        								}

        								ul.membrr li {
        									padding: 5px;
        								}
        							</style>');

        // add JavaScript
        $this->EE->cp->add_to_head('<script type="text/javascript">
        								$(document).ready(function() {
        									$(\'a.confirm\').click(function () {
        										if (!confirm(\'Are you sure you want to delete this?\')) {
        											return false;
        										}
        									});
        								});
        							</script>');
	}
	
	function set_page_title ($title) {
		if (version_compare(APP_VER, '2.6', '>=')) {
			$this->EE->view->cp_page_title = $title;
		} else {
			$this->EE->cp->set_variable('cp_page_title', $title);
		}
	}

	function index () {
		// if not configured, send to settings
		if (!$this->config) {
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
			die();
		}

		// page title
		$this->set_page_title($this->EE->lang->line('membrr_dashboard'));
		
		$plans = $this->membrr->GetPlans();
		
		/**
		* NEW REPORTS CODE
		**/
		
		$plan_options = array();
		$plan_options[0] = 'All Subscription Plans';
		if (!empty($plans)) {
			foreach ($plans as $plan) {
				$occurrences = ($plan['occurrences'] == 0) ? 'infinite' : $plan['occurrences'] . ' charges';
				$plan_options[$plan['id']] = $plan['name'] . ' (' . $plan['interval'] . ' days | ' . $occurrences . ')';
			}
		}
		
		$current_plan = ($this->EE->input->get_post('plan_id')) ? $this->EE->input->get_post('plan_id') : 0;
		
		// build date components
		$days = array();
		for ($i = 1; $i <= 31; $i++) {
			$days[$i] = $i;
		}
		
		$months = array();
		for ($i = 1; $i <= 12; $i++) {
			$months[date('m', strtotime('2013-' . $i . '-01 12:12:12'))] = date('F', strtotime('2013-' . $i . '-01 12:12:12'));
		}
		
		$years = array();
		for ($i = date('Y') - 5; $i <= date('Y'); $i++) {
			$years[$i] = $i;
		}
		
		$current_day = date('d');
		$current_month = date('m');
		$current_year = date('Y');
		
		// count subscriptions, renewals, etc.
		
		$start_year = ($this->EE->input->get_post('start_year')) ? $this->EE->input->get_post('start_year') : (($current_month > 1) ? $current_year : $current_year - 1);
		$start_month = ($this->EE->input->get_post('start_month')) ? $this->EE->input->get_post('start_month') : (($current_month > 1) ? $current_month - 1 : 12);
		$start_day = ($this->EE->input->get_post('start_day')) ? $this->EE->input->get_post('start_day') : $current_day;
		
		$end_year = ($this->EE->input->get_post('end_year')) ? $this->EE->input->get_post('end_year') : $current_year;
		$end_month = ($this->EE->input->get_post('end_month')) ? $this->EE->input->get_post('end_month') : $current_month;
		$end_day = ($this->EE->input->get_post('end_day')) ? $this->EE->input->get_post('end_day') : $current_day;
		
		$start_full = $start_year . '-' . $start_month . '-' . $start_day;
		$end_full = $end_year . '-' . $end_month . '-' . $end_day;
		
		if (strtotime($start_full) > strtotime($end_full)) {
			die(show_error('Invalid dates selected for reports. Start date must precede end date'));
		}
		
		// get the total number of subscriptions at end date
		$total_subs = $this->EE->db->select('COUNT(recurring_id) AS `counted`',FALSE)
											->select('plan_id')
											->where('DATE(date_created) <= "' . $end_full . '" AND (DATE(end_date) > "' . $end_full . '" OR end_date = "0000-00-00 00:00:00")')
											->group_by('plan_id')
											->get('exp_membrr_subscriptions');
		
		$plan_totals = array();									
		foreach ($total_subs->result_array() as $plan_total) {
			$plan_totals[$plan_total['plan_id']] = $plan_total['counted'];
		}									
		
		// for some reason, since we added the DB cache, we get these harmless error notices
		$prev_error_setting = error_reporting();
		error_reporting(0);
		
		// are we limiting to a particular subscription?
		if ($current_plan != 0) {
			$this->EE->db->start_cache();
			$this->EE->db->where('exp_membrr_subscriptions.plan_id', $current_plan);
			$this->EE->db->stop_cache();
		}
		
		// count total subscriptions
		$count_subscriptions = $this->EE->db->select('COUNT(recurring_id) AS `counted`',FALSE)
											->where('DATE(date_created) >=', $start_full)
											->where('DATE(date_created) <=', $end_full)
											->where('renewal','0')
											->get('exp_membrr_subscriptions')
											->row()
											->counted;										
											
		$count_renewals = 	   $this->EE->db->select('COUNT(recurring_id) AS `counted`',FALSE)
											->where('DATE(date_created) >=', $start_full)
											->where('DATE(date_created) <=', $end_full)
											->where('renewal','1')
											->get('exp_membrr_subscriptions')
											->row()
											->counted;			
		
		// count expirations and cancellations
		$count_cancellations = $this->EE->db->select('COUNT(recurring_id) AS `counted`',FALSE)
											->where('DATE(date_cancelled) >=', $start_full)
											->where('DATE(date_cancelled) <=', $end_full)
											->where('cancelled','1')
											->get('exp_membrr_subscriptions')
											->row()
											->counted;
											
		$count_expirations =   $this->EE->db->select('COUNT(recurring_id) AS `counted`',FALSE)
											->where('DATE(end_date) >=', $start_full)
											->where('DATE(end_date) <=', $end_full)
											->where('expired','1')
											->get('exp_membrr_subscriptions')
											->row()
											->counted;
											
		// get subscriptions
		
		// pass startdate, enddate, and reporttype in GET or POST
		// get subscriptions directly from database
		
		// get pagination
		$offset = ($this->EE->input->get('rownum')) ? $this->EE->input->get('rownum') : 0;

		// add JavaScript for options dropdown
		$this->EE->cp->add_to_head("<script type=\"text/javascript\">
        								$(document).ready(function() {
        									$('select.sub_options').change(function () {
        										if ($(this).val() != '') {
        											window.location.href = $(this).val();
        										}
        									});
        								});
        							</script>");

		// get latest subscriptions
		$this->EE->db->select('*');
		$this->EE->db->limit($this->per_page, $offset);								
		
		if ($this->EE->input->get_post('show') == 'expirations') {
			$this->EE->db->where('DATE(end_date) >=', $start_full);
			$this->EE->db->where('DATE(end_date) <=', $end_full);
			$this->EE->db->where('expired','1');
			
			$total = $count_expirations;
			
			$show = 'expirations';
		}
		elseif ($this->EE->input->get_post('show') == 'renewals') {
			$this->EE->db->where('DATE(date_created) >=', $start_full);
			$this->EE->db->where('DATE(date_created) <=', $end_full);
			$this->EE->db->where('renewal','1');
			
			$total = $count_renewals;
			
			$show = 'renewals';
		}
		elseif ($this->EE->input->get_post('show') == 'cancellations') {
			$this->EE->db->where('DATE(date_cancelled) >=', $start_full);
			$this->EE->db->where('DATE(date_cancelled) <=', $end_full);
			$this->EE->db->where('cancelled','1');
			
			$total = $count_cancellations;
			
			$show = 'cancellations';
		}
		else {
			$this->EE->db->where('DATE(date_created) >=', $start_full);
			$this->EE->db->where('DATE(date_created) <=', $end_full);
			$this->EE->db->where('renewal','0');
			
			$total = $count_subscriptions;
			
			$show = 'subscriptions';
		}
							
		$this->EE->db->join('exp_members','exp_membrr_subscriptions.member_id = exp_members.member_id','left');
		$this->EE->db->join('exp_membrr_plans','exp_membrr_subscriptions.plan_id = exp_membrr_plans.plan_id','left');
		$subscriptions_q = $this->EE->db->get('exp_membrr_subscriptions');
		
		// flush cache with subscription plan ID
		$this->EE->db->flush_cache();
		error_reporting($prev_error_setting);
		
		$subscriptions = array();
		foreach ($subscriptions_q->result_array() as $subscription) {
			$subscriptions[] = $subscription;
		}

		if (is_array($subscriptions)) {
			// append $options links
			foreach ($subscriptions as $key => $subscription) {
				$options = array();
				$options[$this->EE->lang->line('membrr_view')] = $this->cp_url('subscription', array('id' => $subscription['recurring_id']));

				if (empty($subscription['renewed'])) {
					$options[$this->EE->lang->line('membrr_renew')] = $this->cp_url('renew_subscription',array('id' => $subscription['recurring_id']));
				}

				if ($subscription['active'] == '1') {
					if (!empty($subscription['card_last_four'])) {
						$options[$this->EE->lang->line('membrr_update_cc')] = $this->cp_url('update_cc', array('id' => $subscription['recurring_id']));
					}

					if ($subscription['end_date'] != FALSE) {
						$options[$this->EE->lang->line('membrr_change_expiration')] = $this->cp_url('expiry', array('id' => $subscription['recurring_id']));
					}

					$options[$this->EE->lang->line('membrr_cancel')] = $this->cp_url('cancel_subscription',array('id' => $subscription['recurring_id']));
				}

				$subscriptions[$key]['options'] = $options;
				$subscriptions[$key]['member_link'] = $this->member_link($subscription['member_id']);
			}

			reset($subscriptions);
		}

		// pass the relevant data to the paginate class so it can display the "next page" links
		$this->EE->load->library('pagination');
		$p_config = $this->pagination_config('index', $total, array('start_month' => $start_month, 'start_day' => $start_day, 'start_year' => $start_year, 'end_month' => $end_month, 'end_day' => $end_day, 'end_year' => $end_year, 'show' => $this->EE->input->get_post('show')));

		$this->EE->pagination->initialize($p_config);

		/*
		* SEND TO TEMPLATE
		*/
		
		$vars = array();
		$vars['reports_action'] = $this->form_url();
		$vars['config'] = $this->config;
		$vars['plans'] = $plans;
		$vars['plan_options'] = $plan_options;
		$vars['current_plan'] = $current_plan;
		$vars['months'] = $months;
		$vars['days'] = $days;
		$vars['years'] = $years;
		$vars['current_month'] = $current_month;
		$vars['current_day'] = $current_day;
		$vars['current_year'] = $current_year;
		$vars['start_month'] = $start_month;
		$vars['start_day'] = $start_day;
		$vars['start_year'] = $start_year;
		$vars['end_month'] = $end_month;
		$vars['end_day'] = $end_day;
		$vars['end_year'] = $end_year;
		$vars['count_subscriptions'] = $count_subscriptions;
		$vars['count_renewals'] = $count_renewals;
		$vars['count_cancellations'] = $count_cancellations;
		$vars['count_expirations'] = $count_expirations;
		$vars['subscriptions'] = $subscriptions;
		$vars['plan_totals'] = $plan_totals;
		$vars['pagination'] = $this->EE->pagination->create_links();
		$vars['cp_url'] = $this->cp_url();																						
		$vars['show'] = $show;

		return $this->EE->load->view('dashboard',$vars, TRUE);
	}

	function current_action ($action) {
		$DSP->title = $this->nav[$action];

		$this->current_action = $action;

		return true;
	}

	function cp_url ($action = 'index', $variables = array()) {
		$url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp' . AMP . 'module=membrr'.AMP.'method=' . $action;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	function form_url ($action = 'index', $variables = array()) {
		$url = AMP.'C=addons_modules'.AMP.'M=show_module_cp' . AMP . 'module=membrr'.AMP.'method=' . $action;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	function member_link ($member_id) {
		$url = BASE.AMP.'D=cp'.AMP.'C=myaccount'.AMP.'id='. $member_id;

		return $url;
	}

	function sync () {
		// perform sync update and checks

		// page title
		$this->set_page_title($this->EE->lang->line('membrr_sync'));

		// store each check in array with keys [ok = true|false], [message = text]
		$checks = array();

		// check API connection
		$api_connection = false;

		// does URL exist?
		$headers = @get_headers($this->config['api_url']);
		if (!empty($headers) and (!isset($headers[0]) or strstr($headers[0],'404') or strstr($headers[0],'403'))) {
			$checks[] = array('ok' => false, 'text' => $this->EE->lang->line('membrr_url_doesnt_exist'));
		}
		else {
			$this->server->SetMethod('GetCharges');
			$response = $this->server->Process();

			if (!isset($response['error'])) {
				// it's valid!
				$api_connection = true;
				$checks[] = array('ok' => true, 'text' => $this->EE->lang->line('membrr_sync_config_ok'));
			}
			else {
				$checks[] = array('ok' => false, 'text' => $this->EE->lang->line('membrr_sync_config_failed'));
			}
		}

		if ($api_connection == true) {
			// we can connect to the API, so let's do the sync
			$plans = $this->membrr->GetPlans();

			if (is_array($plans)) {
				foreach ($plans as $plan) {
					// update price, interval, etc.
					$this->server->SetMethod('GetPlan');

					$this->server->Param('plan_id',$plan['api_id']);
					$api_plan = $this->server->Process();
					if (isset($api_plan['plan'])) {
						$api_plan = $api_plan['plan'];

						$update_array = array(
												'plan_interval' => $api_plan['interval'],
												'plan_free_trial' => $api_plan['free_trial'],
												'plan_occurrences' => $api_plan['occurrences'],
												'plan_price' => $api_plan['amount']
											);

						// if the plan is deleted at OG, we'll set it to "not for sale"
						if ($api_plan['status'] == 'deleted') {
							$update_array['plan_active'] = '0';
						}

						$this->EE->db->where('plan_id',$plan['id']);
						$this->EE->db->update('exp_membrr_plans',$update_array);

						if ($api_plan['status'] == 'active') {
							$checks[] = array('ok' => true, 'text' => $plan['name'] . ' - ' . $this->EE->lang->line('membrr_sync_plan_updated'));
						}
						elseif ($api_plan['status'] == 'deleted' and $plan['deleted'] == '1') {
							// plan is deleted but was previously
							$checks[] = array('ok' => true, 'text' => '(Inactive) ' . $plan['name'] . ' - ' . $this->EE->lang->line('membrr_sync_plan_updated'));
						}
						elseif ($api_plan['status'] == 'deleted' and $plan['deleted'] == '0') {
							// plan was deleted at OpenGateway but not in the Membrr plugin
							$this->membrr->DeletePlan($plan['id']);

							$checks[] = array('ok' => false, 'text' => $plan['name'] . ' - ' . $this->EE->lang->line('membrr_sync_plan_deleted'));
						}

						$notification_url = $this->get_notification_url();

						// do we have to reset the OpenGateway notification_url ?
						if ($api_plan['notification_url'] != $notification_url) {
							// yes, reset it
							$this->server->SetMethod('UpdatePlan');
							$this->server->Param('plan_id',$plan['api_id']);
							$this->server->Param('notification_url', $notification_url);
							$response = $this->server->Process();

							$checks[] = array('ok' => true, 'text' => $plan['name'] . ' - ' . $this->EE->lang->line('membrr_sync_plan_notify_reset'));
						}

						// update next_charge_date
						$offset = 0;
						$limit = 100;

						$this->server->SetMethod('GetRecurrings');
						$this->server->Param('plan_id',$plan['api_id']);
						$this->server->Param('offset',$offset);
						$this->server->Param('limit',$limit);
						$response = $this->server->Process();

						while (isset($response['results']) and $response['results'] > 0) {
							// we may only get one back, that changes the PHP array
							if (isset($response['recurrings']['recurring'][0])) {
								$recurrings = $response['recurrings']['recurring'];
							}
							else {
								$recurrings = array();
								$recurrings[] = $response['recurrings']['recurring'];
							}

							// iterate through
							foreach ($recurrings as $recurring) {
								$next_charge_date = date('Y-m-d',strtotime($recurring['next_charge_date']));
								$this->EE->db->update('exp_membrr_subscriptions',array('next_charge_date' => $next_charge_date),array('recurring_id' => $recurring['id']));
							}

							$checks[] = array('ok' => true, 'text' => $plan['name'] . ' - ' . $this->EE->lang->line('membrr_sync_plan_next_charge'));

							// update offset
							$offset = $offset + $limit;

							// get next batch
							$this->server->SetMethod('GetRecurrings');
							$this->server->Param('plan_id',$plan['api_id']);
							$this->server->Param('offset',$offset);
							$this->server->Param('limit',$limit);
							$response = $this->server->Process();
						}
					}
				}
			}
		}

		// load view
		$vars = array();
		$vars['checks'] = $checks;

		return $this->EE->load->view('sync',$vars,TRUE);
	}

	function channels () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_channel_protector'));

		$channels = $this->membrr->GetChannels();
		$plans = $this->membrr->GetPlans();

		// prep plan names
		if (is_array($plans)) {
			foreach ($plans as $plan) {
				$plan_names[$plan['id']] = $plan['name'];
			}
			unset($plans, $plan);
		}

		if (is_array($channels)) {
			foreach ($channels as $key => $channel) {
				foreach ($channel['plans'] as $key2 => $plan) {
					$channels[$key]['display_plans'][] = $plan_names[$plan];
				}

				$channels[$key]['options'] = '<a href="' . $this->cp_url('edit_channel',array('id' => $channel['id'])) . '">' . $this->EE->lang->line('membrr_edit') . '</a> | <a class="confirm" href="' . $this->cp_url('delete_channel',array('id' => $channel['id'])) . '">' . $this->EE->lang->line('membrr_delete') . '</a>';
			}

			reset($channels);
		}

		// load view
		$vars = array();
		$vars['channels'] = $channels;
		$vars['config'] = $this->config;
		$vars['form_action'] = $this->form_url('new_channel');

		return $this->EE->load->view('channels',$vars,TRUE);
	}

	function delete_channel () {
		$this->membrr->DeleteChannel($this->EE->input->get('id'));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_deleted_channel'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('channels')));
		die();
	}

	function new_channel () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_protect_a_channel'));

		$this->EE->load->model('channel_model');
		$channels = $this->EE->channel_model->get_channels();

		$channel_options = array();

		// make sure we don't duplicate
		$current_channels = $this->membrr->GetChannels();
		if (is_array($current_channels)) {
			foreach ($current_channels as $channel) {
				$no_display[] = $channel['channel_id'];
			}
		}

		foreach ($channels->result_array() as $channel) {
			if (!isset($no_display) or !in_array($channel['channel_id'], $no_display)) {
	        	$channel_options[$channel['channel_id']] = $channel['channel_name'];
	        }
        }

		// load view
		$vars = array();
		$vars['form_action'] = $this->form_url('new_channel_2');
		$vars['channels'] = $channel_options;

		return $this->EE->load->view('new_channel',$vars,TRUE);
	}

	function new_channel_2 () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_protect_a_channel'));

		$this->EE->load->library('form_validation');

		// must have a plan ID
		if ($this->EE->input->post('channel_id') == '') {
			return $this->new_channel();
		}

		// check for a form submission
		if ($this->EE->input->post('expiration_status')) {
			$this->EE->form_validation->set_rules('plans[]','lang:membrr_required_subscription','trim|required');
			$this->EE->form_validation->set_rules('order_form','lang:membrr_no_subscription_redirect','trim|empty');

			if ($this->EE->form_validation->run() != FALSE) {
				$plans = implode('|',$this->EE->input->post('plans'));

				$insert_vars = array(
									'channel_id' => $this->EE->input->post('channel_id'),
									'plans' => $plans,
									'posts' => ($this->EE->input->post('unlimited_posts') == '1') ? '0' : $this->EE->input->post('posts'),
									'expiration_status' => $this->EE->input->post('expiration_status'),
									'order_form' => $this->EE->input->post('order_form')
								);
				$this->EE->db->insert('exp_membrr_channels',$insert_vars);

				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_created_channel'));

				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('channels')));
				die();
			}
		}

		$plans = $this->membrr->GetPlans();

        $plan_options = array();
        if (is_array($plans)) {
	        foreach ($plans as $plan) {
	       		$plan_options[$plan['id']] = $plan['name'];
	        }
        }

        // get channel info
        $this->EE->load->model('channel_model');
        $channel = $this->EE->channel_model->get_channels(null,array(),array(array('channel_id' => $this->EE->input->post('channel_id'))));
        $channel = $channel->row_array();

        $statuses = $this->EE->channel_model->get_channel_statuses($channel['status_group']);

        $status_options = array();
        foreach ($statuses->result_array() as $status) {
        	$status_options[$status['status_id']] = $status['status'];
        }

        // default values
        $plans = ($this->EE->input->post('plans')) ? $this->EE->input->post('plans') : '';
        $one_post =  ($this->EE->input->post('one_post')) ? $this->EE->input->post('one_post') : '1';
        $order_form =  ($this->EE->input->post('order_form')) ? $this->EE->input->post('order_form') : $this->EE->functions->fetch_site_index();
        $expiration_status =  ($this->EE->input->post('expiration_status')) ? $this->EE->input->post('expiration_status') : '';

		// load view
		$vars = array();
		$vars['plan_options'] = $plan_options;
		$vars['statuses'] = $status_options;
		$vars['form_action'] = $this->form_url('new_channel_2');
		$vars['channel'] = $channel;
		$vars['config'] = $this->config;
		$vars['plans'] = $plans;
		$vars['one_post'] = $one_post;
		$vars['order_form'] = $order_form;
		$vars['expiration_status'] = $expiration_status;

		return $this->EE->load->view('new_channel_2',$vars, TRUE);
	}

	function edit_channel () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_protect_a_channel'));

		$this->EE->load->library('form_validation');

		// must have a channel ID
		if ($this->EE->input->get('id') == '') {
			return $this->channels();
		}

		// check for a form submission
		if ($this->EE->input->post('expiration_status')) {
			$this->EE->form_validation->set_rules('plans[]','lang:membrr_required_subscription','trim|required');
			$this->EE->form_validation->set_rules('order_form','lang:membrr_no_subscription_redirect','trim|empty');

			if ($this->EE->form_validation->run() != FALSE) {
				$plans = implode('|',$this->EE->input->post('plans'));

				$update_vars = array(
									'plans' => $plans,
									'posts' => ($this->EE->input->post('unlimited_posts') == '1') ? '0' : $this->EE->input->post('posts'),
									'expiration_status' => $this->EE->input->post('expiration_status'),
									'order_form' => $this->EE->input->post('order_form')
								);
				$this->EE->db->update('exp_membrr_channels',$update_vars,array('protect_channel_id' => $this->EE->input->get('id')));

				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_edited_channel'));

				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('channels')));
				die();
			}
		}

		// get channel
		$channel = $this->membrr->GetChannel($this->EE->input->get('id'));

		// load plans
		$plans = $this->membrr->GetPlans();

        $plan_options = array();
        foreach ($plans as $plan) {
       		$plan_options[$plan['id']] = $plan['name'];
        }

        // get channel info for statuses
        $this->EE->load->model('channel_model');
        $channel_db = $this->EE->channel_model->get_channels(null,array(),array(array('channel_id' => $channel['channel_id'])));
        $channel_db = $channel_db->row_array();

        $statuses = $this->EE->channel_model->get_channel_statuses($channel_db['status_group']);

        $status_options = array();
        foreach ($statuses->result_array() as $status) {
        	$status_options[$status['status_id']] = $status['status'];
        }

        // default values
        $plans = ($this->EE->input->post('plans')) ? $this->EE->input->post('plans') : $channel['plans'];
        $order_form =  ($this->EE->input->post('order_form')) ? $this->EE->input->post('order_form') : $channel['order_form'];
        $expiration_status =  ($this->EE->input->post('expiration_status')) ? $this->EE->input->post('expiration_status') : $channel['expiration_status'];

		// load view
		$vars = array();
		$vars['channel'] = $channel;
		$vars['plan_options'] = $plan_options;
		$vars['statuses'] = $status_options;
		$vars['form_action'] = $this->form_url('edit_channel', array('id' => $channel['id']));
		$vars['config'] = $this->config;
		$vars['plans'] = $plans;
		$vars['posts'] = $channel['posts'];
		$vars['order_form'] = $order_form;
		$vars['expiration_status'] = $expiration_status;

		return $this->EE->load->view('edit_channel',$vars, TRUE);
	}

	function payments () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_payments'));

		// get pagination
		$offset = ($this->EE->input->get('rownum')) ? $this->EE->input->get('rownum') : 0;

		// get latest payments
		$payments = $this->membrr->GetPayments($offset,$this->per_page);

		if (is_array($payments)) {
			foreach ($payments as $key => $payment) {
				$payments[$key]['sub_link'] = '<a href="' . $this->cp_url('subscription',array('id' => $payment['recurring_id'])) . '">' . $payment['recurring_id'] . '</a>';
				if ($payment['amount'] != '0.00') {
					$payments[$key]['refund_text'] = ($payment['refunded'] == '0') ? '<a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('payments')))))) . '">' . $this->EE->lang->line('membrr_refund') . '</a>' : 'refunded';
				}
				else {
					$payments[$key]['refund_text'] = '';
				}

				$payments[$key]['member_link'] = $this->member_link($payment['member_id']);
			}
			reset($payments);
		}

		// pagination
		$total = $this->EE->db->count_all('exp_membrr_payments');

		// pass the relevant data to the paginate class so it can display the "next page" links
		$this->EE->load->library('pagination');
		$p_config = $this->pagination_config('payments', $total);

		$this->EE->pagination->initialize($p_config);

		$vars = array();
		$vars['payments'] = $payments;
		$vars['config'] = $this->config;
		$vars['pagination'] = $this->EE->pagination->create_links();

		return $this->EE->load->view('payments',$vars, TRUE);
	}

	function refund () {
		$response = $this->membrr->Refund($this->EE->input->get('id'));

		if ($response['success'] != TRUE) {
			return $this->EE->load->view('error',array('error' => $response['error']), TRUE);
		}
		else {
			// it refunded
			header('Location: ' . base64_decode(urldecode($this->EE->input->get('return'))));
			die();
		}
	}

	function subscriptions () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_subscriptions'));

		// get pagination
		$offset = ($this->EE->input->get('rownum')) ? $this->EE->input->get('rownum') : 0;

		// is there a query
		if ($this->EE->input->get('search')) {
			$filters = array('search' => $this->EE->input->get('search'));
		}
		else {
			$filters = array();
		}

		// add JavaScript for options dropdown
		$this->EE->cp->add_to_head("<script type=\"text/javascript\">
        								$(document).ready(function() {
        									$('select.sub_options').change(function () {
        										if ($(this).val() != '') {
        											window.location.href = $(this).val();
        										}
        									});
        								});
        							</script>");

		// get latest payments
		$subscriptions = $this->membrr->GetSubscriptions($offset,$this->per_page, $filters);

		if (is_array($subscriptions)) {
			// append $options links
			foreach ($subscriptions as $key => $subscription) {
				$options = array();
				$options[$this->EE->lang->line('membrr_view')] = $this->cp_url('subscription', array('id' => $subscription['id']));

				if (empty($subscription['renewed'])) {
					$options[$this->EE->lang->line('membrr_renew')] = $this->cp_url('renew_subscription',array('id' => $subscription['id']));
				}

				if ($subscription['active'] == '1') {
					if (!empty($subscription['card_last_four'])) {
						$options[$this->EE->lang->line('membrr_update_cc')] = $this->cp_url('update_cc', array('id' => $subscription['id']));
					}

					if ($subscription['end_date'] != FALSE) {
						$options[$this->EE->lang->line('membrr_change_expiration')] = $this->cp_url('expiry', array('id' => $subscription['id']));
					}

					$options[$this->EE->lang->line('membrr_cancel')] = $this->cp_url('cancel_subscription',array('id' => $subscription['id']));
				}

				$subscriptions[$key]['options'] = $options;
				$subscriptions[$key]['member_link'] = $this->member_link($subscription['member_id']);
			}

			reset($subscriptions);
		}

		// pagination
		if (!empty($filters)) {
			$total = count($this->membrr->GetSubscriptions(0,10000,$filters));
		}
		else {
			$result = $this->EE->db->select('count(recurring_id) AS total_rows',FALSE)->from('exp_membrr_subscriptions')->get();
			$total = $result->row()->total_rows;
		}

		// pass the relevant data to the paginate class so it can display the "next page" links
		$this->EE->load->library('pagination');
		$p_config = $this->pagination_config('subscriptions&search=' . $this->EE->input->get('search'), $total);

		$this->EE->pagination->initialize($p_config);

		// get search fields
		$url = htmlspecialchars_decode($this->cp_url('subscriptions'));
		$url = explode('?',$url);
		$params = array();
		parse_str($url[1],$params);

		$vars = array();
		$vars['subscriptions'] = $subscriptions;
		$vars['pagination'] = $this->EE->pagination->create_links();
		$vars['config'] = $this->config;
		$vars['search_fields'] = $params;
		$vars['search_query'] = $this->EE->input->get('search');
		$vars['cp_url'] = $this->cp_url('subscriptions');

		return $this->EE->load->view('subscriptions',$vars, TRUE);
	}

	function subscription () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_subscription'));

		$subscription = $this->membrr->GetSubscription($this->EE->input->get('id'));

		// load payments
		$payments = $this->membrr->GetPayments(0, 100, array('subscription_id' => $subscription['id']));

		// calculate total money received
		$total_amount = 0;
		if (is_array($payments)) {
			foreach ($payments as $key => $payment) {
				$total_amount = $total_amount + $payment['amount'];
				if ($payment['amount'] != '0.00') {
					$payments[$key]['refund_text'] = ($payment['refunded'] == '0') ? '<a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('subscription',array('id' => $this->EE->input->get('id')))))))) . '">' . $this->EE->lang->line('membrr_refund') . '</a>' : 'refunded';
				}
				else {
					$payments[$key]['refund_text'] = '';
				}
			}
			reset($payments);
		}

		$subscription['total_amount'] = $total_amount;

		if ($subscription['active'] == '1') {
			$status = $this->EE->lang->line('membrr_active');
			$status .= ' | <a href="' . $this->cp_url('cancel_subscription',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('membrr_cancel') . '</a>';

			if (!empty($subscription['card_last_four'])) {
				$status .= ' | <a href="' . $this->cp_url('update_cc',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('membrr_update_cc') . '</a>';
			}
		}
		elseif ($subscription['expired'] == '1') {
			$status = $this->EE->lang->line('membrr_expired');
		}
		elseif ($subscription['renewed'] == TRUE) {
			$status = 'Renewed with <a href=" ' . $this->cp_url('subscription',array('id' => $subscription['renewed_recurring_id'])) . '">subscription #' . $subscription['renewed_recurring_id'] . '</a>';
		}
		elseif ($subscription['cancelled'] == '1') {
			$status = $this->EE->lang->line('membrr_cancelled');
		}
		else {
			$status = 'Unknown';
		}

		if (empty($subscription['renewed'])) {
			$status .= ' | <a href="' . $this->cp_url('renew_subscription',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('membrr_renew') . '</a>';
		}

		$subscription['status'] = $status;

		$subscription['plan_link'] = $this->cp_url('edit_plan',array('id' => $subscription['plan_id']));

		$subscription['member_link'] = $this->member_link($subscription['member_id']);

		// should we have an end now button?
		if ($subscription['active'] == '0' and strtotime($subscription['end_date']) > time()) {
			$end_now = ' (<a href="' . $this->cp_url('end_now',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('membrr_end_now') . '</a>)';
		}
		else{
			$end_now = FALSE;
		}

		// should we have an expiry mod?
		if ($subscription['end_date'] != FALSE) {
			$change_expiry = ' (<a href="' . $this->cp_url('expiry',array('id' => $subscription['id'])) . '">modify expiration date</a>)';
		}
		else {
			$change_expiry = FALSE;
		}

		// get billing address
		$address = $this->membrr->GetAddress($subscription['member_id']);

		$vars = array();
		$vars['subscription'] = $subscription;
		$vars['payments'] = $payments;
		$vars['address'] = $address;
		$vars['config'] = $this->config;
		$vars['end_now'] = $end_now;
		$vars['change_expiry'] = $change_expiry;

		return $this->EE->load->view('subscription',$vars,TRUE);
	}

	function renew_subscription () {
		$sub = $this->membrr->GetSubscription($this->EE->input->get('id'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('add_subscription_2', array('renew' => $sub['id'], 'plan_id' => $sub['plan_id'], 'member_id' => $sub['member_id']))));
		die();
	}

	function end_now () {
		$this->membrr->EndNow($this->EE->input->get('id'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $this->EE->input->get('id')))));
		die();
	}

	function expiry () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_change_expiry_title'));

		$recurring_id = $this->EE->input->get('id');
		$subscription = $this->membrr->GetSubscription($recurring_id);

		// end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        $subscription['end_date'] = array(
        								'day' => date('d', strtotime($subscription['end_date'])),
        								'month' => date('m', strtotime($subscription['end_date'])),
        								'year' => date('Y', strtotime($subscription['end_date']))
        							);

		// errors
		$errors = ($this->EE->session->flashdata('errors')) ? $this->EE->session->flashdata('errors') : FALSE;

		$vars = array();
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['form_action'] = $this->form_url('post_expiry');
		$vars['subscription'] = $subscription;
		$vars['errors'] = $errors;

		return $this->EE->load->view('expiry',$vars, TRUE);
	}

	function post_expiry () {
		// setup validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules('subscription_id','Subscription ID','required');

		// get subscription
		$subscription = $this->membrr->GetSubscription($this->EE->input->post('subscription_id'));

		if ($this->EE->form_validation->run() !== FALSE) {
			$new_expiry = $this->EE->input->post('end_date_year') . '-' . $this->EE->input->post('end_date_month') . '-' . $this->EE->input->post('end_date_day');

			$this->membrr->UpdateExpiryDate($subscription['id'], $new_expiry);

			// record payment
			if ($this->EE->input->post('record_payment') == '1') {
				// connect to OG
				$this->server->SetMethod('RecordSubscriptionPayment');
				$this->server->Param('recurring_id', $subscription['id']);
				$this->server->Param('amount', $this->EE->input->post('payment_amount'));

				$response = $this->server->Process();

				if (!isset($response['error'])) {
					$this->membrr->RecordPayment($subscription['id'], $response['charge_id'], $this->EE->input->post('payment_amount'));

					$this->EE->db->update('exp_membrr_subscriptions', array('next_charge_date' => $response['next_charge'], 'end_date' => $response['end_date']), array('recurring_id' => $subscription['id']));
				}
			}

			// success!
			$this->EE->session->set_flashdata('message_success', 'You have successfully updated this subscription.');

			// redirect to URL
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $subscription['id']))));

			die();
			return TRUE;
		}
		else {
			$this->EE->session->set_flashdata('errors',validation_errors());
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('expiry', array('id' => $subscription['id']))));

			die();
			return FALSE;
		}
	}

	function update_cc () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_update_cc_title'));

		$recurring_id = $this->EE->input->get('id');
		$subscription = $this->membrr->GetSubscription($recurring_id);

		// get select user
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($subscription['member_id']);
	    $member = $member->row_array();

	    // end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        // cc expiry date
        $expiry_date_years = array();

        for ($i = date('Y'); $i <= (date('Y') + 10); $i++) {
        	$expiry_date_years[$i] = $i;
        }

        // get address if available
        $address = $this->membrr->GetAddress($member['member_id']);

        // get regions
        $regions = $this->membrr->GetRegions();

		$region_options = array();
		$region_options[] = '';
		foreach ($regions as $code => $region) {
			$region_options[$code] = $region;
		}

        // get countries
        $countries = $this->membrr->GetCountries();

		$country_options = array();
		$country_options[] = '';
		foreach ($countries as $country_code => $country) {
			$country_options[$country_code] = $country;
		}

		// plans
		$plans = $this->membrr->GetPlans(array('active' => '1'));

		$plan_options = array();
		if (is_array($plans)) {
			foreach ($plans as $plan) {
				$plan_options[$plan['id']] = $plan['name'];
			}
		}

		// errors
		$errors = ($this->EE->session->flashdata('errors')) ? $this->EE->session->flashdata('errors') : FALSE;

		$vars = array();
		$vars['config'] = $this->config;
		$vars['member'] = $member;
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['expiry_date_years'] = $expiry_date_years;
		$vars['form_action'] = $this->form_url('post_update_cc');
		$vars['regions'] = $region_options;
		$vars['countries'] = $country_options;
		$vars['address'] = $address;
		$vars['subscription'] = $subscription;
		$vars['errors'] = $errors;
		$vars['plans'] = $plan_options;

		return $this->EE->load->view('update_cc',$vars, TRUE);
	}

	function post_update_cc () {
		// setup validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules('subscription_id','Subscription ID','required');

		$this->EE->form_validation->set_rules('first_name','lang:membrr_order_form_customer_first_name','trim|required');
		$this->EE->form_validation->set_rules('last_name','lang:membrr_order_form_customer_last_name','trim|required');
		$this->EE->form_validation->set_rules('address','lang:membrr_order_form_customer_address','trim|required');
		$this->EE->form_validation->set_rules('city','lang:membrr_order_form_customer_city','trim|required');
		$this->EE->form_validation->set_rules('country','lang:membrr_order_form_customer_country','trim|required');
		$this->EE->form_validation->set_rules('postal_code','lang:membrr_order_form_customer_postal_code','trim|required');

		$this->EE->form_validation->set_rules('cc_number','Credit Card Number','trim|required');
		$this->EE->form_validation->set_rules('cc_name','Credit Card Name','trim|required');

		// get subscription
		$subscription = $this->membrr->GetSubscription($this->EE->input->post('subscription_id'));


		if ($this->EE->form_validation->run() !== FALSE) {
			// update address
			$this->membrr->UpdateAddress($subscription['member_id'],$this->EE->input->post('first_name'),$this->EE->input->post('last_name'),$this->EE->input->post('address'),$this->EE->input->post('address_2'),$this->EE->input->post('city'),$this->EE->input->post('region'),$this->EE->input->post('region_other'),$this->EE->input->post('country'),$this->EE->input->post('postal_code'),$this->EE->input->post('company'),$this->EE->input->post('phone'),$this->EE->input->post('company'),$this->EE->input->post('phone'));

			// process subscription update
			$member_id = $subscription['member_id'];

			$credit_card = array(
								'number' => $this->EE->input->post('cc_number'),
								'name' => $this->EE->input->post('cc_name'),
								'expiry_month' => $this->EE->input->post('cc_expiry_month'),
								'expiry_year' => $this->EE->input->post('cc_expiry_year'),
								'security_code' => $this->EE->input->post('cc_cvv2')
							);

			$plan_id = $this->EE->input->post('plan_id');

			$response = $this->membrr->UpdateCC($subscription['id'], $credit_card, $plan_id);

			if (!is_array($response) or isset($response['error'])) {
				$this->EE->session->set_flashdata('errors',$this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')');
			}
			elseif ($response['response_code'] != '104') {
				$this->EE->session->set_flashdata('errors',$this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')');
			}
			else {
				// success!
				$this->EE->session->set_flashdata('message_success', 'You have successfully updated this subscription.');

				// redirect to URL
				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $response['recurring_id']))));
				die();
				return TRUE;
			}

			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('update_cc', array('id' => $subscription['id']))));
			die();
		}
		else {
			$this->EE->session->set_flashdata('errors',validation_errors());
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('update_cc', array('id' => $subscription['id']))));
			die();
		}
	}

	function cancel_subscription () {
		$this->membrr->CancelSubscription($this->EE->input->get('id'));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_cancelled_subscription'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription',array('id' => $this->EE->input->get('id')))));
		die();

		return true;
	}

	function add_subscription () {
		$this->set_page_title($this->EE->lang->line('membrr_create_subscription'));

		// shall we pass this off?
		if (!$this->EE->input->post('member_search') and $this->EE->input->post('member_id') and $this->EE->input->post('plan_id')) {
			return $this->add_subscription_2();
		}

		// get active plans
		$plans = $this->membrr->GetPlans(array('active' => '1'));

		$plan_options = array();
		if (is_array($plans)) {
			foreach ($plans as $plan) {
				$plan_options[$plan['id']] = $plan['name'];
			}
		}

		// get users
		if ($this->EE->input->post('member_search')) {
			$searching = TRUE;

			$this->EE->load->model('member_model');
		    $members_db = $this->EE->member_model->get_members('','250','',$this->EE->input->post('member_search'),array('screen_name' => 'ASC'));

		    $members = array();

		    if (is_object($members_db) and $members_db->num_rows() > 0) {
			    foreach ($members_db->result_array() as $member) {
			    	$members[] = $member;
			    }
			}
	    }
	    else {
	    	$searching = FALSE;

	    	$members = array();
	    }

		$vars = array();
		$vars['plans'] = $plan_options;
		$vars['searching'] = $searching;
		$vars['members'] = $members;
		$vars['selected_plan'] = ($this->EE->input->post('plan_id')) ? $this->EE->input->post('plan_id') : FALSE;
		$vars['form_action'] = $this->form_url('add_subscription');

		return $this->EE->load->view('add_subscription',$vars, TRUE);
	}

	function add_subscription_2 () {
		// do we have the required info to be here?
		if ($this->EE->input->get_post('member_id') == '' or $this->EE->input->get_post('plan_id') == '0' or $this->EE->input->get_post('plan_id') == '') {
			return $this->add_subscription();
		}

		if ($this->EE->input->get_post('renew')) {
			$this->set_page_title($this->EE->lang->line('membrr_renew_title'));
		}
		else {
			$this->set_page_title($this->EE->lang->line('membrr_create_subscription'));
		}

		$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

		if ($this->EE->input->post('process_transaction') == '1') {
			// setup validation
			$this->EE->form_validation->set_rules('plan_id','lang:membrr_order_form_select_plan','trim|required');
			$this->EE->form_validation->set_rules('member_id','lang:membrr_user','trim|required');
			$this->EE->form_validation->set_rules('recurring_rate','lang:membrr_custom_recurring_rate','');
			$this->EE->form_validation->set_rules('first_charge_rate','lang:membrr_custom_first_charge_rate','');

			// if not free, we require CC info and billing address
			/*
			* no need to require this for everyone
			if ($this->EE->input->post('free') != '1') {
				$this->EE->form_validation->set_rules('first_name','lang:membrr_order_form_customer_first_name','trim|required');
				$this->EE->form_validation->set_rules('last_name','lang:membrr_order_form_customer_last_name','trim|required');
				$this->EE->form_validation->set_rules('address','lang:membrr_order_form_customer_address','trim|required');
				$this->EE->form_validation->set_rules('city','lang:membrr_order_form_customer_city','trim|required');
				$this->EE->form_validation->set_rules('country','lang:membrr_order_form_customer_country','trim|required');
				$this->EE->form_validation->set_rules('postal_code','lang:membrr_order_form_customer_postal_code','trim|required');
			}
			*/

			// if not lasting forever, we require an end date
			if ($this->EE->input->post('never_ends') != '1') {
				$this->EE->form_validation->set_rules('end_date_day','lang:membrr_end_date','trim|required');
				$this->EE->form_validation->set_rules('end_date_month','lang:membrr_end_date','trim|required');
				$this->EE->form_validation->set_rules('end_date_year','lang:membrr_end_date','trim|required');
			}

			if ($this->EE->form_validation->run() != FALSE) {
				// update address
				$this->membrr->UpdateAddress($this->EE->input->post('member_id'),$this->EE->input->post('first_name'),$this->EE->input->post('last_name'),$this->EE->input->post('address'),$this->EE->input->post('address_2'),$this->EE->input->post('city'),$this->EE->input->post('region'),$this->EE->input->post('region_other'),$this->EE->input->post('country'),$this->EE->input->post('postal_code'),$this->EE->input->post('company'),$this->EE->input->post('phone'));

				// process subscription
				// prep arrays to send to Membrr_EE class
				$plan_id = $this->EE->input->post('plan_id');
				$member_id = $this->EE->input->post('member_id');

				if ($this->EE->input->post('free') != '1') {
					$credit_card = array(
										'number' => $this->EE->input->post('cc_number'),
										'name' => $this->EE->input->post('cc_name'),
										'expiry_month' => $this->EE->input->post('cc_expiry_month'),
										'expiry_year' => $this->EE->input->post('cc_expiry_year'),
										'security_code' => $this->EE->input->post('cc_cvv2')
									);
				}
				else {
					// use dummy CC info for free subscription
					$credit_card = array(
										'number' => '0000000000000000',
										'name' => $this->EE->input->post('first_name') . ' ' . $this->EE->input->post('last_name'),
										'expiry_month' => date('m'), // this month
										'expiry_year' => (1 + date('Y')), // 1 year in future
										'security_code' => '000' // free
									);
				}

				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->input->post('member_id'));
			    $member = $member->row_array();

				$customer = array(
								'first_name' => $this->EE->input->post('first_name'),
								'last_name' => $this->EE->input->post('last_name'),
								'address' => $this->EE->input->post('address'),
								'address_2' => $this->EE->input->post('address_2'),
								'city' => $this->EE->input->post('city'),
								'region' => ($this->EE->input->post('region_other') != '') ? $this->EE->input->post('region_other') : $this->EE->input->post('region'),
								'country' => $this->EE->input->post('country'),
								'postal_code' => $this->EE->input->post('postal_code'),
								'email' => $member['email'],
								'company' => $this->EE->input->post('company'),
								'phone' => $this->EE->input->post('phone')
							);

				// create end date if necessary
				if ($this->EE->input->post('never_ends') != '1') {
					$end_date = $this->EE->input->post('end_date_year') . '-' . str_pad($this->EE->input->post('end_date_month'), 2, "0", STR_PAD_LEFT) . '-' . str_pad($this->EE->input->post('end_date_day'), 2, "0", STR_PAD_LEFT);
				}
				else {
					$end_date = FALSE;
				}

				// is it free?
				if ($this->EE->input->post('free') == '1') {
					$first_charge_rate = '0.00';
					$recurring_rate = '0.00';
				}
				else {
					$first_charge_rate = money_format("%!^i",$this->EE->input->post('first_charge_rate'));
					$recurring_rate = money_format("%!^i",$this->EE->input->post('recurring_rate'));
				}

				$coupon = ($this->EE->input->post('coupon')) ? $this->EE->input->post('coupon') : FALSE;

				$gateway_id = $this->EE->input->post('gateway');

				// are we renewing?
				$renew = ($this->EE->input->post('renew')) ? $this->EE->input->post('renew') : FALSE;

				$response = $this->membrr->Subscribe($plan_id, $member_id, $credit_card, $customer, $end_date, $first_charge_rate, $recurring_rate, FALSE, FALSE, $gateway_id, $renew, $coupon);

				if (!is_array($response) or isset($response['error'])) {
					$failed_transaction = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] == '2') {
					$failed_transaction = $this->EE->lang->line('membrr_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_created_subscription'));

					// redirect to URL
					$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $response['recurring_id']))));
					die();
					return TRUE;
				}
			}
		}

		// get selected plan
		$plan = $this->membrr->GetPlan($this->EE->input->get_post('plan_id'));

		// get select user
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($this->EE->input->get_post('member_id'));
	    $member = $member->row_array();

	    // end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        // cc expiry date
        $expiry_date_years = array();

        for ($i = date('Y'); $i <= (date('Y') + 10); $i++) {
        	$expiry_date_years[$i] = $i;
        }

        // get address if available
        $address = $this->membrr->GetAddress($member['member_id']);

        // get regions
        $regions = $this->membrr->GetRegions();

		$region_options = array();
		$region_options[] = '';
		foreach ($regions as $code => $region) {
			$region_options[$code] = $region;
		}

        // get countries
        $countries = $this->membrr->GetCountries();

		$country_options = array();
		$country_options[] = '';
		foreach ($countries as $country_code => $country) {
			$country_options[$country_code] = $country;
		}

		// get gateways
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_options = array();
		$gateway_options[''] = 'Default Gateway';

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];

			$gateway_options[$gateway['id']] = $gateway['gateway'];
		}

		// add a little JavaScript
	    $this->EE->cp->add_to_head("<script type=\"text/javascript\">
        								$(document).ready(function() {
        									$('select[name=\"end_date_day\"], select[name=\"end_date_month\"], select[name=\"end_date_year\"]').focus(function () {
        										$('input[name=\"never_ends\"]').attr('checked',false);
        									});
        								});
        							</script>");

		$vars = array();
		$vars['plan'] = $plan;
		$vars['config'] = $this->config;
		$vars['member'] = $member;
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['expiry_date_years'] = $expiry_date_years;
		$vars['form_action'] = $this->form_url('add_subscription_2');
		$vars['regions'] = $region_options;
		$vars['countries'] = $country_options;
		$vars['address'] = $address;
		$vars['failed_transaction'] = (isset($failed_transaction)) ? $failed_transaction : FALSE;
		$vars['gateways'] = $gateway_options;
		$vars['renew'] = ($this->EE->input->get_post('renew')) ? $this->EE->input->get_post('renew') : '';

		return $this->EE->load->view('add_subscription_2',$vars, TRUE);
	}

	function settings () {
		$this->set_page_title($this->EE->lang->line('membrr_settings'));

		$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

		// handle possible submission
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$this->EE->form_validation->set_rules('api_url','lang:membrr_api_url','trim|required');
			$this->EE->form_validation->set_rules('api_id','lang:membrr_api_id','trim|required');
			$this->EE->form_validation->set_rules('secret_key','lang:membrr_secret_key','trim|required');

			if ($this->EE->form_validation->run() != FALSE) {
				$post_url = $this->EE->input->post('api_url');
				$post_id = $this->EE->input->post('api_id');
				$post_key = $this->EE->input->post('secret_key');
				$post_currency_symbol = $this->EE->input->post('currency_symbol');
				$post_gateway = $this->EE->input->post('gateway');
				$update_email = ($this->EE->input->post('update_email')) ? '1' : '0';
				$use_captcha = ($this->EE->input->post('use_captcha')) ? '1' : '0';

				$post_url = rtrim($post_url, '/');

				if (substr($post_url,3,-3) == 'api') {
					$post_url = substr_replace($post_url,'',-4,4);
				}

				// validate API connection
				if (!$this->validate_api($post_url, $post_id, $post_key)) {
					$failed_to_connect = $this->EE->lang->line('membrr_config_failed');
				}
				else {
					$is_first_config = (!$this->config) ? TRUE : FALSE;

					if (!$is_first_config) {
						$update_vars = array(
								         'api_url' => $post_url,
								         'api_id' => $post_id,
								         'secret_key' => $post_key,
								         'currency_symbol' => $post_currency_symbol,
								         'gateway' => $post_gateway,
								         'update_email' => $update_email,
								         'use_captcha' => $use_captcha
										);

						$this->EE->db->update('exp_membrr_config',$update_vars);
				 	}
				 	else {
				 		$insert_vars = array(
				 						 'api_url' => $post_url,
								         'api_id' => $post_id,
								         'secret_key' => $post_key,
								         'currency_symbol' => $post_currency_symbol,
								         'gateway' => '0',
								         'update_email' => $update_email,
								         'use_captcha' => $use_captcha
				 					);

				 		$this->EE->db->insert('exp_membrr_config', $insert_vars);
				 	}

				 	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_config_updated'));

				 	$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
				 	die();
				 }
			}
		}

		// is this the first config?
		$is_first_config = (!$this->config) ? TRUE : FALSE;

		// get values
		if (!$is_first_config) {
			$api_url = ($this->EE->input->post('api_url')) ? $this->EE->input->post('api_url') : $this->config['api_url'];
			$api_id = ($this->EE->input->post('api_id')) ? $this->EE->input->post('api_id') : $this->config['api_id'];
			$secret_key = ($this->EE->input->post('secret_key')) ? $this->EE->input->post('secret_key') : $this->config['secret_key'];
			$currency_symbol = ($this->EE->input->post('currency_symbol')) ? $this->EE->input->post('currency_symbol') : $this->config['currency_symbol'];
			$default_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : $this->config['gateway'];
			$update_email = ($this->EE->input->post('update_email')) ? TRUE : $this->config['update_email'];
			$use_captcha = ($this->EE->input->post('use_captcha')) ? TRUE : $this->config['use_captcha'];
		}
		else {
			$api_url = ($this->EE->input->post('api_url')) ? $this->EE->input->post('api_url') : 'https://www.yourdomain.com/opengateway';
			$api_id = ($this->EE->input->post('api_id')) ? $this->EE->input->post('api_id') : '';
			$secret_key = ($this->EE->input->post('secret_key')) ? $this->EE->input->post('secret_key') : '';
			$currency_symbol = ($this->EE->input->post('currency_symbol')) ? $this->EE->input->post('currency_symbol') : '$';
			$default_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : '';
			$update_email = ($this->EE->input->post('update_email')) ? TRUE : FALSE;
			$use_captcha = ($this->EE->input->post('use_captcha')) ? TRUE : FALSE;
		}

		// load possible gateways
		if (!$is_first_config) {
   			$this->server->SetMethod('GetGateways');
			$response = $this->server->Process();

			// we may get one gateway or many
			$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

			// hold our list of available options
			$gateway_options = array();
			$gateway_options[''] = 'Default Gateway';

			if (is_array($gateways) and isset($gateways['gateway'][0])) {
				foreach ($gateways['gateway'] as $gateway) {
					$gateway_options[$gateway['id']] = $gateway['gateway'];
				}
			}
			elseif (is_array($gateways)) {
				$gateway = $gateways['gateway'];

				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}

		// countries
		$countries = $this->EE->db->where('available','1')->get('exp_membrr_countries')->num_rows();
		$countries_text = '<a href="' . $this->cp_url('countries') . '">' . $countries . ' countries</a>';

		// load view
		$vars = array();
		$vars['form_action'] = $this->form_url('settings');
		$vars['first_config'] = ($is_first_config == true) ? true : false;
		$vars['api_url'] = $api_url;
		$vars['api_id'] = $api_id;
		$vars['secret_key'] = $secret_key;
		$vars['currency_symbol'] = $currency_symbol;
		$vars['gateway'] = $default_gateway;
		$vars['gateways'] = (isset($gateway_options)) ? $gateway_options : FALSE;
		$vars['countries_text'] = $countries_text;
		$vars['failed_to_connect'] = isset($failed_to_connect) ? $failed_to_connect : FALSE;
		$vars['update_email'] = $update_email;
		$vars['use_captcha'] = $use_captcha;

		return $this->EE->load->view('settings',$vars,TRUE);
	}

	function countries () {
		$this->set_page_title($this->EE->lang->line('membrr_available_countries'));

		 $this->EE->cp->add_to_head('<script type="text/javascript">
        								function uncheck_countries () {
        									$(\'input.countries\').attr(\'checked\',false);
        								}

        								function check_countries () {
        									$(\'input.countries\').attr(\'checked\',\'checked\');
        								}
        							</script>');

		$countries = $this->EE->db->order_by('name')->get('exp_membrr_countries');

		$vars = array();
		$vars['countries'] = $countries;
		$vars['form_action'] = $this->form_url('set_countries');

		return $this->EE->load->view('countries', $vars, TRUE);
	}

	function set_countries () {
		$countries = $this->EE->db->get('exp_membrr_countries');

		foreach ($countries->result_array() as $country) {
			if ($country['available'] == '1' and !isset($_POST['country_' . $country['country_id']])) {
				$this->EE->db->update('exp_membrr_countries', array('available' => '0'), array('country_id' => $country['country_id']));
			}
			elseif ($country['available'] == '0' and isset($_POST['country_' . $country['country_id']]) and $_POST['country_' . $country['country_id']] == '1') {
				$this->EE->db->update('exp_membrr_countries', array('available' => '1'), array('country_id' => $country['country_id']));
			}
		}

		return $this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
	}

	function validate_api ($api_url, $api_id, $secret_key) {
		// does URL exist?
		$headers = @get_headers($api_url);
		$file = @file_get_contents($api_url);
		if ((ini_get('allow_url_fopen') and $file == FALSE) or (!empty($headers) and (!isset($headers[0]) or strstr($headers[0],'404') or strstr($headers[0],'403')))) {
			return FALSE;
		}
		else {
			include_once(dirname(__FILE__) . '/opengateway.php');
			$server = new OpenGateway;

			$server->Authenticate($api_id, $secret_key, $api_url . '/api');
			$server->SetMethod('GetCharges');
			$response = $server->Process();

			if (!isset($response['error'])) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}

		return FALSE;
	}

	function plans () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_plans'));

		$plans = $this->membrr->GetPlans();

		if (is_array($plans)) {
			foreach ($plans as $key => $plan) {
				$plans[$key]['options'] = '<a href="' . $this->cp_url('edit_plan',array('id' => $plan['id'])) . '">' . $this->EE->lang->line('membrr_edit') . '</a> | <a class="confirm" href="' . $this->cp_url('delete_plan',array('id' => $plan['id'])) . '">' . $this->EE->lang->line('membrr_delete') . '</a>';
			}

			reset($plans);
		}

		// load view
		$vars = array();
		$vars['plans'] = $plans;
		$vars['config'] = $this->config;
		$vars['form_action'] = $this->form_url('import_plan');

		return $this->EE->load->view('plans',$vars,TRUE);
	}

	function edit_plan () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_edit_plan'));

		$this->EE->load->library('form_validation');

		// check for a form submission
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$this->EE->form_validation->set_rules('plan_name','lang:membrr_display_name','trim|required');
			$this->EE->form_validation->set_rules('initial_charge','lang:membrr_initial_charge','trim|required');
			$this->EE->form_validation->set_rules('plan_description','lang:membrr_description','trim|required');
			$this->EE->form_validation->set_rules('redirect_url','lang:membrr_redirect_url','trim|empty');

			if ($this->EE->form_validation->run() != FALSE) {
				$update_vars = array(
									'plan_name' => $this->EE->input->post('plan_name'),
									'plan_description' => $this->EE->input->post('plan_description'),
									'plan_initial_charge' => $this->EE->input->post('initial_charge'),
									'plan_gateway' => $this->EE->input->post('gateway'),
									'plan_redirect_url' => $this->EE->input->post('redirect_url'),
									'plan_renewal_extend_from_end' => ($this->EE->input->post('renewal_extend_from_end')) ? '1' : '0',
									'plan_member_group' => $this->EE->input->post('member_group'),
									'plan_member_group_expire' => $this->EE->input->post('member_group_expire'),
									'plan_active' => $this->EE->input->post('for_sale')
								);
				$this->EE->db->update('exp_membrr_plans',$update_vars, array('plan_id' => $this->EE->input->get('id')));

				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_edited_plan'));

				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('plans')));
				die();
			}
		}

		// we've loaded the plan
		$plan = $this->membrr->GetPlan($this->EE->input->get('id'));

		$this->EE->load->model('member_model');
        $groups = $this->EE->member_model->get_member_groups();

        $member_groups = array();
        $member_groups[''] = 'No Change';

        foreach ($groups->result_array() as $row) {
       		$member_groups[$row['group_id']] = $row['group_title'];
        }

        // default values
        $plan_name = ($this->EE->input->post('plan_name')) ? $this->EE->input->post('plan_name') : $plan['name'];
		$plan_description = ($this->EE->input->post('plan_description')) ? $this->EE->input->post('plan_description') : $plan['description'];
		$plan_initial_charge = ($this->EE->input->post('initial_charge')) ? $this->EE->input->post('initial_charge') : $plan['initial_charge'];
		$new_member_group = ($this->EE->input->post('new_member_group')) ? $this->EE->input->post('new_member_group') : $plan['member_group'];
		$new_member_group_expire = ($this->EE->input->post('new_member_group_expire')) ? $this->EE->input->post('new_member_group_expire') : $plan['member_group_expire'];
		$redirect_url = ($this->EE->input->post('redirect_url')) ? $this->EE->input->post('redirect_url') : $plan['redirect_url'];
		$renewal_extend_from_end = (($this->EE->input->post('plan_name') and !$this->EE->input->post('renewal_extend_from_end')) or empty($plan['renewal_extend_from_end'])) ? FALSE : TRUE;
		$for_sale = ($this->EE->input->post('for_sale')) ? $this->EE->input->post('for_sale') : $plan['for_sale'];
		$selected_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : $plan['gateway'];

		// load possible gateways
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_options = array();
		$gateway_options[''] = 'Default Gateway';

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];

			$gateway_options[$gateway['id']] = $gateway['gateway'];
		}

		// load view
		$vars = array();
		$vars['plan'] = $plan;
		$vars['member_groups'] = $member_groups;
		$vars['form_action'] = $this->form_url('edit_plan',array('id' => $plan['id']));
		$vars['config'] = $this->config;
		$vars['plan_name'] = $plan_name;
		$vars['plan_initial_charge'] = $plan_initial_charge;
		$vars['plan_description'] = $plan_description;
		$vars['new_member_group'] = $new_member_group;
		$vars['new_member_group_expire'] = $new_member_group_expire;
		$vars['redirect_url'] = $redirect_url;
		$vars['renewal_extend_from_end'] = $renewal_extend_from_end;
		$vars['for_sale'] = $for_sale;
		$vars['gateways'] = $gateway_options;
		$vars['selected_gateway'] = $selected_gateway;

		return $this->EE->load->view('edit_plan',$vars, TRUE);
	}

	function delete_plan () {
		$this->membrr->DeletePlan($this->EE->input->get('id'));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_deleted_plan'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('plans')));
		die();
	}

	function import_plan ($no_plan_id = FALSE) {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_import_plan'));

		$this->server->SetMethod('GetPlans');
		$response = $this->server->Process();

		$plan_options = array();

		if (!isset($response['results']) or $response['results'] == 0) {
			$no_plans = TRUE;
		}
		else {
			// we have found some plans
			if (isset($response['plans']['plan'][0])) {
				$result_plans = $response['plans']['plan'];
			}
			else {
				$result_plans = $response['plans'];
			}


			foreach ($result_plans as $plan) {
	        	$plan_options[$plan['id']] = $plan['name'];
	        }
		}

		// load view
		$vars = array();
		$vars['form_action'] = $this->form_url('import_plan_2');
		$vars['plans'] = $plan_options;
		$vars['no_plans'] = (isset($no_plans)) ? TRUE : FALSE;
		$vars['no_plan_id'] = $no_plan_id;

		return $this->EE->load->view('import_plan',$vars,TRUE);
	}

	function import_plan_2 () {
		// page title
		$this->set_page_title($this->EE->lang->line('membrr_import_plan'));

		$this->EE->load->library('form_validation');

		// must have a plan ID
		if ($this->EE->input->post('plan_id') == '') {
			return $this->import_plan(TRUE);
		}

		// check for a form submission
		if ($this->EE->input->post('api_plan_id')) {
			$this->EE->form_validation->set_rules('plan_name','lang:membrr_display_name','trim|required');
			$this->EE->form_validation->set_rules('plan_description','lang:membrr_description','trim|required');
			$this->EE->form_validation->set_rules('redirect_url','lang:membrr_redirect_url','trim|empty');

			if ($this->EE->form_validation->run() != FALSE) {
				// set notification_url on Membrr account to this URL
				$this->server->SetMethod('UpdatePlan');
				$this->server->Param('plan_id',$this->EE->input->post('api_plan_id'));
				$this->server->Param('notification_url', $this->get_notification_url());
				$response = $this->server->Process();

				$insert_vars = array(
									'api_plan_id' => $this->EE->input->post('api_plan_id'),
									'plan_name' => $this->EE->input->post('plan_name'),
									'plan_description' => $this->EE->input->post('plan_description'),
									'plan_free_trial' => $this->EE->input->post('free_trial'),
									'plan_occurrences' => $this->EE->input->post('occurrences'),
									'plan_price' => $this->EE->input->post('amount'),
									'plan_initial_charge' => $this->EE->input->post('initial_charge'),
									'plan_gateway' => $this->EE->input->post('gateway'),
									'plan_interval' => $this->EE->input->post('interval'),
									'plan_import_date' => date('Y-m-d H:i:s'),
									'plan_redirect_url' => $this->EE->input->post('redirect_url'),
									'plan_renewal_extend_from_end' => ($this->EE->input->post('renewal_extend_from_end')) ? '1' : '0',
									'plan_member_group' => $this->EE->input->post('new_member_group'),
									'plan_member_group_expire' => $this->EE->input->post('new_member_group_expire'),
									'plan_active' => '1',
									'plan_deleted' => '0'
								);
				$this->EE->db->insert('exp_membrr_plans',$insert_vars);

				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('membrr_imported_plan'));

				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('plans')));
				die();
			}
		}

		$this->server->SetMethod('GetPlan');
		$this->server->Param('plan_id',$this->EE->input->post('plan_id'));

		$response = $this->server->Process();

		if (isset($response['error'])) {
			return import_plan(TRUE);
		}

		// we've loaded the plan
		$plan = array(
							'plan_id' => $response['plan']['id'],
							'name' => $response['plan']['name'],
					   		'api_plan_id' => $response['plan']['id'],
					   		'interval' => $response['plan']['interval'],
					   		'amount' => $response['plan']['amount'],
					   		'free_trial' => $response['plan']['free_trial'],
					   		'occurrences' => $response['plan']['occurrences'],
					   		'initial_charge' => (empty($response['plan']['free_trial'])) ? $response['plan']['amount'] : '0.00'
						);

		$this->EE->load->model('member_model');
        $groups = $this->EE->member_model->get_member_groups();

        $member_groups = array();
        $member_groups[''] = 'No Change';

        foreach ($groups->result_array() as $row) {
       		$member_groups[$row['group_id']] = $row['group_title'];
        }

        // default values
        $plan_name = ($this->EE->input->post('plan_name')) ? $this->EE->input->post('plan_name') : $plan['name'];
        $plan_initial_charge = ($this->EE->input->post('initial_charge')) ? $this->EE->input->post('initial_charge') : $plan['initial_charge'];
		$plan_description = ($this->EE->input->post('plan_description')) ? $this->EE->input->post('plan_description') : '';
		$new_member_group = ($this->EE->input->post('new_member_group')) ? $this->EE->input->post('new_member_group') : '';
		$new_member_group_expire = ($this->EE->input->post('new_member_group_expire')) ? $this->EE->input->post('new_member_group_expire') : '';
		$redirect_url = ($this->EE->input->post('redirect_url')) ? $this->EE->input->post('redirect_url') : $this->EE->functions->fetch_site_index();
		// only don't check if this form has been submitted (i.e., we have a plan name) and this box was left unchecked
		$renewal_extend_from_end = ($this->EE->input->post('plan_name') and !$this->EE->input->post('renewal_extend_from_end')) ? FALSE : TRUE;
		$selected_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : '';

		// load possible gateways
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_options = array();
		$gateway_options[''] = 'Default Gateway';

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];

			$gateway_options[$gateway['id']] = $gateway['gateway'];
		}

		// load view
		$vars = array();
		$vars['plan'] = $plan;
		$vars['member_groups'] = $member_groups;
		$vars['form_action'] = $this->form_url('import_plan_2');
		$vars['config'] = $this->config;
		$vars['plan_name'] = $plan_name;
		$vars['plan_initial_charge'] = $plan_initial_charge;
		$vars['plan_description'] = $plan_description;
		$vars['new_member_group'] = $new_member_group;
		$vars['new_member_group_expire'] = $new_member_group_expire;
		$vars['redirect_url'] = $redirect_url;
		$vars['renewal_extend_from_end'] = $renewal_extend_from_end;
		$vars['gateways'] = $gateway_options;
		$vars['selected_gateway'] = $selected_gateway;

		return $this->EE->load->view('import_plan_2',$vars, TRUE);
	}

	function pagination_config($method, $total_rows, $parameters = array())
	{
		// Pass the relevant data to the paginate class
		$config['base_url'] = $this->cp_url($method, $parameters);
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->per_page;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

		return $config;
	}

	function get_notification_url () {
		$action_id = $this->EE->cp->fetch_action_id('Membrr', 'post_notify');

		$url = $this->EE->functions->create_url('?ACT=' . $action_id, 0);

		// fix an issue that pops up with force_query_strings ON
		$url = str_replace('/?/?','/?',$url);

		return $url;
	}
}

if (!function_exists('htmlspecialchars_decode')) {
	function htmlspecialchars_decode($string,$style = ENT_COMPAT)
	{
	    $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS,$style));
	    if($style === ENT_QUOTES){ $translation['&#039;'] = '\''; }
	    return strtr($string,$translation);
	}
}