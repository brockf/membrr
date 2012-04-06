<? if ($first_config == TRUE) { ?>
<div class="membrr_box"><b>Welcome to Membrr for Expression Engine!</b><br /><br />
Your plugin has not yet been configured.  Enter your API configuration details below so that
the Membrr plugin can communicate with your billing server.  This integration will:
<ul class="membrr">
	<li>Import the plans you created in your Membrr control panel as subscription products purchasable at your web site.</li>
	<li>Process subscription payments via the Membrr billing engine.</li>
	<li>Allow you to synchronize system data and see the latest subscription payments (both failed and successful).</li>
</ul>
</div>
<? } ?>

<? if (validation_errors()) { ?>
	<div class="membrr_error"><?=validation_errors();?></div>
<? } ?>
<? if ($failed_to_connect) { ?>
	<div class="membrr_error"><?=$failed_to_connect;?></div>
<? } ?>

<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('membrr_settings'), 'colspan' => '2')
						);

$this->table->add_row(
		lang('membrr_api_url'),
		form_input(array('name' => 'api_url', 'value' => $api_url, 'style' => 'width: 375px'))
	);

$this->table->add_row(
		lang('membrr_api_id'),
		form_input(array('name' => 'api_id', 'value' => $api_id, 'style' => 'width: 375px'))
	);
	
$this->table->add_row(
		lang('membrr_secret_key'),
		form_input(array('name' => 'secret_key', 'value' => $secret_key, 'style' => 'width: 375px'))
	);
	
$this->table->add_row(
		lang('membrr_currency_symbol'),
		form_input(array('name' => 'currency_symbol', 'value' => $currency_symbol, 'style' => 'width: 50px'))
	);
	
if ($gateways) {
	$this->table->add_row(
		lang('membrr_default_gateway'),
		form_dropdown('gateway',$gateways,$gateway)
	);
}

$this->table->add_row(
	lang('membrr_update_email'),
	form_checkbox('update_email','1',$update_email)
);

$this->table->add_row(
	lang('membrr_use_captcha'),
	form_checkbox('use_captcha','1',$use_captcha)
);

$this->table->add_row(
		lang('membrr_available_countries'),
		$countries_text
	);

$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('membrr_save_configuration'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>