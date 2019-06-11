<?php
    ee()->cp->load_package_css('settings');
?>

<div>
	<?php if (isset($active_service_names)) : ?>		
		<input id='active_services' type='hidden' value='<?php echo $active_service_names; ?>' />
	<?php endif; ?>
	<?php
        if (isset($current_settings) && $debug) {
            echo '<div><pre>';
            echo print_r($current_settings, 1);
            echo '</div>';
        }
    ?>
</div>
<div class="col-group ">
<!-- <div class="app-notice-wrap"><=//ee('CP/Alert')->getAllInlines()?></div> -->
	<?php if (isset($table)) : ?>
		<?php $this->embed(EXT_SHORT_NAME.((isset($emails)) ? ':email/sent' : ':email/templates'), $table); ?>
	<?php elseif (empty($sections)):  ?>
		<h1><?= lang($current_action.'_heading'); ?></h1>
		<div class="txt-wrap">
			<?=lang($current_action.'_text'); ?>
		</div>
	<?php else: ?>			
		<?php $this->embed('ee:_shared/form', $vars); ?>	
	<?php endif; ?>
</div>