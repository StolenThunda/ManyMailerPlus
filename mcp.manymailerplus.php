<?php
/**
 * MANYMAILERPLUS
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * Manymailerplus_mcp class
 *
 * @category Bootstrap_For_MMP
 * @package  Category
 * @author   Tony Moses <tonymoses@texasbluesalley.com>
 * @license  MIT http://url.com
 * @link     http://url.com
 */
class Manymailerplus_mcp
{
    private $_vars = array();
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
         $this->model = ee('Model')->get('Extension')
             ->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
             ->first();
        $this->_config =  array('debug' => ($this->model->settings['debug_mode'] === 'y'));
        $this
            ->_loadLibs()
            ->_loadConfigs()
            ->_jsConfigAutoload()
            ->_updateServiceOptions(array_keys($this->services));
        
        if (!$this->sidebar_loaded) {
            //render page to show errors
            $_vars = array(
                'base_url' => ee('CP/URL', EXT_SETTINGS_PATH),
                'cp_page_title' => lang(EXT_SHORT_NAME),
                'save_btn_text' => 'btn_save_settings',
                'sections' => array(),
                'save_btn_text_working' => 'btn_saving',
            );

            return ee('View')->make(EXT_SHORT_NAME.':'.$this->view)->render($vars);
        }
       
        ee()->dbg->c_log($this->model->settings, __METHOD__);    
        $this->_vars['save_btn_text'] = '';
        $this->_vars['save_btn_text_working'] = '';
        $this->_vars['sections'] = array();
        $this->_vars['sidebar'] = array_keys($this->sidebar_options);
        $this->_vars['services'] = $this->services;
        $this->_vars['view'] = 'compose_view';
        $this->_makeSidebar();
    }

    /**
     * Loads all helpers
     *
     * @return void
     */
    private function _loadLibs()
    {
        ee()->load->helper('html');
        ee()->load->library('debughelper', $this->_config, 'dbg');
        ee()->load->library('services_module', $this->_config, 'mail_svc');
        ee()->load->library('composer', $this->_config, 'mail_funcs');
        ee()->load->library('settings', $this->_config, 'mail_settings');
        // ee()->load->driver('txservice/txservice');
        return $this;
    }
    /**
     * Loads configs
     *
     * @return void
     */
    private function _loadConfigs()
    {
        $this->services = ee()->config->item('services', 'services');
        $this->sidebar_loaded = ee()->config->load('sidebar', true, true);
        $this->sidebar_options = ee()->config->item('options', 'sidebar');
        return $this;

    }
    /**
     * Ensures proper js injection
     *
     * @return void
     */
    private function _jsConfigAutoload()
    {
        ee()->config->load('compose_js');

        $internal_js = ee()->config->item('internal_js');
        foreach ($internal_js as $js) {
            ee()->cp->load_package_js($js);
        }
        $external_js = ee()->config->item('external_js');
        foreach ($external_js as $script) {
            ee()->cp->add_to_foot($script);
        }
        return $this;
    }
    /**
     * Make the sidebar using config files
     *
     * @return void
     */
    private function _makeSidebar()
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

    /**
     * Url construction for sidebar links
     *
     * @param array $additional_links List array of names for sidebar links
     *
     * @return void
     */
    private function _updateServiceOptions($additional_links = array())
    {
        if (!empty($additional_links)) {
            $this->sidebar_options['services']['links'] = array_unique(array_merge($this->sidebar_options['services']['links'], ee()->mail_svc->get_service_order()));
        }
        return $this;
        // ee()->dbg->c_log($this->sidebar_options['services']['links'], __METHOD__);
    }

    /**
     * home page
     *
     * @return void
     */
    public function index()
    {
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->_vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->_vars['current_action'] = __FUNCTION__;
        $this->_vars['breadcrumb'] = ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile();

        return $this->view_page();
    }

    /**
     * All Email Related pages
     *
     * @param string $func action from url
     *
     * @return void
     */
    public function email($func = '')
    {
        ee()->dbg->c_log($this->_vars, __METHOD__);
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/email')->compile() => lang('email_title'),
        );
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email');
        $this->_vars['cp_page_title'] = lang('email_title');
        $id = ee()->uri->segment(7, '');
        switch ($func) {
        case 'get_template_view':
            return ee()->mail_funcs->{$func}();
        case 'view_templates':
            $this->_vars = array_merge($this->_vars, ee()->mail_funcs->{$func}());
            break;
        case 'compose2':
            $this->_vars['view'] = 'compose_view2';
            // no break
        case 'compose':
        case 'send':
        case 'sent':
        case 'resend':
        case 'batch':
        case 'edit_template':
            if ($id != '') {
                $this->_vars = array_merge($this->_vars, ee()->mail_funcs->{$func}($id));
                break;
            }
            // no break
        case 'save_template':
        case 'delete_template':
            $this->_vars = array_merge($this->_vars, ee()->mail_funcs->{$func}());
            // no break
        default:
            $this->_vars['current_action'] = 'email';
            array_pop($breadcrumbs);
        }
        ee()->dbg->c_log($this->_vars, __METHOD__);

        return $this->view_page($breadcrumbs);
    }

    /**
     * All services related functions
     *
     * @param string $func Url fragment
     *
     * @return void
     */
    public function services($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/services')->compile() => lang('services'),
        );
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->_vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->_vars['current_action'] = __FUNCTION__;
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
            $this->_vars['current_action'] = 'settings';
            array_pop($breadcrumbs);
            // if the current = the service detail page
            $this->_vars = array_merge($this->_vars, ee()->mail_svc->settings_form(array()));
            break;
        }
        if (!isset($this->_vars['current_service'])) {
            array_pop($breadcrumbs);
        }
        return $this->view_page($breadcrumbs);
    }

    /**
     * Handle all settings functionality
     *
     * @param string $func Url Frag
     *
     * @return void
     */
    public function settings($func = '')
    {
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/settings')->compile() => lang('settings'),
        );
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->_vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        $this->_vars['current_action'] = __FUNCTION__;
       
        switch ($func) {
        case 'return_settings':
            return ee()->output->send_ajax_response(ee()->mail_settings->{$func}());
            // ee()->functions->redirect($_SERVER['HTTP_REFERER']);
        case 'save_settings':
            ee()->mail_settings->save_settings(ee()->mail_svc->get_settings()); 
            break;           
        default:
            $this->_vars = array_merge($this->_vars, ee()->mail_settings->settings());
        
        }
        ee()->dbg->c_log($this->_vars, __METHOD__);
        return $this->view_page($breadcrumbs);
    }
    /**
     * Renderer function for all pages
     *
     * @param [type] $breadcrumbs
     * @return void
     */
    public function view_page($breadcrumbs = null)
    {
        $breadcrumbs = (is_null($breadcrumbs) ? array(
            $this->_vars['breadcrumb'] => EXT_NAME,
        ) : $breadcrumbs);

        $return = array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':'.$this->_vars['view'])->render($this->_vars),
            'breadcrumb' => $breadcrumbs,
            'heading' => $this->_vars['cp_page_title'],
        );
        ee()->dbg->c_log($this->_vars, __METHOD__);

        return $return;
    }
}
// END CLASS
