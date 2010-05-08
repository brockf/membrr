<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_create_subscription'), 'colspan' => '2')
						);

if (!empty($plans)) {
	$this->table->add_row(
			array('data' => lang('membrr_order_form_select_plan'), 'style' => 'width:30%'),
			form_dropdown('plan_id', $plans)
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
		form_dropdown('member_id', $members)
	);

$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_continue'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>