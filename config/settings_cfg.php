<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

return $config = array(
    'options' => array(
        'debug_mode' => array(
            'type' => 'yes_no',
            'default' => 'n'
        ),
        // 'default_sent_rows' => array(
        //     'type' => 'text',
        //     'default' => '10'
        // )
    )
);
//EOF
