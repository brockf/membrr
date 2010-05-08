<div class="membrr_box"><?=$this->lang->line('membrr_channels_intro');?></div>
<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_channel'), 'style' => 'width:30%'),
					array('data' => lang('membrr_required_subscription'), 'style' => 'width:40%'),
					array('data' => '', 'style' => 'width: 30%')
				);
						
if (!$channels) {
	$this->table->add_row(
						array('data' => lang('membrr_no_channels'), 'colspan' => '3')
					);
}
else {
	foreach ($channels as $channel) {
		$this->table->add_row(
							$channel['channel_name'],
							implode('<br />',$channel['display_plans']),
							$channel['options']
						);
	}
}
?>

<?=$this->table->generate();?>

<?=form_open($form_action);?>
<?=form_submit('form_submit',$this->lang->line('membrr_protect_a_channel'));?>
<?=form_close();?>