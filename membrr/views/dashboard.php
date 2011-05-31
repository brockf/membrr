<div class="membrr_box">
	<?=$this->lang->line('membrr_dashboard_intro');?>
	<? if (!$plans) { ?>
		<br /><br />
		<b><?=$this->lang->line('membrr_dashboard_first');?></b>
	<? } ?>
</div>
<br />
<h4><?=$this->lang->line('membrr_latest_payments');?></h4>
<br />
<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('membrr_id'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_user'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_subscription'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_plan_name'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_date'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_amount'), 'style' => 'width: 15%;'),
    array('data' => '', 'style' => 'width: 10%;')
);

if (!$payments) {
	$this->table->add_row(array(
							'data' => lang('membrr_no_payments_dataset'),
							'colspan' => '7'
						));
}
else {
	foreach ($payments as $payment) {
		$this->table->add_row($payment['id'],
						'<a href="' . $payment['member_link'] . '">' . $payment['user_screenname'] . '</a>',
						$payment['sub_link'],
						$payment['plan_name'],
						$payment['date'],
						$config['currency_symbol'] . $payment['amount'],
						$payment['refund_text']
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<? if (!empty($months)) { ?>

<br />
<h4><?=$this->lang->line('membrr_month_by_month');?></h4>
<br />

<select name="month" style="margin-bottom: 15px">
	<? foreach ($months as $month) { ?>
		<option value="<?=$month['url'];?>" <? if ($current['code'] == $month['code']) { ?>selected="selected"<? } ?>><?=$month['month'];?>, <?=$month['year'];?> (<?=$month['difference'];?> subscribers)</option>
	<? } ?>
</select>

<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('membrr_current_month'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_current_revenue'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_current_subscriptions'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_current_expirations'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_current_cancellations'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_current_payments'), 'style' => 'width: 15%;')
);

$this->table->add_row(
				$current['month'],
				$config['currency_symbol'] . money_format("%!i", $current['revenue']),
				$current['new_subscribers'],
				$current['expirations'],
				$current['cancellations'],
				$current['payments']
			);

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<? } ?>