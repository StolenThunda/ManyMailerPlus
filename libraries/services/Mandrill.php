<?php
use ManyMailer\libraries\services\Service;

class Mandrill extends Service{
    public function __construct($settings = array()){
        $this->key = $this->_get_mandrill_api($settings);
        $this->settings = $settings;
    }

    public function get_key(){
        return $this->key;
    }
   
    public function send_mail(){
        
        if ($this->key !== '') {
            $subaccount = (!empty($this->settings['mandrill_subaccount']) ? $this->settings['mandrill_subaccount'] : '');
            $sent = $this->_send_mandrill($subaccount);
            $missing_credentials = false;
            return array('missing_credentials' => false, 'sent' => $sent);
        }
    }

    public function _get_mandrill_api($settings = array())
    {
        try {
            if (!ee()->is_loaded('service_module'))  ee()->load->library('services_module', null, 'mail_svc');
            $settings = empty($settings) ? ee()->mail_svc->get_settings() : $settings;
            // $key = (!empty($settings['mandrill_api_key'])) ? $settings['mandrill_api_key'] : "";
            $key = (!empty($settings['mandrill_api_key'])) ? $settings['mandrill_api_key'] : '';
            $test_key = (!empty($settings['mandrill_test_api_key'])) ? $settings['mandrill_test_api_key'] : '';
            $test_mode = (isset($settings['mandrill_testmode__yes_no']) && $settings['mandrill_testmode__yes_no'] == 'y');
            $active_key = ($test_mode && $test_key !== '') ? $test_key : $key;
            // ee()->dbg->console_message("Act Key: $active_key", __METHOD__);
            return $active_key;
        } catch (\Throwable $th) {
            //throw $th;
            ee()->dbg->console_message($th, __METHOD__);

            return $th;
        }
    }
  /**
        Sending methods for each of our services follow.
     **/
    public function _send_mandrill($subaccount)
    {
        $content = array(
            'key' => $this->key,
            'async' => true,
            'message' => $this->email_out,
        );
        ee()->dbg->console_message($content, __METHOD__);
        if (isset($content['message']['extras'])) {
            ee()->dbg->console_message($content['message']['extras'], __FUNCTION__);

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
                        ee()->dbg->console_message($k, __METHOD__);
                        $v = $content['message']['html'];
                    }
                    array_push($t_content, array('name' => $k, 'content' => $v));
                }
                $content['template_content'] = $t_content;
            }
        }
        ee()->dbg->console_message($content, __METHOD__);
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
                'vars' => $this->_mandrill_lookup_to_merge($content['message']['lookup']),
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

        ee()->dbg->console_message($content, __METHOD__);
        //TODO: save email data to table
        // ee()->logger->developer($content);
        return $this->curl_request('https://mandrillapp.com/api/1.0/messages/'.$method.'.json', $this->headers, $content);
    }
    public function _mandrill_lookup_to_merge($lookup)
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

    private function _get_mandrill_templates($obj)
    {
        $func = (isset($obj['func'])) ? $obj['func'] : 'list';
        $template_name = (isset($obj['template_name'])) ? $obj['template_name'] : null;
        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/'.$func.'.json';
        $data = array(
            'key' => $this->_get_mandrill_api(),
        );
        if (!is_null($template_name)) {
            $data['name'] = $template_name;
        }
        $content = json_encode($data);
        ee()->dbg->console_message($api_endpoint.$content, __METHOD__);
        $templates = $this->curl_request($api_endpoint, $this->headers, $content, true);
        ee()->dbg->console_message($templates, __METHOD__);

        return $templates;
    }
}