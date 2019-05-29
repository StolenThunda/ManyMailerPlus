<?php
use ManyMailerPlus\libraries\Tx_service\Tx_service as TransactionService;
class Mandrill extends TransactionService
{
    
    public function __construct($settings = array())
    {
        parrent::__construct($settings);
        
        $this->key = $this->_get_api($settings);
        $this->settings = $settings;
        ee()->dbg->c_log($this, __METHOD__);
    }

    public function get_api_key()
    {
        return $this->key;
    }

    public function send_email()
    {
        $sent = false;
        $missing_credentials = true;
        if ($this->key !== '') {
            $missing_credentials = false;
            $subaccount = (!empty($this->settings['mandrill_subaccount']) ? $this->settings['mandrill_subaccount'] : '');
            $sent = $this->_send_email($subaccount);
        }
        return array('missing_credentials' => $missing_credentials, 'sent' => $sent);
    }

    private function _get_api($settings = array())
    {
        try {
            if (!ee()->load->is_loaded('mod_services')) {
                ee()->load->library('services_module', null, 'mod_svc');
            }
            $settings = empty($settings) ? ee()->mod_svc->get_settings() : $settings;
            $key = (!empty($settings['mandrill_api_key'])) ? $settings['mandrill_api_key'] : '';
            $test_key = (!empty($settings['mandrill_test_api_key'])) ? $settings['mandrill_test_api_key'] : '';
            $test_mode = (isset($settings['mandrill_testmode__yes_no']) && $settings['mandrill_testmode__yes_no'] == 'y');
            $active_key = ($test_mode && $test_key !== '') ? $test_key : $key;
            // ee()->dbg->c_log("Act Key: $active_key", __METHOD__);
            return $active_key;
        } catch (\Throwable $th) {
            //throw $th;
            ee()->dbg->c_log($th, __METHOD__);

            return $th;
        }
    }

    /**
        Sending methods for each of our services follow.
     **/
    public function _send_email($subaccount)
    {
        $content = array(
            'key' => $this->key,
            'async' => true,
            'message' => $this->email_out,
        );
        ee()->dbg->c_log($content, __METHOD__);
        if (isset($content['message']['extras'])) {
            ee()->dbg->c_log($content['message']['extras'], __FUNCTION__);

            if (isset($content['message']['extras']['from_name'])) {
                $content['message']['from_name'] = $content['message']['extras']['from_name'];
            }
            if (isset($content['message']['extras']['template_name'])) {
                $content['template_name'] = $content['message']['extras']['template_name'];
            }
            if (isset($content['template_name']) && isset($content['message']['extras']['mc-edit'])) {
                $edits = $content['message']['extras']['mc-edit'];
                $t_content = array();
                foreach ($edits as $k => $v) {
                    if (in_array($k, array('main', 'content', 'body_content'))) {
                        ee()->dbg->c_log($k, __METHOD__);
                        $v = $content['message']['html'];
                    }
                    array_push($t_content, array('name' => $k, 'content' => $v));
                }
                $content['template_content'] = $t_content;
            }
        }
        ee()->dbg->c_log($content, __METHOD__);
        if (!empty($subaccount)) {
            $content['message']['subaccount'] = $subaccount;
        }

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

        ee()->dbg->c_log($content, __METHOD__);
        //TODO: save email data to table
        // ee()->logger->developer($content);
        return $this->curl_request('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', $this->headers, $content);
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

    public function get_templates($obj = array('func' => 'list', 'template_name' => ''))
    {
        $templates = array();
        $func = (isset($obj['func'])) ? $obj['func'] : 'list';
        $template_name = (isset($obj['template_name'])) ? $obj['template_name'] : null;
        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/'.$func.'.json';
        $data = array(
            'key' => $this->_get_api(),
        );
        if (!is_null($template_name)) {
            $data['name'] = $template_name;
        }
        $content = json_encode($data);
        ee()->dbg->c_log($api_endpoint.$content, __METHOD__);
        $templates = $this->curl_request($api_endpoint, $this->headers, $content, true);
        ee()->dbg->c_log($templates, __METHOD__);

        return $templates;
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
