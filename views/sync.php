<? foreach ($checks as $check) { ?>
	<p>
	<? if ($check['ok'] == true) { ?> <b><span style="color:green">OK!</span></b>&nbsp;&nbsp; <? } ?>
	<? if ($check['ok'] == false) { ?> <b><span style="color:red">WARNING</span></b>&nbsp;&nbsp; <? } ?>
	<?=$check['text'];?>
	</p>
<? } ?>