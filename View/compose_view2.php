<?php
	ee()->cp->load_package_css('settings');
?>
<div class="col-group">   
    <div class='form-standard'>
        
    <form class="demo-form" action="<?=$base_url;?>" method="post">
         <div class="form-btns-top form-btns">
    <h1><?=$cp_page_title; $iter = 0; $steps = count(array_keys($sections)); ?></h1>
    <div class="form-navigation">
            <button type="button" class="previous btn btn-info pull-left">&lt; Previous</button>
            <button type="button" class="next btn btn-info pull-right">Next &gt;</button>
            <input type="submit" name='top_submit' class="btn btn-default pull-right submit" value="<?=$save_btn_text;?>">
            <span class="clearfix"></span>
        </div></div>
        <?php foreach(array_keys($sections) as $fieldset): ?>
            <div class="form-section" data-slug="<?=$fieldset;?>">  
                <?php ++$iter; $percent = ($iter/$steps)*100; ?>       
                <h3>Progress: <i><?=$percent;?>%</i></h3>
                <?php $this->embed('ee:_shared/progress_bar', array('percent' => $percent));?>
                 <h2><?php echo lang($fieldset);?></h2>
                <?php if (is_array($sections[$fieldset])): ?>    
                    <?php foreach(array_keys($sections[$fieldset]) as $el_name): ?>
                        <fieldset data-control="<?=$el_name?>">
                            <?php if (!in_array($el_name,array(''))): ?>        
                                <div class='field-instruct'>
                                <?php echo lang($el_name, $el_name); ?> 
                                <em><?=lang($el_name . '_desc');?></em>
                                </div>
                            <?php endif; ?>
                            <div class="field_control">
                                <?=$sections[$fieldset][$el_name];?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>       
                <?php else: ?>    
                    <?=$sections[$fieldset];?>
                <?php endif; ?>
            </div>
        <?php endforeach;?>
       
        <div class="form-navigation form-btns">
            <button type="button" class="previous btn btn-info pull-left">&lt; Previous</button>
            <button type="button" class="next btn btn-info pull-right">Next &gt;</button>
            <input type="submit" name='bottom_submit' class="btn btn-default pull-right submit" value="<?=$save_btn_text;?>">
            <span class="clearfix"></span>
        </div>
    </form>
    </div>
</div>