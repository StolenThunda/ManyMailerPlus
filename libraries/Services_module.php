<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

/*
    This file is part of ManyMailer add-on for ExpressionEngine.

    ManyMailer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ManyMailer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.

    Copyright 2019 Antonio Moses - http://texasbluesalley.com
*/

require_once PATH_THIRD.EXT_SHORT_NAME.'/config.php';

class Services_module
{
    public $config;
    public $debug = false;
    public $email_crlf = '\n';
    public $email_in = array();
    public $email_out = array();
    public $model;
    public $protocol;
    public $settings = array();
    public $site_id;
    public $services = array();
    public $version = EXT_VERSION;
    public $is_func = null;
    public $service_order = array();

    public function __construct($settings = '')
    {
        ee()->load->helper('MessageArray');
        ee()->load->helper('html');
        $this->debug = (isset($settings['debug']) ? $settings['debug'] : false);
        $this->config = ee()->config->item(EXT_SHORT_NAME.'_settings');
        if (ee()->config->item('email_crlf') != false) {
            $this->email_crlf = ee()->config->item('email_crlf');
        }
        $this->model = ee('Model')->get('Extension')
            ->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
            ->first();
        $this->protocol = ee()->config->item('mail_protocol');
        $this->site_id = ee()->config->item('site_id');
        $this->sidebar_loaded = ee()->config->load('sidebar', true, true);
        $this->services_loaded = ee()->config->load('services', true, true);
        $this->sidebar_options = ee()->config->item('options', 'sidebar');
        $this->services = ee()->config->item('services', 'services');
        $this->settings = $settings;
        $this->dbg_msgs = new MessageArray();
        $this->service_order = $this->get_service_order();
    }

    public function settings_form($all_settings)
    {
        $settings = $this->get_settings();
        $services_sorted = $this->service_order;
        if (ee('Request')->isAjax()) {
            // $this->update_service_order($all_settings);
            if ($services = ee('Request')->post('service_order')) {
                $all_settings[$this->site_id]['service_order'] = explode(',', $services);
                $this->model->settings = $all_settings;
                $this->model->save();
                ee()->dbg->c_log($all_settings, __METHOD__, true);
                exit;
            }
        }
        $vars = array(
            'debug' => $this->debug,
            'current_service' => false,
            'current_settings' => $settings,
            'services' => $services_sorted,
            'ee_version' => $this->ee_version(),
            'categories' => array_keys($this->sidebar_options),
        );
        if (ee()->uri->segment(6) !== '') {
            $vars['current_service'] = $this->current_service = ee()->uri->segment(6);
            $vars['current_action'] = null;
        }

        if (!empty($this->config)) {
            $vars['form_vars']['extra_alerts'] = array('config_warning');
            ee('CP/Alert')->makeInline('config_warning')
                ->asWarning()
                ->withTitle(lang('config_warning_heading'))
                ->addToBody(lang('config_warning_text'))
                ->now();
        }

        $vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/services/save');

        $vars['save_btn_text'] = 'btn_save_settings';
        $vars['save_btn_text_working'] = 'btn_saving';
        $vars['sections'] = array();
        if ($this->current_service) {
            $vars['sections'] = array_merge($vars['sections'], $this->_get_service_detail());
            $vars['cp_page_title'] = lang($this->current_service.'_name');
        } else {
            $vars['cp_page_title'] = lang('services');
            $vars['current_action'] = 'services';
            unset($vars['current_service']);
        }

        return $vars;
    }

    public function get_settings($all_sites = false)
    {
        $all_settings = $this->model->settings;

        // ee()->dbg->c_log($all_settings, __METHOD__);
        $settings = ($all_sites == true || empty($all_settings)) ? $all_settings : $all_settings[$this->site_id];
        // Check for config settings - they will override database settings
        if ($all_sites == false) {
            // Override each setting from config
            if (!empty($this->config[$this->site_id])) {
                foreach ($this->config[$this->site_id] as $k => $v) {
                    $settings[$k] = $v;
                }
            }

            // Set a service order if none is set
            if (empty($settings['service_order']) && empty($this->config[$this->site_id]['service_order']) || ($settings['service_order'] !== $this->service_order)) {
                $settings['service_order'] = $this->get_service_order();
            }
        }
        // ee()->dbg->c_log($settings, __METHOD__);

        return $settings;
    }

