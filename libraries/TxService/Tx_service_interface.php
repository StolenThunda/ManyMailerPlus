<?php

namespace ManyMailerPlus\libraries\TxService;

interface TxService_interface
{
    function get_templates();
    function get_api_key();
    function send_email();
}