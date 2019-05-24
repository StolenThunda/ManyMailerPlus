<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Manymailerplus_mcp
{
    private $version = EXT_VERSION;
    private $attachments = array();
    private $csv_lookup = array();
    private $debug = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $CI = ee();
        // $this->debug = true;
        $this->library_config = array(
            'debug' => $this->debug,
        );
        ee()->extensions->end_script = true;
        if (!ee()->cp->allowed_group('can_access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
        ee()->config->load('compose_js');

        $internal_js = ee()->config->item('internal_js');
        foreach ($internal_js as $js) {
            ee()->cp->load_package_js($js);
        }
        $external_js = ee()->config->item('external_js');
        foreach ($external_js as $script) {
            ee()->cp->add_to_foot($script);
        }
        ee()->load->library('debughelper', $this->library_config, 'dbg');
        ee()->load->library('services_module', $this->library_config, 'mod_svc');
        ee()->load->library('composer', array_merge($this->library_config, array('service' => ee()->mod_svc->get_initial_service())), 'mail_funcs');
        ee()->load->helper('html');
        $this->services = ee()->config->item('services', 'services');
        $this->sidebar_loaded = ee()->config->load('sidebar', true, true);
        $this->sidebar_options = ee()->config->item('options', 'sidebar');
        $this->_update_sidebar_options(ee()->mod_svc->get_settings()['service_order']);

        if (!$this->sidebar_loaded) {
            //render page to show errors
            $vars = array(
                'base_url' => ee('CP/URL', EXT_SETTINGS_PATH),
                'cp_page_title' => lang(EXT_SHORT_NAME),
                'save_btn_text' => 'btn_save_settings',
                'sections' => array(),
                'save_btn_text_working' => 'btn_saving',
            );

            return ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($vars);
        } else {
            $this->makeSidebar();
        }
        $this->vars = array(
            'settings' => ee()->mod_svc->get_settings(),
            'services' => $this->services,
            'sidebar' => $this->sidebar_options,
            'sections' => array(),
            'save_btn_text' => '',
            'save_btn_text_working' => '',
            'categories' => array_keys($this->sidebar_options),
            'active_service_names' => ee()->mod_svc->get_active_services(),
        );
    }

    public function makeSidebar()
    {
        $this->sidebar = ee('CP/Sidebar')->make();
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

    public function _update_sidebar_options($additional_links = array())
    {
        if (!empty($additional_links)) {
            if (array_key_exists('services', $this->sidebar_options)) {
                $this->sidebar_options['services']['links'] = array_unique(array_merge($this->sidebar_options['services']['links'], $additional_links));
            }
        }
    }

    public function index()
    {
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->vars['current_action'] = __FUNCTION__;
        $this->vars['breadcrumb'] = ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile();
        ee()->dbg->console_message($this->vars, __METHOD__);

        return  array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($this->vars),
            'breadcrumb' => array(
                $this->vars['breadcrumb'] => EXT_NAME,
            ),
            'heading' => $this->vars['cp_page_title'],
        );
    }

    public function email($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/email')->compile() => lang('email_title'),
        );
        $id = ee()->uri->segment(7, '');
        switch ($func) {
            case 'resend':
            case 'batch':
                return ee()->mail_funcs->{$func}($id);
                break;
            case 'compose2':
                return ee()->mail_funcs->{$func}();
                break;
            case 'edit_template':
                if ($id != '') {
                    $this->vars = ee()->mail_funcs->{$func}($id);
                    break;
                }
                // no break
            case 'compose':
            case 'send':
            case 'sent':
            case 'view_templates':
            case 'save_template':
            case 'delete_template':
                $this->vars = ee()->mail_funcs->{$func}();
                break;
            default:
                array_pop($breadcrumbs);
                $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email');
                $this->vars['cp_page_title'] = lang('email_title');
                $this->vars['current_action'] = 'email';
        }
        ee()->dbg->console_message($this->vars, __METHOD__);

        return array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($this->vars),
            'breadcrumb' => $breadcrumbs,
            'heading' => $this->vars['cp_page_title'],
            );
    }

    public function services($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/services')->compile() => lang('services'),
        );
        $this->vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/services');
        $this->vars['cp_page_title'] = lang('services');
        $this->vars['current_action'] = $func;
        switch ($func) {
            case 'list':
                $this->vars['settings'] = ee()->mod_svc->get_settings();
               break;
            case 'save':
                return ee()->mod_svc->save_settings();
                break;
            case '':
            case in_array($func, array_values($this->sidebar_options['services']['links'])):
                // if the current = the service detail page
                $this->vars = array_merge($this->vars, ee()->mod_svc->settings_form(array()));
                break;
            default:
                $this->vars['settings'] = ee()->mod_svc->{$func}();
                break;
        }
        if (!isset($this->vars['current_service'])) {
            array_pop($breadcrumbs);
        }
        $this->vars['active_service_names'] = ee()->mod_svc->get_active_services();
        ee()->dbg->console_message($this->vars, __METHOD__);

        return array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':compose_view')->render($this->vars),
            'breadcrumb' => $breadcrumbs,
            'heading' => $this->vars['cp_page_title'],
        );
    }
}
// END CLASS
