<?php

namespace ManyMailerPlus\libraries\TxService;

interface TxServiceInterface
{
    function get_templates();
    function get_api_key();
    function send_email();
}