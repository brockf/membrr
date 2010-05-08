<? if (validation_errors()) { ?>
	<div class="membrr_error"><?=validation_errors();?></div>
<? } ?>
<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_protect_a_channel'), 'colspan' => '2')
						);

$this->table->add_row(
		array('data' => lang('membrr_channel'), 'style' => 'width:30%'),
		$channel['channel_name'] . form_hidden('channel_id',$channel['channel_id'])
	);
	
$this->table->add_row(
		lang('membrr_required_subscription'),
		form_multiselect('plans[]',$plan_options,$plans)
	);
	
$this->table->add_row(
		lang('membrr_one_subscription_per_post'),
		form_checkbox('one_post','1',($one_post == 1) ? TRUE : FALSE)
	);

if (!empty($statuses)) {	
	$this->table->add_row(
			lang('membrr_expiration_status'),
			form_dropdown('expiration_status',$statuses,$expiration_status)
		);
} else {
	$this->table->add_row(
			lang('membrr_expiration_status'),
			lang('membrr_must_have_status_group') . form_hidden('expiration_status','')
		);
}
	
$this->table->add_row(
		lang('membrr_no_subscription_redirect'),
		form_input(array('name' => 'order_form','value' => $order_form, 'style' => 'width:375px'))
	);

$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_protect_this_channel_edit'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>