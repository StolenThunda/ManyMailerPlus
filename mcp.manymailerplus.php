<?php
/**
 * MANYMAILERPLUS
 *
 * @category Bootstrap_For_MMP
 * @package  Category
 * @author   Tony Moses <tonymoses@texasbluesalley.com>
 * @license  MIT http://url.com
 * @link     http://url.com
 */
namespace ManyMailerPlus;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * Manymailerplus_mcp class
 *
 */
class Manymailerplus_mcp
{
    use libraries\Utility_Functions;
    private $_vars = array();
    /**
     * Constructor.
     */
    public function __construct()
    {
        $CI = ee();
        // ini_set('memory_limit', '2G');
        ee()->extensions->end_script = true;
        if (!ee()->cp->allowed_group('can_access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
        $this
            ->_loadLibs()
            ->_loadConfigs()
            ->_jsConfigAutoload()
            ->_updateServiceOptions()
            ->_makeSidebar();

        $current_settings = $this->u_getCurrentSettings();
        $service_list = isset($current_settings['service_order']) ? $current_settings['service_order'] : array_keys($this->services);
        
        $this->_vars = array(
            'save_btn_text' => '',
            'save_btn_text_working' => '',
            'sections' => array(),
            'sidebar' => array_keys($this->sidebar_options),
            'services' => $service_list,
            'view' => 'compose_view'
        );
        if (!$this->sidebar_loaded) {
            //render page to show errors
            $this->_vars = array(
                'base_url' => ee('CP/URL', EXT_SETTINGS_PATH),
                'cp_page_title' => lang(EXT_SHORT_NAME),
                'save_btn_text' => 'btn_save_settings',
                'sections' => array(),
                'save_btn_text_working' => 'btn_saving',
            );

            return ee('View')->make(EXT_SHORT_NAME.':'. $this->view)->render($this->_vars);
        }
    }

    /**
     * Loads all helpers
     *
     * @return void
     */
    private function _loadLibs()
    {
        ee()->load->helper('html');
        ee()->load->library('debughelper', array(), 'dbg');
        ee()->load->library('services', array(), 'mail_svc');
        ee()->load->library('composer', array(), 'mail_funcs');
        ee()->load->library('settings', array(), 'mail_settings');
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
        $this->sidebar_loaded = ee()->config->load('sidebar', true);
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
        ee()->cp->add_to_foot(
            link_tag(
                array(
                    'href' => 'https://cdn.datatables.net/v/dt/dt-1.10.20/b-1.6.1/b-colvis-1.6.1/fc-3.3.0/fh-3.1.6/r-2.2.3/sc-2.0.1/datatables.min.css',
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                )
            )
        );
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
        }
        $this->sidebar = ee('CP/Sidebar')->make();
        foreach (array_keys($this->sidebar_options) as $category) {
            $left_nav = $this->sidebar->addHeader(lang("{$category}_title"), ee('CP/URL', EXT_SETTINGS_PATH.'/'.$category));
            if (isset($this->sidebar_options[$category]['links']) and count($this->sidebar_options[$category]['links']) > 0) {
                $list_items = $left_nav->addBasicList();
                foreach ($this->sidebar_options[$category]['links'] as $link_text) {
                    $list_items->addItem(lang(''. $link_text .'_name'), ee('CP/URL', EXT_SETTINGS_PATH.'/'.$category.'/'.$link_text));
                }
            }
        }
        return $this;
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
        if (empty($additional_links)) {
            $current_settings = $this->u_getCurrentSettings();
            $additional_links =  isset($current_settings['service_order'])  ? $current_settings['service_order'] : array_keys($this->services);
        }
        $link_list = array_unique(
            array_merge(
                $this->sidebar_options['services']['links'],
                ee()->mail_svc->get_service_order()
            )
        );
        $this->sidebar_options['services']['links'] =  $link_list;
        return $this;
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
        // ee()->dbg->c_log($this->_vars, __METHOD__ . '  ' . __LINE__);
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/email')->compile() => lang('email_title'),
        );
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email');
        $this->_vars['cp_page_title'] = lang('email_title');
        $id = ee()->uri->segment(7, '');
        switch ($func) {
        case 'mail_progress':
            echo(ee()->output->send_ajax_response(ee()->mail_funcs->{$func}()));
            exit;
        case 'getTemplateView':
            return ee()->mail_funcs->{$func}();
        case 'compose2':
            $this->_vars['view'] = 'compose_view2';
            // no break
        case 'edit_template':
        case 'compose':
        case 'send':
        case 'sent':
        case 'resend':
        case 'batch':
        case 'edit_template':
        case 'saveTemplate':
        case 'view_templates':
        case 'delete_template':
            if ($id != '') {
                $this->_vars = array_merge($this->_vars, ee()->mail_funcs->{$func}($id));
                break;
            }
            $this->_vars = array_merge($this->_vars, ee()->mail_funcs->{$func}());
        break;
        default:
            $this->_vars['current_action'] = 'email';
            array_pop($breadcrumbs);
        }
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
        $result = array();
        $breadcrumbs = array(
            ee('CP/URL')->make(EXT_SETTINGS_PATH)->compile() => EXT_NAME,
            ee('CP/URL')->make(EXT_SETTINGS_PATH.'/'.__FUNCTION__)->compile() => lang(__FUNCTION__),
        );
        $this->_vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/'.__FUNCTION__);
        $this->_vars['cp_page_title'] = lang(__FUNCTION__.'_title');
        switch ($func) {
        case 'save':
            ee()->mail_svc->save_settings();
            return false;
        case 'update_service_order':
        case 'get_settings':
        case 'get_active_services':
        case 'get_initial_service':
        case 'get_service_order':
            $result =  ee()->output->send_ajax_response(ee()->mail_svc->{$func}());
            break;
        default:
            $this->_vars['current_action'] = 'services';
            // if the current = the service detail page
            $this->_vars = array_merge($this->_vars, ee()->mail_svc->settings_form(array()));
            break;
        }
        if (!empty($result)) {
            $this->_makeSidebar();
            return $result;
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
        case 'get_settings':
            return ee()->output->send_ajax_response(ee()->mail_settings->{$func}());
        case 'save_settings':
            return ee()->mail_settings->save_settings(ee()->mail_svc->get_settings());
        case 'reset_settings':
            $this->_vars = array_merge($this->_vars, ee()->mail_settings->{$func}());
            break;
        default:
            $this->_vars = array_merge($this->_vars, ee()->mail_settings->settings());
        
        }
        // ee()->dbg->c_log($this->_vars, __METHOD__ . '  ' . __LINE__);
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
        $this->_vars['active_service_names'] = json_encode(ee()->mail_svc->get_active_services(), 1);
        $breadcrumbs = (is_null($breadcrumbs) ? array(
            $this->_vars['breadcrumb'] => EXT_NAME,
        ) : $breadcrumbs);

        $return = array(
            'body' => ee('View')->make(EXT_SHORT_NAME.':'.$this->_vars['view'])->render($this->_vars),
            'breadcrumb' => $breadcrumbs,
            'heading' => $this->_vars['cp_page_title'],
        );
        ee()->dbg->c_log($this->_vars, __METHOD__ . ' View Page ' . __LINE__);
        ob_end_clean();
        return $return;
    }

    public function ucArray(&$list)
    {
        $list = ucfirt($list);
    }
}
// END CLASS
