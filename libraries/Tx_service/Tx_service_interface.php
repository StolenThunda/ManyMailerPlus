<?php

namespace ManyMailerPlus\libraries\Tx_service;

interface Tx_service_interface
{
    function get_templates();
    function get_api_key();
    function send_email();
}