<?php
use ManyMailerPlus\libraries;
class TxService_Mandrill extends libraries\TxService\TxService
{
    use libraries\Utility_Functions;
    private $_apiKey = "";
    public function __construct($settings = array())
    {
        $this->debug = $this->u_debug_enabled();
        $this->settings = $settings;
        ee()->dbg->c_log($this->getApiKey(), __METHOD__);
    }

    public function getApiKey()
    {
        $settings = $this->u_getCurrentSettings();
        ee()->dbg->c_log($settings, __METHOD__);
        $key = (!empty($settings['mandrill_api_key'])) ? $settings['mandrill_api_key'] : '';
        $test_key = (!empty($settings['mandrill_test_api_key'])) ? $settings['mandrill_test_api_key'] : '';
        $test_mode = (isset($settings['mandrill_testmode__yes_no']) && $settings['mandrill_testmode__yes_no'] == 'y');
        $active_key = ($test_mode && $test_key !== '') ? $test_key : $key;
        // ee()->dbg->c_log($settings, __METHOD__, true);
        $this->setApiKey($active_key);
        return $this->_apiKey;
    }
    public function setApikey($key)
    {
        $this->_apiKey = $key;
    }
    public function ident()
    {
        ee()->dbg->c_log('Loaded: '.__CLASS__.' k:'.$this->getApiKey(), __METHOD__);
    }
    public function sendEmail($email = array())
    {
        ee()->dbg->c_log($email, __METHOD__);
        $sent = false;
        $missing_credentials = true;
        
        if (!empty($email)) {
            if ($this->getApiKey() !== '') {
                $missing_credentials = false;
                $subaccount = (!empty($this->settings['mandrill_subaccount']) ? $this->settings['mandrill_subaccount'] : '');
                $sent = $this->_send_email($email);
            }
        }
        ee()->dbg->c_log(array('missing_credentials' => $missing_credentials, 'sent' => $sent), __METHOD__);
        return array('missing_credentials' => $missing_credentials, 'sent' => $sent);
    }

