<? if ($errors) { ?>
	<div class="membrr_error"><?=$errors;?></div>
<? } ?>

<?=form_open($form_action)?>
<?=form_hidden('subscription_id', $subscription['id']);?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_create_subscription'), 'colspan' => '2')
						);
		
$this->table->add_row(
		lang('membrr_user'),
		$member['screen_name'] . ' (' . $member['email'] . ')' . form_hidden('member_id',$member['member_id'])
	);
	
$this->table->add_row(
			array('data' => 'Subscription Plan', 'style' => 'width:30%'),
			form_dropdown('plan_id', $plans, $subscription['plan_id'])
		);

$this->table->add_row(
		array('data' => '<b>New Credit Card Information</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		array('data' => 'This new credit card will be charged starting ' . $subscription['next_charge_date'] . '.', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('membrr_order_form_cc_number'),
		form_input(array('name' => 'cc_number', 'style' => 'width: 170px'))
	);

$this->table->add_row(
		lang('membrr_order_form_cc_name'),
		form_input(array('name' => 'cc_name', 'style' => 'width: 170px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_cc_cvv2'),
		form_input(array('name' => 'cc_cvv2', 'style' => 'width: 50px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_cc_expiry'),
		form_dropdown('cc_expiry_month',$end_date_months) . '&nbsp;&nbsp;' . form_dropdown('cc_expiry_year',$expiry_date_years)
	);
	
$this->table->add_row(
		array('data' => '<b>' . lang('membrr_order_form_customer_info') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_first_name'),
		form_input(array('name' => 'first_name', 'value' => $address['first_name'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_last_name'),
		form_input(array('name' => 'last_name', 'value' => $address['last_name'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_address'),
		form_input(array('name' => 'address', 'value' => $address['address'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_address_2'),
		form_input(array('name' => 'address_2', 'value' => $address['address_2'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_city'),
		form_input(array('name' => 'city', 'value' => $address['city'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_region'),
		form_dropdown('region', $regions, $address['region']) . '&nbsp;&nbsp;' . form_input(array('name' => 'region_other', 'value' => $address['region_other'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_country'),
		form_dropdown('country', $countries, $address['country'])
	);
	
$this->table->add_row(
		lang('membrr_order_form_customer_postal_code'),
		form_input(array('name' => 'postal_code', 'value' => $address['postal_code'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_update_cc_button'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>