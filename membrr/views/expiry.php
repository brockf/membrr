<? if ($errors) { ?>
	<div class="membrr_error"><?=$errors;?></div>
<? } ?>

<?=form_open($form_action)?>
<?=form_hidden('subscription_id', $subscription['id']);?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_change_expiry_title'), 'colspan' => '2')
						);
						
$this->table->add_row(
		'Subscription ID',
		$subscription['id']
	);
	
$this->table->add_row(
		'Member',
		$subscription['user_screenname']
	);	
		
$this->table->add_row(
		lang('membrr_subscription_ends'),
		form_dropdown('end_date_day',$end_date_days,$subscription['end_date']['day']) . '&nbsp;' . form_dropdown('end_date_month',$end_date_months,$subscription['end_date']['month']) . '&nbsp;' . form_dropdown('end_date_year',$end_date_years,$subscription['end_date']['year'])
	);
	
$this->table->add_row(
		'',
		'<input type="checkbox" name="record_payment" value="1" checked="checked" /> Record as payment of <input type="text" name="payment_amount" style="width:50px" value="' . $subscription['amount'] . '" /> (Payment will be recorded as a charge on the next charge date and the next charge date will be advanced by one interval).'
	);	
	
$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_update_subscription'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>