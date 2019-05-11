<?php
	ee()->cp->load_package_css('settings');
?>
<div class="col-group ">
    <h1><?=$cp_page_title; $iter = 0;?></h1>
    <form class="demo-form" action="<?=$base_url;?>">
    <div class="form-navigation">
            <button type="button" class="previous btn btn-info pull-left">&lt; Previous</button>
            <button type="button" class="next btn btn-info pull-right">Next &gt;</button>
            <input type="submit" class="btn btn-default pull-right" value="<?=$save_btn_text;?>">
            <span class="clearfix"></span>
        </div>

    <?php foreach(array_keys($sections) as $fieldset): ?>
        <div class="form-section"data-slug="<?=$fieldset;?>">                  
                <h2>Step <?php echo ++$iter; ?>: <?php echo lang($fieldset);?></h2>
            <?php if (is_array($sections[$fieldset])): ?>    
                <?php foreach(array_keys($sections[$fieldset]) as $el_name): ?>
                    <?php if (!in_array($el_name,array('',' '))): ?>        
                        <div class='field-instruct'>
                        <?php echo lang($el_name, $el_name); ?> 
                        <em><?=lang($el_name . '_desc');?></em>
                        </div>
                    <?php endif; ?>
                    <div class="field_control">
                    <?=$sections[$fieldset][$el_name];?>
                    </div>

                <?php endforeach; ?>       
            <?php else: ?>    
                <?=$sections[$fieldset];?>
            <?php endif; ?>
        </div>
    <?php endforeach;?>
        <div class="form-navigation">
            <button type="button" class="previous btn btn-info pull-left">&lt; Previous</button>
            <button type="button" class="next btn btn-info pull-right">Next &gt;</button>
            <input type="submit" class="btn btn-default pull-right" value="<?=$save_btn_text;?>">
            <span class="clearfix"></span>
        </div>

    </form>
</div>