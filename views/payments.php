<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('membrr_id'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_user'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_subscription'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_plan_name'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_date'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_amount'), 'style' => 'width: 15%;')
);

if (!$payments) {
	$this->table->add_row(array(
							'data' => lang('membrr_no_payments_dataset'),
							'colspan' => '6'
						));
}
else {
	foreach ($payments as $payment) {
		$this->table->add_row($payment['id'],
						$payment['user_screenname'],
						$payment['sub_link'],
						$payment['plan_name'],
						$payment['date'],
						$config['currency_symbol'] . $payment['amount']
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>
<?=$pagination;?>