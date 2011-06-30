<? if (validation_errors()) { ?>
	<div class="membrr_error"><?=validation_errors();?></div>
<? } ?>
<?=form_open($form_action)?>
<? foreach ($plan as $key => $hidden) { ?>
<?=form_hidden($key, $hidden);?>
<? } ?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_import_plan_2'), 'colspan' => '2')
						);

$this->table->add_row(
		array('data' => '<b>' . lang('membrr_plan_details') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		array('data' => lang('membrr_plan_name'), 'style' => 'width:30%'),
		$plan['name']
	);
	
$this->table->add_row(
		lang('membrr_plan_fee'),
		$config['currency_symbol'] . $plan['amount'] . ' every ' . $plan['interval'] . ' days'
	);
	
$this->table->add_row(
		lang('membrr_plan_free_trial'),
		($plan['free_trial'] == '0') ? 'none' : $plan['free_trial'] . ' ' . $this->lang->line('membrr_before_charge')
	);
	
$this->table->add_row(
		lang('membrr_plan_occurrences'),
		($plan['occurrences'] == '0') ? 'infinite' : $plan['occurrences']
	);
	
$this->table->add_row(
		array('data' => '<b>' . lang('membrr_configure_plan') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('membrr_display_name'),
		form_input('plan_name', $plan_name)
	);	
	
$this->table->add_row(
		array('data' => lang('membrr_description'), 'valign' => 'top'),
		form_textarea('plan_description', $plan_description)
	);
	
$this->table->add_row(
		lang('membrr_initial_charge'),
		form_input('initial_charge', $plan_initial_charge)
	);	
	
$this->table->add_row(
		lang('membrr_new_member_group'),
		form_dropdown('new_member_group', $member_groups, $new_member_group)
	);
	
$this->table->add_row(
		lang('membrr_new_member_group_expire'),
		form_dropdown('new_member_group_expire', $member_groups, $new_member_group_expire)
	);
	
$this->table->add_row(
		lang('membrr_redirect_url'),
		form_input(array('name' => 'redirect_url', 'value' => $redirect_url, 'style' => 'width:375px'))
	);
	
$this->table->add_row(
		lang('membrr_renewal_option'),
		form_checkbox(array('name' => 'renewal_extend_from_end', 'value' => '1', 'checked' => $renewal_extend_from_end))
	);
	
$this->table->add_row(
		lang('membrr_plan_gateway'),
		form_dropdown('gateway', $gateways, $selected_gateway)
	);
		
$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_import_plan'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>