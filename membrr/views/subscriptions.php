<?php ?>
<form method="get" action="<?=basename($_SERVER['SCRIPT_NAME']);?>">
<label><?=lang('membrr_search_all_subscriptions');?></label>
<?=form_input('search', $search_query);?><br /><input type="submit" name="go" value="Search" />&nbsp;<a href="<?=$cp_url;?>">View All</a>
<? foreach ($search_fields as $field => $value) { ?>
<input type="hidden" name="<?=$field;?>" value="<?=$value;?>" />
<? } ?>
</form>
<br />
<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('membrr_id'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_user'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_plan_name'), 'style' => 'width: 15%;'),
    array('data' => lang('membrr_amount'), 'style' => 'width: 10%;'),
    array('data' => lang('membrr_start_date'), 'style' => 'width: 20%;'),
    array('data' => lang('membrr_status'), 'style' => 'width: 15%;'),
    array('data' => '', 'style' => 'width:15%')
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
		elseif ($subscription['cancelled'] == '1') {
			$status = $this->lang->line('membrr_cancelled');
		}
		
		$this->table->add_row($subscription['id'],
						$subscription['user_screenname'],
						$subscription['plan_name'],
						$config['currency_symbol'] . $subscription['amount'],
						$subscription['date_created'],
						$status,
						$subscription['options']
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>
<?=$pagination;?>