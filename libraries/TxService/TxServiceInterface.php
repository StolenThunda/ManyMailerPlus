<?php

namespace ManyMailerPlus\libraries\TxService;

interface TxServiceInterface
{
    
    public function get_templates();
    public function get_api_key();
    public function send_email();
    
}
