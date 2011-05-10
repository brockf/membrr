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