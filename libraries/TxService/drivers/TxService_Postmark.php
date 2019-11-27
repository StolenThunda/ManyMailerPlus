<?php
use ManyMailerPlus\libraries;
class TxService_Postmark extends libraries\TxService\TxService
{
    function getTemplates()
    {
        $templates = array();
        return $templates;
    }

    function getApiKey()
    {
        return null;
    }
    public function sendEmail($email = null)
    {
        $sent = false;
        $settings = $this->u_getCurrentSettings();
        $missing_credentials = true;
        // if ($email) {
        //     $this->email_out = $email;
        //     unset($email);
        //     if (!empty($settings['postageapp_api_key'])) {
        //         $sent = $this->_send_email($settings['postageapp_api_key']);
        //         $missing_credentials = false;
        //     }
        // }

        return array('missing_credentials' => $missing_credentials, 'sent' => $sent);
    }
}