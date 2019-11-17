<?php
    ee()->cp->load_package_css('settings');
?>
 <!-- CSS Loader -->
    <div class="loader loader-bar" data-text data-rounded></div>
   <?php
    if (isset($current_settings)) {
        echo '<div class="app-notice-wrap">'. ee("CP/Alert")->getAllInlines() .'</div>';
        ee()->dbg->c_log($current_settings, "Current Settings");
        echo "<script>console.groupCollapsed('Current Services Settings');</script>";
        echo "<script>console.dir(". json_encode($current_settings).");</script>";
        echo "<script>console.groupEnd();</script>";
        echo '<input type="button" id="btnData" class="btn" value="Show Data" />';
    }
    ?>
<div>
   
    <?php if (isset($active_service_names)) : ?>
        <input id='active_services' type='hidden' value='<?php echo $active_service_names; ?>' />
    <?php endif; ?>
 
    </div>
<div class="col-group ">

    <?php if (isset($table)) : ?>

        <?php $this->embed(EXT_SHORT_NAME.((isset($emails)) ? ':email/sent' : ':email/templates'), $table); ?>
    <?php elseif (empty($sections)) :  ?>
        <h1><?= lang($current_action.'_heading'); ?></h1>
        <div class="txt-wrap">
        <?=lang($current_action.'_text'); ?>
        </div>
    <?php else: ?>
        <?php $this->embed('ee:_shared/form', $vars); ?>
    <?php endif; ?>
</div>