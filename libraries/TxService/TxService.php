<?php

namespace ManyMailerPlus\libraries\TxService;

require_once APPPATH . '/libraries/Driver.php';
use EE_Driver_Library;

interface_exists('TxServiceInterface', false) or require_once 'TxServiceInterface.php';


class TxService extends EE_Driver_Library
{
    private $debug = false;
    protected $headers = array();
    

    public function __construct($settings = array())
    {
        if (isset($settings['debug'])) {
            $this->debug = $settings['debug'];
        }
       
    );
        $this->valid_drivers = array('Mandrill', 'Postageapp');
    }
    
}
