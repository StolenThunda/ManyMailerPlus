<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
use EllisLab\ExpressionEngine\Controller\Utilities;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Email\EmailCache;
/**
 * Copy of Communicate Controller
 */
class Settings {


	/**
	 * Constructor
	 */
	function __construct($settings=array())
	{
		$this->settings = $settings;
		$this->site_id = ee()->config->item('site_id');
		$this->model = ee('Model')->get(EXT_SHORT_NAME)
			->first();
	}

	function get_options(){
		$defaults = $this->get_settings();
		$vars['base_url'] = ee('CP/URL',EXT_SETTINGS_PATH.'/settings/save');
		$vars['save_btn_text'] = 'btn_save_settings';				
		$vars['save_btn_text_working'] = 'btn_saving';
		$vars['sections'] = array(
			array(
				array(
					'title' => 'debug_mode',
					'fields' => array(
						'debug_mode' => array(
							'type' => 'yes_no',
							'value' => (isset($defaults['debug_mod'])) ? $defaults['debug_mode'] : "",
						)
					),
				),
				array(
					'title' => 'language_options',
					'fields' => array(
						'language_options' => array(
							'type' => 'select',
							'choices' => array('EN'),
						)
					)
				)
			)
		);
		

		
		ee()->dbg->console_message($vars, __METHOD__);
		return $vars;
	}

	function get_settings(){
		$settings = array();
		foreach ($_POST as $key => $val)
		{
			$settings[$key] = $val;
		}
		return $settings;
	}

	function save_settings()
	{
	
		if (empty($_POST))
		{
			show_error(ee()->lang->line('unauthorized_access'));
		}else{
			$settings = $this->get_settings();
		}

	
		unset($_POST['submit']);
	
		// ee()->db->where('module_name', EXT_NAME);
		// ee()->db->update(EXT_SHORT_NAME, array('settings' => serialize($_POST)));
		$this->model->settings = $settings;
		$this->model->save();
		ee('CP/Alert')->makeInline('message_success')
		->asAttention()
		->withTitle('message_success')
		->addToBody('preferences_updated')
		->canClose()
		->now();
		ee()->functions->redirect(ee('CP/URL')->make(EXT_SETTINGS_PATH.'/settings/options')->compile());
	}

}
// END CLASS
// EOF