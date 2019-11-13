<?php
    ee()->cp->load_package_css('settings');
?><!-- Loader -->
<div class="loader loader-default"></div>
<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines(); ?></div>
<div class="col-group">   
    <div class='form-standard'>
        
    <form class="demo-form" action="<?=$base_url; ?>" method="post">
         <div class="form-btns-top form-btns">
            <h1 style='float: none'><?=$cp_page_title; $iter = 0; $steps = count(array_keys($sections)); ?></h1><br />
            <div class="form-navigation">
            <input type="button" class="previous btn pull-left" value="Previous" />
            <input type="button" class="next btn pull-right" value="Next &gt" />
                <input type="submit" class="btn pull-right" value="<?=$save_btn_text; ?>" data-submit-text="<?=$save_btn_text; ?>" data-work-text="<?=$save_btn_text; ?>" />
            
                <span class="clearfix"></span>
            </div>
        </div>
        <?php foreach (array_keys($sections) as $fieldset): ?>
            <div class="form-section" data-slug="<?=$fieldset; ?>">  
                <?php ++$iter; $percent = ($iter / $steps) * 100; ?>       
                <h3>Progress: <i><?=$percent; ?>%</i></h3>
                <?php $this->embed('ee:_shared/progress_bar', array('percent' => $percent)); ?>
                 <h2><?php echo lang($fieldset); ?></h2>
                <?php if (is_array($sections[$fieldset])): ?>    
                    <?php foreach (array_keys($sections[$fieldset]) as $el_name): ?>
                        <fieldset data-control="<?=$el_name; ?>" <?php
                            if (strpos($sections[$fieldset][$el_name], '*') !== false) {
                                echo "class='fieldset-required'";
                                $sections[$fieldset][$el_name] = str_replace('*', '', $sections[$fieldset][$el_name]);
                            } ?>>
                            <?php if (!in_array($el_name, array(''))): ?>        
                                <?php if ($el_name !== ' '): ?>
                                    <div class='field-instruct'>
                                    <?php echo lang($el_name, $el_name); ?> 
                                    <em><?=lang($el_name.'_desc'); ?></em>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="field_control">
                                <?php if (is_array($sections[$fieldset][$el_name])) {
                                foreach ($sections[$fieldset][$el_name] as $el) {
                                    echo $el;
                                }
                            } else {
                                echo $sections[$fieldset][$el_name];
                            }
                                ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>       
                <?php else: ?>    
                    <?=$sections[$fieldset]; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
       
        <div class="form-navigation form-btns">
            <input type="button" class="previous btn pull-left" value="Previous" />
            <input type="button" class="next btn pull-right" value="Next &gt" />
            <input type="submit" class="btn pull-right" value="<?=$save_btn_text; ?>" data-submit-text="<?=$save_btn_text; ?>" data-work-text="<?=$save_btn_text; ?>" />
            <span class="clearfix"></span>
        </div>
    </form>
    </div>
</div>