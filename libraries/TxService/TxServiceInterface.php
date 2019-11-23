<?php
namespace ManyMailerPlus\libraries\TxService;

interface TxServiceInterface
{
    public function getTemplates();
    public function getApiKey();
    public function sendEmail();
}
