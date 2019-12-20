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
        $this->settings = ee()->config->load('settings_cfg', true);
        $this->settings_options = ee()->config->item('options', 'settings_cfg');
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
        $vars = array(
            'base_url' => ee('CP/URL', EXT_SETTINGS_PATH.'/settings/save_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'sections' => $this->_buildSections()
        );
        return $vars;
    }
    
    private function _buildSections()
    {
        $sections = array();
        $db_defaults = $this->u_getCurrentSettings();
        foreach ($this->settings_options as $key => $cfg) {
            // ee()->dbg->c_log($cfg, __METHOD__ . '  ' . __LINE__);
            $field = array(
                'title' => $key,
                'desc'  => $key . '_desc',
                'fields' => array(
                    $key => array(
                        'type' => $cfg['type'],
                        'value' => $db_defaults['config'][$key] ?: $cfg['default']
                        )
                    )
                );
            $sections[] = $field;
            // ee()->dbg->c_log($db_defaults['config'][$key], __METHOD__ . '  ' . __LINE__, true);
        }
        
        $return = (count($sections) < 1) ? array() : array($sections);
        // ee()->dbg->c_log($return, __METHOD__ . '  ' . __LINE__, true);
        return $return;
    }

    public function get_settings()
    {
        $settings = array();
        foreach ($_POST as $key => $val) {
            $settings[$key] = $val;
        }
       
        // ee()->dbg->c_log($settings, __METHOD__, true);
        return array_merge($this->u_getCurrentSettings(), array('config' => $settings));
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
