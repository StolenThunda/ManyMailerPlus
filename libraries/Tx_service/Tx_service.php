<?php

namespace ManyMailerPlus\libraries\Tx_service;

abstract class Tx_service implements Tx_service_interface
{
    private $debug = false;
    protected $headers = array();
    

    public function __construct($settings = array())
    {
        if (isset($settings['debug'])) {
            $this->debug = $settings['debug'];
        }
        $this->headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
    );
    }
    
    abstract function send_email();
    abstract function get_templates($obj = null);
    abstract function get_api_key();

    /**
        Ultimately sends the email to each server.
     **/
    public function curl_request($server, $headers = array(), $content, $return_data = false, $htpw = null)
    {
        $content = (is_array($content) ? json_encode($content) : $content);
        ee()->dbg->c_log($server.$content, __METHOD__);
        $ch = curl_init($server);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        // Convert @ fields to CURLFile if available
        if (is_array($content) && class_exists('CURLFile')) {
            foreach ($content as $key => $value) {
                if (strpos($value, '@') === 0) {
                    $filename = ltrim($value, '@');
                    $content[$key] = new CURLFile($filename, null, null);
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($htpw)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $htpw);
        }

        //return response instead of outputting
        if ($return_data) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        $status = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        ee()->dbg->c_log($http_code.' '.json_encode($status), __METHOD__);
        $result = ($return_data) ? json_decode($status) : true;
        // ee()->dbg->c_log($result, __METHOD__);
        // ee()->logger->developer($server . BR . BR . $content . BR . BR . $status);
        return ($http_code != 200 && !$return_data) ? false : json_decode(json_encode($result), true);
    }
}
