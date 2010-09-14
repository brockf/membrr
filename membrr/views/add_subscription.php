<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_create_subscription'), 'colspan' => '2')
						);

if (!empty($plans)) {
	$this->table->add_row(
			array('data' => lang('membrr_order_form_select_plan'), 'style' => 'width:30%'),
			form_dropdown('plan_id', $plans, $selected_plan)
		);
}
else {
$this->table->add_row(
			array('data' => lang('membrr_order_form_select_plan'), 'style' => 'width:30%'),
			lang('membrr_you_need_plans')
		);
}

$this->table->add_row(
		lang('membrr_user'),
		form_input(array('name' => 'member_search', 'style' => 'width: 200px; margin-bottom: 5px;')) . '&nbsp;&nbsp;' . form_submit('submit_form', $this->lang->line('membrr_search_for_member'))
		. '<br />' . $this->lang->line('membrr_search_by')
	);
	
if ($searching === TRUE) {
	if (empty($members)) {
		$this->table->add_row(
			'',
			$this->lang->line('membrr_no_members')
		);
	}
	else {
		foreach ($members as $member) {
			$this->table->add_row(
				'',
				form_radio(array('name' => 'member_id', 'value' => $member['member_id'])) . '&nbsp;' . $member['screen_name'] . ' (' . $member['email'] . ')'
			);
		}
		
		$this->table->add_row(
			'',
			form_submit(array('name' => 'form_submit', 'value' => $this->lang->line('membrr_add_subscription_to_membrr')))
		);
	}
}
		
?>
<?=$this->table->generate();?>
<?=form_close();?>