<?php ?>
<form method="get" action="<?=$cp_url;?>">
<label><?=lang('membrr_search_all_subscriptions');?></label>
<?=form_input('search', $search_query);?><br /><input type="submit" name="submit" value="Search" />&nbsp;<a href="<?=$cp_url;?>">View All</a>
<? foreach ($search_fields as $field => $value) { ?>
<input type="hidden" name="<?=$field;?>" value="<?=$value;?>" />
<? } ?>
</form>
<br />
<?php

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
		elseif ($subscription['renewed'] == TRUE) {
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
		
		$this->table->add_row($subscription['id'],
						'<a href="' . $subscription['member_link'] . '">' . $subscription['user_screenname'] . '</a>',
						$subscription['plan_name'],
						$config['currency_symbol'] . $subscription['amount'],
						$subscription['next_charge_date'],
						$status,
						$options
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>
<?=$pagination;?>