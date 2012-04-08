<? if (validation_errors()) { ?>
	<div class="membrr_error"><?=validation_errors();?></div>
<? } ?>
<? if ($failed_transaction) { ?>
	<div class="membrr_error"><?=$failed_transaction;?></div>
<? } ?>

<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_create_subscription'), 'colspan' => '2')
						);

$this->table->add_row(
			array('data' => lang('membrr_order_form_select_plan'), 'style' => 'width:30%'),
			$plan['name'] . form_hidden('plan_id',$plan['id'])
		);

$this->table->add_row(
		lang('membrr_user'),
		$member['screen_name'] . ' (' . $member['email'] . ')' . form_hidden('member_id',$member['member_id']) . form_hidden('process_transaction','1')
	);
	
$this->table->add_row(
		lang('membrr_subscription_ends'),
		form_checkbox('never_ends','1',TRUE) . '&nbsp;' . lang('membrr_never') . '&nbsp;&nbsp;&nbsp;&nbsp;' . form_dropdown('end_date_day',$end_date_days) . '&nbsp;' . form_dropdown('end_date_month',$end_date_months) . '&nbsp;' . form_dropdown('end_date_year',$end_date_years)
	);
	
$this->table->add_row(
		array('data' => '<b>' . lang('membrr_pricing_details') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('membrr_free_subscription'),
		form_checkbox('free','1',($plan['price'] == '0.00') ? TRUE : FALSE)
	);
	
$this->table->add_row(
		lang('membrr_custom_recurring_rate'),
		 $config['currency_symbol'] . form_input(array('name' => 'recurring_rate', 'value' => $plan['price'], 'maxlength' => '10', 'style' => 'width:75px'))
	);
	
$this->table->add_row(
		lang('membrr_custom_first_charge_rate'),
		 $config['currency_symbol'] . form_input(array('name' => 'first_charge_rate', 'value' => $plan['initial_charge'], 'maxlength' => '10', 'style' => 'width:75px'))
	);
	
$this->table->add_row(
		lang('membrr_coupon'),
		form_input(array('name' => 'coupon', 'value' => '', 'maxlength' => '100', 'style' => 'width:125px'))
	);	
	
$this->table->add_row(
		array('data' => '<b>' . lang('membrr_order_form_credit_card') . '</b>', 'colspan' => '2')
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
		lang('membrr_order_form_customer_company'),
		form_input(array('name' => 'company', 'value' => $address['company'], 'style' => 'width: 250px'))
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
		lang('membrr_order_form_customer_phone'),
		form_input(array('name' => 'phone', 'value' => $address['phone'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		array('data' => '<b>' . lang('membrr_options') . '</b>', 'colspan' => '2')
	);	
	
$this->table->add_row(
		lang('membrr_gateway'),
		form_dropdown('gateway', $gateways, '')
	);	
	
$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_process'))
	);
	
echo form_hidden('renew', $renew);	
		
?>
<?=$this->table->generate();?>
<?=form_close();?>