    public function save_settings()
    {
        $settings = $this->get_settings(true);
        $current_service = '';
        ee()->dbg->c_log($settings, __METHOD__);
        foreach ($this->services as $service => $service_settings) {
            // ee()->dbg->c_log($service, __METHOD__);
            $v = ee('Request')->post($service.'_active');
            if (! is_null($v)) {
                $current_service = $service;
                $settings[$this->site_id][$service.'_active'] = $v; 

                foreach ($service_settings as $setting) {
                    $settings[$this->site_id][$setting] = ee('Request')->post($setting);
                }
            }
        }

        ee()->dbg->c_log($settings, __METHOD__);
        $this->model->settings = $settings;
        $this->model->save();
        ee()->dbg->c_log("$current_service : ".json_encode($settings), __METHOD__);
        ee('CP/Alert')->makeInline()
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), EXT_NAME))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/'.EXT_SHORT_NAME.'/services/'.$current_service));
    }

    // public function update_service_order($settings = null)
    // {
        
    //     $settings = (!is_null($settings)) ? $settings : $this->get_settings();
    //     ee()->dbg->c_log($settings, __METHOD__);
    //     if ($services = ee('Request')->post('service_order')) {
    //         $settings[$this->site_id]['service_order'] = explode(',', $services);
    //         $this->model->settings = $settings;

    //         $this->model->save();
    //         ee()->dbg->c_log("$current_service : ".json_encode($settings), __METHOD__);
    //         // exit();
    //     }
    //     ee()->dbg->c_log($settings, __METHOD__);

    //     return $settings['service_order'];
    // }

    public function get_service_order()
    {
        $all_settings = $this->model->settings;
        $settings = (empty($all_settings)) ? $all_settings : $all_settings[$this->site_id];

        $active_services = $this->get_active_services();
               

        if (empty($settings['service_order']) && empty($this->config[$this->site_id]['service_order']) ) {
            return array_keys($this->services); 
        
        } else {
            $other_services = array_diff(array_keys($this->services), $active_services); 
            ee()->dbg->c_log($other_services, __METHOD__);
            foreach ($other_services as $service) {
                $active_services[] = $service;
            }
            // if ($active_services !== $settings['service_order']) {
            //     $this->model->settings = $settings;
            //     $this->model->save();
            // }

            return $active_services;
        }
    }

    public function get_initial_service()
    {
        $active = $this->get_active_services();
        return  (empty($active)) ? null : $active[0];
    }

    public function get_active_services()
    {
        $active = array();
        $current_settings = (isset($this->model->settings[$this->site_id])) ? $this->model->settings[$this->site_id] : $this->model->settings;
        $active_services = array_filter($current_settings, function ($v, $k) {
            return $v == 'y' && strstr($k, '_active') !== false;
        }, ARRAY_FILTER_USE_BOTH);

        $active = array_map(function ($k) {
            return explode('_', $k)[0];
        }, array_keys($active_services));

        return array_unique($active);
    }

    private function _get_service_detail()
    {
        $settings = (isset($this->model->settings[$this->site_id])) ? $this->model->settings[$this->site_id] : $this->model->settings; //$this->get_settings();
        $sections = array(
            array(
                'title' => lang('description'),
                'fields' => array(
                    'description' => array(
                        'type' => 'html',
                        'content' => "<div class='".EXT_SHORT_NAME."-service-description'>".lang(''.$this->current_service.'_description').'</div>', ),
                        $this->current_service.'_active' => array(
                        'type' => 'inline_radio',
                        'choices' => array(
                            'y' => lang('enabled'),
                            'n' => lang('disabled'),
                        ),
                        'value' => (!empty($settings[$this->current_service.'_active']) && $settings[$this->current_service.'_active'] == 'y') ? 'y' : 'n',
                    ),
                ),
            ),
        );

        if (array_key_exists($this->current_service, $this->services)) {
            // ee()->dbg->c_log($this->services, __METHOD__);
            foreach ($this->services[$this->current_service] as $field_name) {
                $i = $this->_getServiceFields($field_name);
                // ee()->dbg->c_log($i);
                extract($i, EXTR_OVERWRITE);
                $field = array('type' => $control_type);
                switch ($control_type) {
                    case 'file':
                    case 'image':
                        $filepicker = ee('CP/FilePicker')->make();
                        $link = $filepicker->getLink($field_name);
                        $field = array_merge($field, array(
                            'value' => $link->render(),
                        ));
                        break;
                    case 'select':
                    case 'dropdown':
                    case 'radio':
                    case 'yes_no':
                    case 'checkbox':
                    case 'inline_radio':

                        $choices = (is_array($choice_options)) ? $choice_options[key($choice_options)] : $enabled_disabled;
                        $field = array_merge($field, array(
                            'choices' => $choices,
                            'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
                        ));
                        break;
                    case 'textarea':
                    case 'short-text':
                        $field = array_merge($field, array('value' => (!empty($settings[$field_name])) ? $settings[$field_name] : ''));
                        break;
                    default:
                        $field = array(
                            'type' => 'text',
                            'value' => (!empty($settings[$field_name])) ? $settings[$field_name] : '',
                        );
                    }

                $sections[] = array(
                        'title' => lang(''.$field_name),
                        'desc' => (in_array($field_name, array('mandrill_test_api_key', 'mandrill_subaccount'))) ? lang('optional') : '',
                        'fields' => array(
                            $field_name => $field,
                        ),
                    );
            }
        }
        // ee()->dbg->c_log($sections, __METHOD__);
        return array($sections);
    }

    private function _getServiceFields($field_name, $type = 'text')
    {
        $info = array();
        $is_multi_choice = is_array($field_name);
        $choice_options = false;
        if ($is_multi_choice) {
            $choice_options = $field_name;
            $field_name = array_keys($field_name)[0];
            ee()->dbg->c_log($choice_options, __METHOD__);
        }

        $is_control = strpos($field_name, '__');

        if ($is_control !== false) {
            $type = substr($field_name, ($is_control + 2));
            // ee()->dbg->c_log("$field_name ( $is_control ) :  $type", __METHOD__);
        }

        return array(
            'field_name' => $field_name,
            'choice_options' => $choice_options,
            'control_type' => $type,
            'enabled_disabled' => $enabled_disabled = array(
                'y' => lang('enabled'),
                'n' => lang('disabled'),
            ),
        );
    }

    public function viewDbg(&$vars)
    {
        // if ($this->debug){
        // add any accumalated debug messages
        $content = $this->dbg_msgs->data;

        // add messages to page
        ee()->load->helper('html');
        foreach ($this->dbg_msgs as $msg) {
            $vars['form_vars']['extra_alerts'][] = array('config_vars');
            ee('CP/Alert')->makeInline($msg->title)
                    ->asAttention()
                    ->withTitle($msg->title)
                    ->addToBody($msg->msg)
                    ->canClose()
                    ->now();
        }
        // }
    }

    public function ee_version()
    {
        return substr(APP_VER, 0, 1);
    }
}
