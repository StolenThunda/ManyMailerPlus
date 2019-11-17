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
class Settings //extends \ManyMailerPlus\libraries\chriskacerguis\RestServer\RestController
{
    /**
     * Constructor
     */
    public function __construct($settings=array())
    {
        // parent::__construct();
        $CI = ee();
        $this->settings = $settings;
        $this->site_id = ee()->config->item('site_id');
        $this->model = ee('Model')->get('Extension')
            ->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
            ->first();
    }
    // --------------------------------
    //  Settings
    // --------------------------------

    
    public function settings()
    {
        $defaults = $this->model->settings;
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
        ee()->dbg->c_log($vars, __METHOD__);
        return $vars;
    }

    public function get_settings()
    {
        $settings = array();
        foreach ($_POST as $key => $val) {
            $settings[$key] = $val;
        }
       
     
        return $settings;
    }

    public function return_settings()
    {
        return ee()->mail_svc->get_service_order();
    }

    public function save_settings($other_settings=array())
    {
        if (empty($_POST)) {
            show_error(ee()->lang->line('unauthorized_access'));
        } else {
            $settings = $this->get_settings();//array_merge($this->get_settings(), $other_settings);;
        }
        // var_dump($other_settings);
        ee()->dbg->c_log($settings, __METHOD__);
        $this->model->settings = $settings;
        $this->model->save();
        ee('CP/Alert')->makeInline('message_success')
        ->asSuccess()
        ->canClose()
        ->withTitle(lang('message_success'))
        ->addToBody(lang('preferences_updated'))
        ->defer();
        ee()->functions->redirect(ee('CP/URL')->make(EXT_SETTINGS_PATH.'/settings')->compile());
    }
}
// END CLASS
// EOF
