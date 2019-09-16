<?php

use ManyMailerPlus\libraries\TxService\TxService as TransactionService;

class Postageapp extends TransactionService
{
    public function __construct($settings = array())
    {
        parent::__construct($settings);
        $this->settings = $settings;
        $this->key = $this->_get_api($settings);
        ee()->dbg->c_log($this, __METHOD__);
    }

    public function get_api_key()
    {
        return $this->key;
    }

    public function send_email($email = null)
    {
        $sent = false;
        $settings = ee()->mod_svc->get_settings() ;
        $missing_credentials = true;
        if ($email) {
            $this->email_out = $email;
            unset($email);
            if (!empty($settings['postageapp_api_key'])) {
                $sent = $this->_send_email($settings['postageapp_api_key']);
                $missing_credentials = false;
            }
        }

        return array('missing_credentials' => $missing_credentials, 'sent' => $sent);
    }

    private function _get_api($settings = array())
    {
        $settings = empty($settings) ? ee()->mod_svc->get_settings() : $settings;
        ee()->dbg->c_log($settings, __METHOD__);

        return (!empty($settings['postageapp_api_key']) || !isset($settings['postageapp_api_key'])) ? null : $settings['postageapp_api_key'];
    }

    /**
        Sending methods for each of our services follow.
     **/
    public function _send_email($api_key)
    {
        $content = array(
            'api_key' => $api_key,
            'uid' => sha1(serialize($this->email_out['to']).$this->email_out['subject'].ee()->localize->now),
            'arguments' => array(
                'headers' => array(
                    'from' => $this->_recipient_str($this->email_out['from'], true),
                    'subject' => $this->email_out['subject'],
                ),
            ),
        );

        foreach ($this->email_out['headers'] as $header => $value) {
            $content['arguments']['headers'][$header] = $value;
        }

        /*
            All recipients, including Cc and Bcc, must be in the recipients array, and will be Bcc by default.
            Any addresses which are *also* included in the Cc header will be visible as Cc
        */
        $recipients = $this->email_out['to'];
        if (!empty($this->email_out['cc'])) {
            $recipients = array_merge($recipients, $this->email_out['cc']);
            $content['arguments']['headers']['cc'] = $this->_recipient_str($this->email_out['cc']);
        }
        if (!empty($this->email_out['bcc'])) {
            $recipients = array_merge($recipients, $this->email_out['bcc']);
        }
        $content['arguments']['recipients'] = $recipients;

        if (!empty($this->email_out['reply-to'])) {
            $content['arguments']['headers']['reply-to'] = $this->_recipient_str($this->email_out['reply-to'], true);
        }
        if (!empty($this->email_out['html'])) {
            $content['arguments']['content']['text/html'] = $this->email_out['html'];
        }
        if (!empty($this->email_out['text'])) {
            $content['arguments']['content']['text/plain'] = $this->email_out['text'];
        }
        if (!empty($this->email_out['attachments'])) {
            foreach ($this->email_out['attachments'] as $attachment) {
                $content['arguments']['attachments'][$attachment['name']] = array(
                    'content_type' => $attachment['type'],
                    'content' => $attachment['content'],
                );
            }
        }

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );

        if (ee()->extensions->active_hook('escort_pre_send')) {
            $content = ee()->extensions->call('escort_pre_send', 'postageapp', $content);
        }
        $content = json_encode($content);

        return $this->_curl_request('https://api.postageapp.com/v.1.0/send_message.json', $headers, $content);
    }

    public function save_template(){
        return true;
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

    public function get_templates($obj = array('template_name' => ''))
    {
        return array();
    }

    public function delete_template($template_name)
    {
        $data = array(
            'name' => $template_name,
            'key' => $this->_get_mandrill_api(),
        );
        $api_endpoint = 'https://mandrillapp.com/api/1.0/templates/delete.json';

        return $this->curl_request($api_endpoint, $this->headers, $data, true);
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
