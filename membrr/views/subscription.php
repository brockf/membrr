<?php
	
$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_subscription'), 'colspan' => '2')
						);

$this->table->add_row(
		array('data' => lang('membrr_id'), 'style' => 'width:30%'),
		$subscription['id']
	);
	
$this->table->add_row(
		array('data' => lang('membrr_status'), 'style' => 'width:30%'),
		$subscription['status']
	);
	
$this->table->add_row(
		array('data' => lang('membrr_user'), 'style' => 'width:30%'),
		'<a href="' . $subscription['member_link'] . '">' . $subscription['user_screenname'] . '</a>'
	);
	
$this->table->add_row(
		array('data' => lang('membrr_plan_name'), 'style' => 'width:30%'),
		'<a href="' . $subscription['plan_link'] . '">' . $subscription['plan_name'] . '</a>'
	);
	
$this->table->add_row(
		array('data' => lang('membrr_recurring_amount'), 'style' => 'width:30%'),
		$config['currency_symbol'] . $subscription['amount']
	);
	
$this->table->add_row(
		array('data' => lang('membrr_total_amount'), 'style' => 'width:30%'),
		$config['currency_symbol'] . money_format("%!^i",$subscription['total_amount'])
	);
	
if (!empty($subscription['coupon'])) {	
	$this->table->add_row(
			array('data' => lang('membrr_coupon_code'), 'style' => 'width:30%'),
			$subscription['coupon']
		);
}

if (!empty($subscription['card_last_four'])) {	
	$this->table->add_row(
			array('data' => lang('membrr_credit_card'), 'style' => 'width:30%'),
			'**** ' . str_pad($subscription['card_last_four'],4,"0",STR_PAD_LEFT)
		);
}
	
$this->table->add_row(
		array('data' => lang('membrr_start_date'), 'style' => 'width:30%'),
		$subscription['date_created']
	);
	
if ($subscription['active'] == '1') {
	if ($subscription['next_charge_date'] != FALSE) {
		$this->table->add_row(
			array('data' => lang('membrr_next_charge_date'), 'style' => 'width:30%'),
			$subscription['next_charge_date']
		);
	}
	if ($subscription['end_date'] != FALSE) {
		$this->table->add_row(
			array('data' => lang('membrr_date_ending'), 'style' => 'width:30%'),
			$subscription['end_date'] . $change_expiry
		);
	}
}
else {
	$this->table->add_row(
			array('data' => lang('membrr_date_cancelled'), 'style' => 'width:30%'),
			$subscription['date_cancelled']
		);
	$this->table->add_row(
			array('data' => lang('membrr_date_ending'), 'style' => 'width:30%'),
			$subscription['end_date'] . $end_now
		);
}

if ($subscription['entry_id'] != FALSE) {
    $this->table->add_row(
                   array('data' => lang('membrr_channel_post_link'), 'style' => 'width:30%'),
                   '<a href="' . BASE.AMP. 'C=content_publish' . AMP . 'M=entry_form' . AMP . 'entry_id=' . $subscription['entry_id'] . '">' . $subscription['channel'] . ': #' . $subscription['entry_id'] . '</a>'
           );
}

if (!empty($address['first_name']) and !empty($address['last_name'])) {
	$this->table->add_row(
			array('Billing Name',$address['first_name'] . ' ' . $address['last_name'])
		);
}

if (!empty($address['company'])) {
	$this->table->add_row(
			array('Billing Company',$address['company'])
		);
}

if (!empty($address['address'])) {
	$this->table->add_row(
			array('Address',$address['address'])
		);
}

if (!empty($address['address_2'])) {
	$this->table->add_row(
			array('Address 2',$address['address_2'])
		);
}

if (!empty($address['city'])) {
	$this->table->add_row(
			array('Billing City',$address['city'])
		);
}

if (!empty($address['region'])) {
	if (!empty($address['region_other'])) {
		$region = $address['region_other'] . '<br />';
	}
	else {
		$region = $address['region'] . '<br />';
	}
	
	$this->table->add_row(
			array('Billing Region',$region)
		);
}

if (!empty($address['country'])) {
	$this->table->add_row(
			array('Billing Country',$address['country'])
		);
}

if (!empty($address['postal_code'])) {
	$this->table->add_row(
			array('Billing Postal Code',$address['postal_code'])
		);
}

if (!empty($address['phone'])) {
	$this->table->add_row(
			array('Billing Phone',$address['phone'])
		);
}

$this->table->add_row(
		array('data' => '<b>' . lang('membrr_payments') . '</b>', 'colspan' => '2', 'style' => 'width:30%')
	);
	
if (empty($payments)) {
	$this->table->add_row(
		array('data' => lang('membrr_no_payments'), 'colspan' => '2', 'style' => 'width:30%')
	);
}
else {
	foreach ($payments as $payment) {
		$payment['refund_text'] = (empty($payment['refund_text'])) ? '' : ' (' . $payment['refund_text'] . ')';
		
		$this->table->add_row(
				array('data' => $config['currency_symbol'] . $payment['amount'] . ' ' . lang('membrr_received_on') . ' ' . $payment['date'] . $payment['refund_text'], 'colspan' => '2', 'style' => 'width:30%')
			);
	}	
}
		
?>

<?=$this->table->generate();?>