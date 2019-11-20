<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
/**
 * MANYMAILERPLUS internal settings
 */
class Settings 
{
    use ManyMailerPlus\libraries\Utility_Functions;
    /**
     * Constructor
     */
    public function __construct($settings=array())
    {
        // $CI = ee();
        $list = get_class_methods($this);
        rsort($list);
        // ee()->dbg->c_log(json_encode($list, 1), __METHOD__, true);
        
        $this->settings = $settings;
        $this->model = ee('Model')->get('Extension')
            ->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
            ->first();
    }
    // --------------------------------
    //  Settings
    // --------------------------------

    
    public function settings()
    {
        $defaults = $this->u_getCurrentSettings();
        $vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/settings/save_settings');
        $vars['save_btn_text'] = 'btn_save_settings';
        $vars['save_btn_text_working'] = 'btn_saving';
        $vars['sections'] = array(array(array(
                    'title' => 'debug_mode',
                    'desc' => 'debug_desc',
                    'fields' => array(
                        'debug_mode' => array(
                            'type' => 'yes_no',
                            'value' => (isset($defaults['debug_mode'])) ? $defaults['debug_mode'] : "n",
                        )
                    )
                )
        )
        );
        ee()->dbg->c_log($defaults, __METHOD__);
        return $vars;
    }

    public function get_settings()
    {
        $settings = array();
        foreach ($_POST as $key => $val) {
            $settings[$key] = $val;
        }
       
        // ee()->dbg->c_log($settings, __METHOD__, true);
        return array_merge($this->u_getCurrentSettings(), $settings);
    }

    public function reset_settings()
    {
        $name = $title = $body = 'reset_';
        $this->u_saveSettings(array());
        $current = $this->u_getCurrentSettings();
        $status = true;
        $defer = true;
        if (!empty($current)) {
            $name .= 'error';
            $title .= 'error_title';
            $body .= 'error_body';
            $status = false;
            $defer = false;
        } else {
            $name .= 'success';
            $title .= 'success_title';
            $body .= 'success_body';
        }
        
        $this->u_messages($name, lang($title), lang($body), $status, $defer);
        return $current;
    }
  
    public function save_settings()
    {
        
        $final_settings = array();
        if (empty($_POST)) {
            show_error(ee()->lang->line('unauthorized_access'));
        } else {
            $final_settings = $this->get_settings();
        }
        // ee()->dbg->c_log($final_settings, __METHOD__, true);
        $this->u_saveSettings($final_settings);
        
        // ee()->dbg->c_log($this->u_getCurrentSettings(), __METHOD__, true);
        $this->u_messages('message_success', lang('message_success'), lang('preferences_updated'), false, false);
        ee()->functions->redirect(ee('CP/URL')->make(EXT_SETTINGS_PATH.'/settings')->compile());
    }

}
// END CLASS
// EOF
