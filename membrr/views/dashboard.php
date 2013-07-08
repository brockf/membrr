<div class="membrr_box">
	<?=$this->lang->line('membrr_dashboard_intro');?>
	<? if (!$plans) { ?>
		<br /><br />
		<b><?=$this->lang->line('membrr_dashboard_first');?></b>
	<? } ?>
</div>
<br />

<h4><?=$this->lang->line('membrr_reports');?></h4>
<br />

<?=form_open($reports_action);?>

<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => 'Select Date Range and Subscription Plan', 'colspan' => '4')
);


$this->table->add_row(
				array('style' => 'width: 25%', 'data' => 'Start Date: ' . form_dropdown('start_month', $months, $start_month) . ' ' . form_dropdown('start_day', $days, $start_day) . ' ' . form_dropdown('start_year', $years, $start_year)),
				array('style' => 'width: 25%', 'data' => 'End Date: ' . form_dropdown('end_month', $months, $end_month) . ' ' . form_dropdown('end_day', $days, $end_day) . ' ' . form_dropdown('end_year', $years, $end_year)),
				array('colspan' => '2', 'data' => '')
			);
			
$this->table->add_row(
				array('colspan' => '2', 'style' => 'width: 25%', 'data' => 'Subscription Plan: ' . form_dropdown('plan_id', $plan_options, $current_plan)),
				array('colspan' => '2', 'data' => '')
			);			
			
$this->table->add_row(
				array('colspan' => '4', 'style' => 'width: 100%', 'data' => form_submit('report', 'Refresh Report'))
			);						

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => 'Total Subscriptions at End of Date Range (' . date('F d, Y', strtotime($end_year . '-' . $end_month . '-' . $end_day)) . ')', 'colspan' => '4')
);

if (!empty($plans)) {
	foreach ($plans as $plan) {
		if ($current_plan == $plan['id'] or $current_plan == 0) {
			$total = isset($plan_totals[$plan['id']]) ? $plan_totals[$plan['id']] : 0;
			
			$occurrences = ($plan['occurrences'] == 0) ? 'infinite' : $plan['occurrences'] . ' charges';
			$plan_name = $plan['name'] . ' (' . $plan['interval'] . ' days | ' . $occurrences . ')';
						
			$this->table->add_row(
							array('width' => '50%', 'data' => $plan_name),
							array('width' => '50%', 'data' => $total)
						);			
		}
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<?=form_open($reports_action);?>

<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => 'Select Report Type', 'colspan' => '4')
);

$this->table->add_row(
				array('style' => 'width: 25%', 'data' => form_radio('show','subscriptions',($show == 'subscriptions') ? TRUE : FALSE) . ' Subscriptions (' . $count_subscriptions . ')'),
				array('style' => 'width: 25%', 'data' => form_radio('show','renewals',($show == 'renewals') ? TRUE : FALSE) . ' Renewals (' . $count_renewals . ')'),
				array('style' => 'width: 25%', 'data' => form_radio('show','expirations',($show == 'expirations') ? TRUE : FALSE) . ' Expirations (' . $count_expirations . ')'),
				array('style' => 'width: 25%', 'data' => form_radio('show','cancellations',($show == 'cancellations') ? TRUE : FALSE) . ' Cancellations (' . $count_cancellations . ')')
			);			
			
$this->table->add_row(
				array('colspan' => '4', 'style' => 'width: 100%', 'data' => form_submit('report', 'Refresh Report'))
			);			

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<?=form_close();?>

<?

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('membrr_id'), 'style' => 'width: 7%;'),
    array('data' => lang('membrr_user'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_plan_name'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_amount'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_next_charge_date'), 'style' => 'width: 17%;'),
    array('data' => lang('membrr_status'), 'style' => 'width: 10%;'),
    array('data' => '', 'style' => 'width:26%')
);

if (!$subscriptions) {
	$this->table->add_row(array(
							'data' => lang('membrr_no_subscriptions_dataset'),
							'colspan' => '7'
						));
}
else {
	foreach ($subscriptions as $subscription) {
		if ($subscription['active'] == '1') {
			$status = $this->lang->line('membrr_active');
		}	
		elseif ($subscription['expired'] == '1') {
			$status = $this->lang->line('membrr_expired');
		}
		elseif ($subscription['renewed_recurring_id'] != 0) {
			$status = $this->lang->line('membrr_renewed');
		}
		elseif ($subscription['cancelled'] == '1') {
			$status = $this->lang->line('membrr_cancelled');
		}
		else {
			$status = '';
		}
		
		// prep options dropdown
		$options = '<select class="sub_options">';
		
		$options .= '<option value="" selected="selected">options (' . count($subscription['options']) . ')</option>';
		
		foreach ($subscription['options'] as $option => $link) {
			$options .= '<option value="' . $link . '">' . $option . '</option>';
		}
		
		$options .= '</optgroup></select>';
		
		$this->table->add_row(
						$subscription['recurring_id'],
						'<a href="' . $subscription['member_link'] . '">' . $subscription['screen_name'] . '</a>',
						$subscription['plan_name'],
						$config['currency_symbol'] . $subscription['subscription_price'],
						($subscription['next_charge_date'] == '0000-00-00') ? '' : date('F j, Y', strtotime($subscription['next_charge_date'])),
						$status,
						$options
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>
<?=$pagination;?>