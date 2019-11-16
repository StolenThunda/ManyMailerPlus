<?php
 if (!defined('BASEPATH')) {
     exit('No direct script access allowed');
 }

    return $config = array(
        'options' => array(
            'index' => array(
            ),
            'email' => array(
                'links' => array('compose', 'sent', 'edit_template', 'view_templates')//, 'compose2' )
            ),
            'settings' => array(
                // 'links' => array('return_settings')
            ),
            'services' => array(
                'links' => array(),
            ),
        ),
    );
//EOF
