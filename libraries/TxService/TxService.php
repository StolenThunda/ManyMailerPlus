<?php
namespace ManyMailerPlus\libraries\TxService;
use ManyMailerPlus\libraries;

// interface_exists('TxServiceInterface', false) or require_once 'TxServiceInterface.php';
abstract class TxService implements libraries\TxService\TxServiceInterface
{
    use libraries\Utility_Functions; // misc shared module functions
    use libraries\TxService\TX; // to share transactional functions (curl, etc.)
}