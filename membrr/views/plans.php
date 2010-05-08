<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_id'), 'style' => 'width:5%'),
					array('data' => lang('membrr_plan_name'), 'style' => 'width:19%'),
					array('data' => lang('membrr_plan_fee'), 'style' => 'width:15%'),
					array('data' => lang('membrr_plan_free_trial'), 'style' => 'width:12%'),
					array('data' => lang('membrr_total_charges_short'), 'style' => 'width:12%'),
					array('data' => lang('membrr_num_subscribers'), 'style' => 'width:15%'),
					array('data' => lang('membrr_for_sale'), 'style' => 'width:10%'),
					array('data' => '', 'style' => 'width: 15%')
				);
						
if (!$plans) {
	$this->table->add_row(
						array('data' => lang('membrr_no_plans_dataset'), 'colspan' => '8')
					);
}
else {
	foreach ($plans as $plan) {
		$this->table->add_row(
							$plan['id'],
							$plan['name'],
							($plan['price'] == '0.00' or empty($plan['price'])) ? $this->lang->line('membrr_free') : $config['currency_symbol'] . $plan['price'] . ' every ' . $plan['interval'] . ' days',
							($plan['free_trial'] == '0') ? 'none' : $plan['free_trial'] . ' days',
							($plan['occurrences'] == '0') ? 'infinite' : $plan['occurrences'],
							$plan['num_subscribers'],
							($plan['for_sale'] == 1) ? 'Yes' : 'No',
							$plan['options']
						);
	}
}
?>

<?=$this->table->generate();?>

<?=form_open($form_action);?>
<?=form_submit('form_submit',$this->lang->line('membrr_import_new_plan'));?>
<?=form_close();?>