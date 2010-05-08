<div class="membrr_box"><?=$this->lang->line('membrr_howto_import_plan');?></div>
<? if ($no_plans) { ?>
<div class="membrr_error"><?=$this->lang->line('membrr_unable_to_find_plans');?></div>
<? } else { ?>
	<? if ($no_plan_id) { ?>
		<div class="membrr_error"><?=$this->lang->line('membrr_must_have_plan');?></div>
	<? } ?>
	<?=form_open($form_action)?>
	
	<?php
	
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
						array('data' => lang('membrr_import_plan_1'), 'colspan' => '2')
							);
	
	$this->table->add_row(
			array('data' => lang('membrr_select_plan'), 'style' => 'width:30%'),
			form_dropdown('plan_id', $plans)
		);
	
	$this->table->add_row(
			'',
			form_submit('submit_form', $this->lang->line('membrr_continue_with_import'))
		);
			
	?>
	<?=$this->table->generate();?>
	<?=form_close();?>
<? } ?>