<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_protect_a_channel'), 'colspan' => '2')
						);

if (!empty($channels)) {
	$this->table->add_row(
			array('data' => lang('membrr_channel'), 'style' => 'width:30%'),
			form_dropdown('channel_id', $channels)
		);
}
else {
	$this->table->add_row(
			array('data' => lang('membrr_channel'), 'style' => 'width:30%'),
			lang('membrr_you_need_channels')
		);
}

$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_protect_this_channel'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>