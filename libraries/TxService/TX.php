<?php
/**
 *   Common functionality for transactional services
 * 
 * @category Transactional_Services
 * @package  ManyMailerPlus
 * @author   Tony Moses <tonymoses@texasbluesalley.com>
 * @license  MIT http://url.com
 * @link     http://url.com
 */
namespace ManyMailerPlus\libraries\TxService;
/**
 * Used for common transactional service functions
 * 
 * @category Transactional_Services
 * @package  ManyMailerPlus
 * @author   Tony Moses <tonymoses@texasbluesalley.com>
 * @license  MIT http://url.com
 * @link     http://url.com
 */
trait TX
{
    /**
     * Ultimately sends the email to each server.
     * 
     * @param string $server      the url to send the request to
     * @param array  $headers     http headers
     * @param json   $content     payload
     * @param bool   $return_data return json?
     * @param string $htpw        http password
     * 
     * @return bool if $return_data is false otherwise json data
     */
    public function curlRequest($server, $headers = array('Accept: application/json','Content-Type: application/json'), $content = "", $return_data = false, $htpw = null)
    {
        // $defaultHeaders = array(
        //     'Accept: application/json',
        //     'Content-Type: application/json',
        // );
        // $headers =  !empty($headers) ? $headers : $defaultHeaders;
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        ee()->dbg->c_log($curl_error, __METHOD__);
        $result = ($return_data) ? json_decode($status) : true;
        ee()->dbg->c_log($result, __METHOD__);
        if ($http_code !== 200) {
            ee()->logger->developer($server . BR . BR . $content . BR . BR . $status);
        }
        return ($http_code != 200 && !$return_data) ? false : json_decode(json_encode($result), true);
    }
}
//EOF