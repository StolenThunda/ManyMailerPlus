<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
namespace Manymailerplus\Model;

use ExpressionEngine\Service\Model\Model;

/**
 *
 */
class EmailQueuePlus extends Model
{
    protected static $_primary_key = 'queue_id';
    protected static $_table_name = 'email_queue_plus';

    protected static $_typed_columns = array(
        'queue_id'      => 'int',
        'queue_start'         => 'timestamp',
        'email_id'      => 'int',
        'sent'      => 'int',
        'recipient_count'      => 'int',
        'queue_end'         => 'timestamp',
        'active' => 'int',
    );
    protected static $_validation_rules = array(
        'email_id'    => 'required',
        'recipient_count' => 'required',
    );
    protected $queue_id;
    protected $queue_start;
    protected $queue_end;
    protected $email_id;
    protected $sent;
    protected $recipient_count;
    protected $messages;
    protected $active;
}
// EOF