    /**
        Sending methods for each of our services follow.
     **/
    public function _send_email($email)
    {
        $content = array(
            'key' => $this->_apiKey,
            'async' => true,
            'message' => $email,
        );
        //ee()->dbg->c_log($content, __METHOD__);
        // print_r($content);
        
        if (isset($content['message']['extras'])) {
            if (isset($content['message']['extras']['from_name'])) {
                $content['message']['from_name'] = $content['message']['extras']['from_name'];
            }
            if (isset($content['message']['extras']['template_name'])) {
                $content['template_name'] = $content['message']['extras']['template_name'];
            }
            $editables = array_filter(
                $content['message']['extras'],
                function ($v, $k) {
                    return 'mc-check_' == substr($k, 0, strlen('mc-check_'));
                },
                ARRAY_FILTER_USE_BOTH
            );
            if (count($editables) > 0) {
                $body_field = substr(array_keys($editables)[0], strlen('mc-check_')) || array();
            }
            

            if (isset($content['message']['extras']['mc-edit'])) {
                $t_content = array();
                $edits = $content['message']['extras']['mc-edit'];
                foreach ($edits as $k => $v) {
                    // $default = in_array($k, array('main', 'content', 'bod_content'));
                    // $chosen = ($k === $body_field);
                    // if ($chosen || $default) {
                    //     $message = $content['message']['html'];
                    //     $v = ($message !== '') ? $message : $v;
                    //     ee()->dbg->c_log($v, __METHOD__);
                    // }
                    array_push($t_content, array('name' => $k, 'content' => $v));
                }
                $content['template_content'] = $t_content;
            }
        }
        
        
        // ee()->dbg->c_log($content, __METHOD__);
        // if (!empty($subaccount)) {
        //     $content['message']['subaccount'] = $subaccount;
        // }

        $content['message']['from_email'] = $content['message']['from']['email'];
        if (!empty($content['message']['from']['name'])) {
            $content['message']['from_name'] = $content['message']['from']['name'];
        }
        unset($content['message']['from']);

        $mandrill_to = array('email' => $content['message']['to']);
        foreach ($content['message']['to'] as $to) {
            $mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'to'));
        }

        if (!empty($content['message']['cc'])) {
            foreach ($content['message']['cc'] as $to) {
                $mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'cc'));
            }
            unset($content['message']['cc']);
        }

        if (!empty($content['message']['reply-to'])) {
            $content['message']['headers']['Reply-To'] = $this->_recipient_str($content['message']['reply-to'], true);
        }
        unset($content['message']['reply-to']);

        if (!empty($content['message']['bcc'])) {
            foreach ($content['message']['bcc'] as $to) {
                $mandrill_to[] = array_merge($this->_name_and_email($to), array('type' => 'bcc'));
            }
        }
        unset($content['message']['bcc']);

        $content['message']['to'] = $mandrill_to;

        $content['message']['merge_language'] = 'handlebars';

        $content['message']['track_opens'] = true;

        $content['message']['tags'][] = EXT_NAME.' '.EXT_VERSION;

        $merge_vars = array(
            array(
                'rcpt' => $content['message']['to'][0]['email'],
                'vars' => $this->lookup_to_merger($content['message']['lookup']),
            ),
        );
        unset($content['message']['lookup']);

        $content['message']['auto_text'] = true;
        $content['message']['merge_vars'] = $merge_vars;

        if (ee()->extensions->active_hook('pre_send')) {
            $content = ee()->extensions->call('pre_send', 'mandrill', $content);
        }

        // Did someone set a template? Then we need a different API method.
        $method = (!empty($content['template_name']) && !empty($content['template_content'])) ? 'send-template' : 'send';
        
        $content = json_encode($content);
        
        
        // ee()->dbg->c_log($content, __METHOD__, true);
        //TODO: save email data to table
        // ee()->logger->developer($content);
        return $this->curlRequest('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', array(), $content, true);
    }

    public function lookup_to_merger($lookup)
    {
        $merge_vars = array();
        foreach (array_keys($lookup) as $key) {
            $merge_vars[] = array(
                'name' => str_replace(array('{{', '}}'), '', $key),
                'content' => $lookup[$key],
            );
        }

        return $merge_vars;
    }
    
    public function getTemplate($obj = array('template_name' => '', 'func' => 'list'))
    {
        $obj[0]['template_name'] = str_replace(' ', '-', strtolower($obj[0]['template_name']));
        $req = $obj[0];
        ee()->dbg->c_log($req, __METHOD__);
        return $this->getTemplates($req);
    }
    public function getTemplates($obj = array('template_name' => '', 'func' => 'list'))
    {
        ee()->dbg->c_log($obj, __METHOD__);
        $templates = array();
        $func = (isset($obj['func'])) ? $obj['func'] : 'list';
        $template_name = (isset($obj['template_name'])) ? $obj['template_name'] : null;
        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/'.$func.'.json';
        $key = $this->getApiKey();
        if ($key !== '') {
            $data = array(
                'key' => $key,
            );
            if (!is_null($template_name)) {
                $data['name'] = $template_name;
            }
            $content = json_encode($data);
            ee()->dbg->c_log($api_endpoint.$content, __METHOD__);
            $templates = $this->curlRequest($api_endpoint, array(), $content, true);
            ee()->dbg->c_log($templates, __METHOD__);
        }

        return $templates;
    }

    public function saveTemplate()
    {
        $form_fields = array(
            'created_at_hidden',
            'orig_template_name',
            'template_name',
            'from_email',
            'from_name',
            'subject',
            'code',
            'text',
            'publish',
            // "labels",
        );

        ee()->dbg->c_log($_POST, __METHOD__);
        foreach ($_POST as $key => $val) {
            ee()->dbg->c_log("$key : ".ee()->input->post($key), __METHOD__);
            if (in_array($key, $form_fields)) {
                $$key = ee()->input->get_post($key);
                ee()->dbg->c_log("$key : ".ee()->input	->post($key), __METHOD__);
            }
        }

        if (isset($template_name)) {
            ee()->load->library('form_validation');
            ee()->form_validation->set_rules('template_name', 'lang:template_name', 'required|valid_xss_check');
            if (ee()->form_validation->run() === false) {
                ee()->view->set_message('issue', lang('saveTemplateError'), lang('saveTemplateErrorDesc'));
                // echo '<pre>';
                // print_r($_POST);
                // echo '</pre>';

                return $this->edit_template($template_name);
            }
        }
        $cache_data = array(
            'key' => $this->getApiKey(),
            'name' => (isset($template_name) ? $template_name : $orig_template_name),
            'from_email' => $from_email,
            'from_name' => $from_name,
            'subject' => $subject,
            'code' => utf8_encode($code),
            'text' => $text,
            'publish' => ($publish == 'y'),
            // "labels" => explode(',', $labels),
        );
        $function = ($created_at_hidden !== '') ? 'update' : 'add';

        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/'.$function.'.json';
        ee()->dbg->c_log($api_endpoint . json_encode($cache_data), __METHOD__);
        $result = $this->curlRequest($api_endpoint, $this->headers, $cache_data, true);
        if (isset($result['status'])) {
            ee()->view->set_message($result['status'], $result['message'], null, true);
            ee()->session->set_flashdata('result', $result['status'].':'.$result['message']);
        }

        ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/edit_template/'.(isset($template_name) ? $template_name : $orig_template_name)));
    }

    public function delete_template($template_name)
    {
        $data = array(
            'name' => $template_name,
            'key' => $this->getApiKey(),
        );
        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/delete.json';

        return $this->curlRequest($api_endpoint, $this->headers, $data, true);
    }

    /**
     *  Explodes a string which contains either a name and email address or just an email address into an array.
     **/
    public function _name_and_email($str)
    {
        $r = array(
            'name' => '',
            'email' => '',
        );

        $str = str_replace('"', '', $str);
        if (preg_match('/<([^>]+)>/', $str, $email_matches)) {
            $r['email'] = trim($email_matches[1]);
            $str = trim(preg_replace('/<([^>]+)>/', '', $str));
            if (!empty($str) && $str != $r['email']) {
                $r['name'] = utf8_encode($str);
            }
        } else {
            $r['email'] = trim($str);
        }

        return $r;
    }
}
