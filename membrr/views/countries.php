<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					lang('membrr_available_countries')
				);
				
$this->table->add_row(
			'<a href="javascript:check_countries();">Check All</a>&nbsp;&nbsp;&nbsp;<a href="javascript:uncheck_countries()">Uncheck All</a>'
		);				

foreach ($countries->result_array() as $country) {
	$this->table->add_row(
			'<input type="checkbox" class="countries" name="country_' . $country['country_id'] . '" value="1" ' . (($country['available'] == '1') ? 'checked="checked"' : '') . ' /> ' . $country['name']
		);
}

$this->table->add_row(
		form_submit('submit_form', $this->lang->line('membrr_save_configuration'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>