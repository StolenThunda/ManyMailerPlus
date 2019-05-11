<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * 
 */
class Manymailerplus extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'exp_manymailerplus';

	protected $id;
	protected $site_id;
	protected $settings;
}

// EOF
