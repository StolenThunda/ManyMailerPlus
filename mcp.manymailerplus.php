<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Manymailerplus_mcp
{
    private $vars = array();
    private $config =  array('debug' => TRUE);

    /**
     * Constructor.
     */
    public function __construct()
    {
        $CI = ee();
        ee()->extensions->end_script = true;
        if (!ee()->cp->allowed_group('can_access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
        
        $this->_load_libs();
        $this->_load_configs();
        $this->_js_config_autoload();        
        $this->_update_service_options(array_keys($this->services));

        if (!$this->sidebar_loaded) {
            //render page to show errors
            $vars = array(
                'base_url' => ee('CP/URL', EXT_SETTINGS_PATH),
                'cp_page_title' => lang(EXT_SHORT_NAME),
                'save_btn_text' => 'btn_save_settings',
                'sections' => array(),
                'save_btn_text_working' => 'btn_saving',
            );

            return ee('View')->make(EXT_SHORT_NAME.':'.$this->view)->render($vars);
        }
        $this->vars['save_btn_text'] = '';
        $this->vars['save_btn_text_working'] = '';
        $this->vars['sections'] = array();
        $this->vars['sidebar'] = array_keys($this->sidebar_options);
        $this->vars['services'] = $this->services;
        $this->vars['view'] = 'compose_view';
        $this->makeSidebar();
    }

    public function _load_libs(){
        ee()->load->helper('html');
        ee()->load->library('debughelper', $this->config, 'dbg');
        ee()->load->library('services_module', $this->config, 'mail_svc');
        ee()->load->library('composer', $this->config, 'mail_funcs');
    }
    public function _load_configs(){
        $this->services = ee()->config->item('services', 'services');
        $this->sidebar_loaded = ee()->config->load('sidebar', true, true);
        $this->sidebar_options = ee()->config->item('options', 'sidebar');
    }
    public function _js_config_autoload(){
        ee()->config->load('compose_js');

        $internal_js = ee()->config->item('internal_js');
        foreach ($internal_js as $js) {
            ee()->cp->load_package_js($js);
            // $path = ee('CP/URL', EXT_SETTINGS_PATH.'/javascript/'.$js.'.js');           
            // $script = "<script src='".$path."' type='module'></script>";
            // ee()->dbg->c_log($script, __METHOD__, true);
            // ee()->cp->add_to_foot($script);
        }
        $external_js = ee()->config->item('external_js');
        foreach ($external_js as $script) {
            ee()->cp->add_to_foot($script);
        }

    }
    public function makeSidebar()
    {
        if (!isset($this->sidebar)) {
            $this->sidebar = ee('CP/Sidebar')->make();
        }
        foreach (array_keys($this->sidebar_options) as $category) {
            $left_nav = $this->sidebar->addHeader(lang("{$category}_title"), ee('CP/URL', EXT_SETTINGS_PATH.'/'.$category));
            if (isset($this->sidebar_options[$category]['links']) and count($this->sidebar_options[$category]['links']) > 0) {
                $list_items = $left_nav->addBasicList();
                foreach ($this->sidebar_options[$category]['links'] as $link_text) {
                    $list_items->addItem(lang(''.$link_text.'_name'), ee('CP/URL', EXT_SETTINGS_PATH.'/'.$category.'/'.$link_text));
                }
            }
        }
    }

    public function _update_service_options($additional_links = array())
    {
        if (!empty($additional_links)) {
            $this->sidebar_options['services']['links'] = array_unique(array_merge($this->sidebar_options['services']['links'], ee()->mail_svc->get_service_order()));
        }
        // ee()->dbg->c_log($this->sidebar_options['services']['links'], __METHOD__);
    }

    public function index()
    {
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->vars['current_action'] = __FUNCTION__;
        $this->vars['breadcrumb'] = ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile();

        return $this->view_page();
    }

    public function email($func = '')
    {
        ee()->dbg->c_log($this->vars, __METHOD__);
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/email')->compile() => lang('email_title'),
        );
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email');
        $this->vars['cp_page_title'] = lang('email_title');
        $id = ee()->uri->segment(7, '');
        switch ($func) {
            case 'view_templates':
                $this->vars = array_merge($this->vars, ee()->mail_funcs->{$func}());
                break;
            case 'compose2':
                $this->vars['view'] = 'compose_view2';
                // no break
            case 'compose':
            case 'send':
            case 'sent':
            case 'resend':
            case 'batch':
            case 'edit_template':
                if ($id != '') {
                    $this->vars = array_merge($this->vars, ee()->mail_funcs->{$func}($id));
                    break;
                }
                // no break
            case 'save_template':
            case 'delete_template':
                $this->vars = array_merge($this->vars, ee()->mail_funcs->{$func}());
                // no break
            default:
                $this->vars['current_action'] = 'email';
                array_pop($breadcrumbs);
        }
        ee()->dbg->c_log($this->vars, __METHOD__);

        return $this->view_page($breadcrumbs);
    }

    public function services($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/services')->compile() => lang('services'),
        );
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->vars['current_action'] = __FUNCTION__;
        switch ($func) {
            case 'save':
                return ee()->mail_svc->save_settings();
            // case 'update_service_order':
            case 'get_settings':
            case 'get_service_order':
            case 'get_active_services':
            case 'get_initial_service':
                return ee()->output->send_ajax_response(ee()->mail_svc->{$func}());
                // ee()->functions->redirect($_SERVER['HTTP_REFERER']);
            default:
                // if the current = the service detail page
                $this->vars = array_merge($this->vars, ee()->mail_svc->settings_form(array()));
                break;
        }
        if (!isset($this->vars['current_service'])) {
            array_pop($breadcrumbs);
        }
        $this->vars['active_service_names'] = json_encode(ee()->mail_svc->get_active_services(), 1);

        return $this->view_page($breadcrumbs);
    }

    public function settings($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/settings')->compile() => lang('settings'),
        );
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->vars['current_action'] = __FUNCTION__;
        switch ($func) {
            
            default:
                // if the current = the service detail page
                $this->vars = array_merge($this->vars, ee()->mail_opts->{$func}());
                break;
        }
        return $this->view_page($breadcrumbs);
    }
    public function view_page($breadcrumbs = null)
    {
        $breadcrumbs = (is_null($breadcrumbs) ? array(
            $this->vars['breadcrumb'] => EXT_NAME,
        ) : $breadcrumbs);

        $return = array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':'.$this->vars['view'])->render($this->vars),
            'breadcrumb' => $breadcrumbs,
            'heading' => $this->vars['cp_page_title'],
        );
        ee()->dbg->c_log($this->vars, __METHOD__);

        return $return;
    }
}
// END CLASS
