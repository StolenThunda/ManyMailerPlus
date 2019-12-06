<?php
    ee()->cp->load_package_css('settings');
?>
 <!-- CSS Loader -->
    <div class="loader loader-bar" data-text data-rounded></div>
    
   <?php
    if (isset($current_settings)) {
        ee()->dbg->c_log($current_settings, __FILE__ ." Current Settings ".__LINE__);
        echo "<script>console.groupCollapsed('Current Services Settings');</script>";
        echo "<script>console.dir(". json_encode($current_settings).");</script>";
        echo "<script>console.groupEnd();</script>";
    }
    ee()->dbg->c_log(get_defined_vars(), "Current Vars ". __FILE__.': '.__LINE__);

    ?>
<div>
   
    <?php if (isset($active_service_names)) : ?>
        <input id='active_services' type='hidden' value='<?php echo $active_service_names; ?>' />
    <?php endif; ?>
 
    </div>
<div class="col-group ">
<div class="col w-12">
    <a href="" class="m-link" rel="mail_progress"></a>
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
</div>