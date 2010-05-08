<h1><?=$LANG->line('membrr_weblog_protector');?></h1>
<p><?=$LANG->line('membrr_weblogs_intro');?></p>
<? if ($notice_created == true) { ?>
<p class="box"><?=$LANG->line('membrr_created_weblog');?></p>
<? } ?>
<? if ($notice_deleted == true) { ?>
<p class="box"><?=$LANG->line('membrr_deleted_weblog');?></p>
<? } ?>
<?=$table;?>
<?=$form;?>