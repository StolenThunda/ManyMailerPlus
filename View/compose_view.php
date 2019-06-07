<?php
    ee()->cp->load_package_css('settings');
?>

<div>
	<?php if (isset($active_service_names)) : ?>		
		<input id='active_services' type='hidden' value='<?php echo json_encode($active_service_names, 1); ?>' />
	<?php endif; ?>
	<?php
        if (isset($current_settings)) {
            echo '<div><pre>';
            echo print_r($current_settings, 1);
            echo '</div>';
        }
    ?>
</div>
<div class="col-group ">
<!-- <div class="app-notice-wrap"><=//ee('CP/Alert')->getAllInlines()?></div> -->
	<?php if (isset($current_action)) : ?>
		<?php if (!in_array($current_action, array_merge(array_keys($services), array_keys($sidebar)))): ?>
			<?php
                $a = get_defined_vars()['vars']['settings'];
                echo '<pre>';
                echo print_r($a, 1);
                echo '</pre>';
            ?>
		<?php else: ?>	
			<h1><?= lang($current_action.'_heading'); ?></h1>
			<div class="txt-wrap">
				<?=lang($current_action.'_text'); ?>
			</div>
		<?php endif; ?>	
	<?php elseif (isset($table)) : ?>

		<?php $this->embed(EXT_SHORT_NAME.((isset($emails)) ? ':email/sent' : ':email/templates'), $table); ?>
	<?php else: ?>		
		<?php $this->embed('ee:_shared/form', $vars); ?>
	<?php endif; ?>
</div>