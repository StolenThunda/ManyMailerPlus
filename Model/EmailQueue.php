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
use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 *
 */
class EmailQueue extends Model
{
    protected static $_primary_key = 'queue_id';
    protected static $_table_name = 'email_queue_plus';

    protected static $_typed_columns = array(
        'queue_id'      => 'int',
        'email_id'      => 'int',
        'recipient_count'      => 'int',
        // 'messages' => 'serialized'
    );
    protected static $_validation_rules = array(
        'email_id'    => 'required',
        'recipient_count' => 'required',
    );
    protected $queue_id;
    protected $email_id;
    protected $recipient_count;
    protected $messages;
}
// EOF