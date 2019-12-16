<?php
/**
 * This source file is part of the open source project
 */
use EllisLab\ExpressionEngine\Library\CP\Table;

use ManymailerPlus\Model\EmailCachePlus as EmailCachePlus;

/**
 * Copy of Communicate Controller.
 */
class Composer
{
    use ManyMailerPlus\libraries\Utility_Functions;
    private $_attachments = array();
    private $_csv_lookup = array();
    private $_csv_email_column = '{{email}}';
    private $_email_regex = '/<([^>]+)>/';//'/(?:[a-z0-9!#$%&\'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/m';
     
    /**
     * Constructor.
     */
    public function __construct($settings = array())
    {
        if (!ee()->cp->allowed_group('can_access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
        $this->settings = $this->u_getCurrentSettings();
        $this->default_config = ee('Config')->getFile(EXT_SHORT_NAME.':settings_cfg')->get('options');
    }
    private function createModalProgress()
    {
        $modal_vars = array(
            'name' => 'mail_progress',
            'contents' => implode(
                '',
                array(
                '<div id="mail_progress_output">',
                '<h1>Progress: </h1>',
                '<span class="txt-wrap">',
                '<progress max="100" value="0">',
                '</progress><br/></hr>',
                '<p>'. lang('sent'). ' <span id="current">0</span> '.lang('of').' <span id="total">--</span> '. lang('emails').'.</span></br>',
                '<span id="p-info"><span id="percent">--</span>% done</span></p><br />',
                '<textarea id="result" style="white-space:pre-wrap" placeholder="Initializing..." cols="30" rows="5"></textarea>',
                '<br/><h3>Elapsed: <span id="time">--</span></h3><hr />',
                '</span>',
                '</div>'
                )
            )
        );
        $modal_html = ee('View')->make('ee:_shared/modal')->render($modal_vars);
        ee()->dbg->c_log($modal_html, __METHOD__ . '  ' . __LINE__);
        return $modal_html;
    }

    private function _getConfigValue($value, $default = null)
    {
        $default_config_value = (isset($default)) ? ee('Config')->getFile(EXT_SHORT_NAME.':settings_cfg')->get('options.'.$value, $default) : ee('Config')->getFile(EXT_SHORT_NAME.':settings_cfg')->get('options.'.$value);
        $value = explode('.', $value)[0];
        return $this->settings['config'][$value] ?: $default_config_value;
    }
    /**
     *  Removes <>'ed email addresses from a string
     *  (Tony Moses \<tonymoses@texasbluesalley.com\>, Antonio Moses \<tonym415@gmail.com\>, test \<test@test.com\>)
     *
     * @param String $str String of email address. May or may not have <> in the string
     */
    private function extractBracketedEmail($str = null)
    {
        $emails = explode(',', $str);
        // ee()->dbg->c_log($str, __METHOD__ . '  ' . __LINE__);
        $matches = null;
        foreach ($emails as $email) {
            preg_match_all($this->_email_regex, $email, $matches, PREG_SET_ORDER, 0);
            foreach ($matches as $key=>$match) {
                if (!in_array($match[0][0], $emails)) {
                    $emails[] = $match[0][0];
                }
            }
        }
       
        $email_str = empty($emails) ? $str : join(", ", $emails);
        // ee()->dbg->c_log($matches, __METHOD__ . '  ' . __LINE__);
        return $email_str;
    }

     
    /**
     * compose.
     *
     * @param obj $email An EmailCachePlus object for use in re-populating the form (see: resend())
     */
    public function compose(EmailCachePlus $email = null)
    {
        $default = array(
            'from' => ee()->session->userdata('email'),
            'from_name' => ee()->session->userdata('screen_name'),
            'recipient' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => '',
            'message' => '',
            'plaintext_alt' => '',
            'mailtype' => ee()->config->item('mail_format'),
            'wordwrap' => ee()->config->item('word_wrap'),
            'csv_object' => null,
            'mailKey' => null
        );

        $vars['mailtype_options'] = array(
            'text' => lang('plain_text'),
            'markdown' => lang('markdown'),
            'html' => lang('html'),
        );
        $member_groups = array();

        if (!is_null($email)) {
            $default['from'] = $email->from_email;
            $default['recipient'] = $this->extractBracketedEmail($email->recipient);
            $default['cc'] = $email->cc;
            $default['bcc'] = $email->bcc;
            $default['subject'] = str_replace('(TEMPLATE) ', '', $email->subject);
            $default['message'] = $email->message;
            $default['plaintext_alt'] = $email->plaintext_alt;
            $default['mailtype'] = $email->mailtype;
            $default['wordwrap'] = $email->wordwrap;
            $default['csv_object'] = json_encode($email->csv_object);
            $default['mailKey'] = $email->mailKey;
        }
        // Set up member group emailing options
        if (ee()->cp->allowed_group('can_email_member_groups')) {
            $groups = ee('Model')->get('MemberGroup')
                ->filter('site_id', ee()->config->item('site_id'))
                ->all();

            $member_groups = [];
            $disabled_groups = [];
            foreach ($groups as $group) {
                $member_groups[$group->group_id] = $group->group_title;

                if (ee('Model')->get('Member')
                    ->filter('group_id', $group->group_id)
                    ->count() == 0) {
                    $disabled_groups[] = $group->group_id;
                }
            }
        }

        $csvHTML = array(
            form_textarea(
                array(
                    'name' => 'csv_recipient',
                    'id' => 'csv_recipient',
                    'rows' => '10',
                    'class' => 'required',
                )
            ),
            form_button('convert_csv', 'Convert CSV', 'class="btn"'),
        );

        if ($default['mailtype'] != 'html') {
            ee()->javascript->output('$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).hide();');
        }

       
        ee('CP/Modal')->addModal('hello', $this->createModalProgress());
        $vars['sections'] = array(
            'sender_info' => array(
                array(
                    'title' => 'from_email',
                    'desc' => 'from_email_desc',
                    'fields' => array(
                        'from' => array(
                            'type' => 'text',
                            'value' => $default['from'],
                        ),
                    ),
                ),
                array(
                    'title' => 'from_name',
                    'desc' => 'from_name_desc',
                    'fields' => array(
                        'from_name' => array(
                            'type' => 'text',
                            'value' => $default['from_name'],
                        ),
                    ),
                ),
            ),
            'recipient_options' => array(
                array(
                    'title' => 'recipient_entry',
                    'fields' => array(
                        'recipient_entry' => array(
                            'type' => 'select',
                            'choices' => array(
                                'file_recipient' => lang('upload'),
                                'csv_recipient' => lang('manual'),
                            ),
                        ),
                    ),
                ),
                array(
                    'title' => 'file_recipient',
                    'desc' => 'file_recipient_desc',
                    'fields' => array(
                        'files[]' => array(
                            'type' => 'html',
                            'content' => '<input type="file" name="file_recipient" accept=".csv" value="CHoose file"  />',
                        ),
                        'csv_object' => array(
                            'type' => 'hidden',
                            'value' => $default['csv_object'],
                        ),
                        'mailKey' => array(
                            'type' => 'hidden',
                            'value' => $default['mailKey'],
                        ),
                    ),
                ),
                array(
                    'title' => 'csv_recipient',
                    'desc' => 'csv_recipient_desc',
                    'fields' => array(
                        'csv_errors' => array(
                            'type' => 'html',
                            'content' => '<details id="csv_errors" class="validation_error"></details>',
                        ),
                        'csv_recipient' => array(
                            'type' => 'html',
                            'content' => implode('<br />', $csvHTML),
                        ),
                    ),
                ),
                array(
                    'title' => 'primary_recipients',
                    'desc' => 'primary_recipients_desc',
                    'fields' => array(
                        'csv_reset' => array(
                            'type' => 'html',
                            'content' => form_button('btnReset', 'Reset CSV Data', 'id="reset" class="btn"'),
                        ),
                        'recipient' => array(
                            'type' => 'text',
                            'value' => $default['recipient'],
                            'required' => true,
                        ),
                        'csv_content' => array(
                            'type' => 'html',
                            'content' => '<table class=\'fixed_header\' id=\'csv_content\'></table>',
                        ),
                    ),
                ),
            ),
            'compose_email_detail' => array(
                  array(
                    'title' => 'email_subject',
                    'fields' => array(
                        'subject' => array(
                            'type' => 'text',
                            'required' => true,
                            'value' => $default['subject'],
                        ),
                    ),
                ),
                array(
                    'title' => 'message',
                    'fields' => array(
                        'message' => array(
                            'type' => 'html',
                            'content' => ee('View')->make(EXT_SHORT_NAME.':email/body-field')
                                ->render($default + $vars),
                        ),
                    ),
                ),
                array(
                    'title' => 'plaintext_body',
                    'desc' => 'plaintext_alt',
                    'fields' => array(
                        'plaintext_alt' => array(
                            'type' => 'textarea',
                            'value' => $default['plaintext_alt'],
                            'required' => true,
                        ),
                    ),
                ),
                array(
                    'title' => 'attachment',
                    'desc' => 'attachment_desc',
                    'fields' => array(
                        'attachment' => array(
                            'type' => 'file',
                        ),
                    ),
                ),
            ),

        'other_recipient_options' => array(
            array(
                'title' => 'cc_recipients',
                'desc' => 'cc_recipients_desc',
                'fields' => array(
                    'cc' => array(
                        'type' => 'text',
                        'value' => $default['cc'],
                    ),
                ),
            ),
            array(
                'title' => 'bcc_recipients',
                'desc' => 'bcc_recipients_desc',
                'fields' => array(
                    'bcc' => array(
                        'type' => 'text',
                        'value' => $default['bcc'],
                    ),
                ),
            ),
            ),
        );

        // if (ee()->cp->allowed_group('can_email_member_groups')) {
        //     $vars['sections']['other_recipient_options'][] = array(
        //         'title' => 'add_member_groups',
        //         'desc' => 'add_member_groups_desc',
        //         'fields' => array(
        //             'member_groups' => array(
        //                 'type' => 'checkbox',
        //                 'choices' => $member_groups,
        //                 'disabled_choices' => $disabled_groups,
        //             ),
        //         ),
        //     );
        // }
        
        array_unshift(
            $vars['sections']['compose_email_detail'],
            array(
                'title' => '',
                'fields' => array(
                    'btn' => array(
                        'type' => ($this->u_debug_enabled()) ? 'html' : 'hidden',
                        'content' => '<div class="form-btns">'.form_button('btnDump', 'Dump Hidden Values', "class='btn'").BR.form_button('btnDump2', 'Dump Form Values', 'class="btn dbg"').'</div>',
                    ),
                )
            ),
            array(
                'title' => 'use_templates',
                'desc' => 'use_templates_desc',
                'fields' => array(
                    'use_template' => array(
                        'type' => 'html',
                        'content' => form_yes_no_toggle('use_templates', false).form_input(
                            array(
                            'id' => 'chosen_template_html',
                            'name' => 'chosen_template_html',
                            'type' => 'hidden'
                            )
                        ),
                    ),
                    'template_list' => array(
                        'type' => 'html',
                        'content' => ''
                    ),
                ),
            ),
            array(
                'title' => 'template_name',
                'desc' => '_template_name',
                'fields' => array(
                    'template_name' => array(
                        'type' => 'html',
                        'content' => form_input(
                            array(
                                'id' => 'template_name',
                                'name' => 'template_name',
                            )
                        ),
                    ),
                ),
            )
        );
        // }
        $vars['cp_page_title'] = lang('compose_heading');
        $vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email/send');
        $vars['save_btn_text'] = lang('compose_send_email');
        $vars['save_btn_text_working'] = lang('compose_sending_email');
        ee()->cp->add_js_script(
            array(
            'file' => array('cp/form_group'),
            )
        );
        
        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);

        return $vars;
    }

    public function mail_progress()
    {
        session_start();

        if (!array_key_exists('status', $_SESSION)) {
            $_SESSION['status'] = array('progress' => 0, 'messages' => '');
        }
        if ($_SESSION['status']['progress'] >= 100) {
            unset($_SESSION['status']);
            die('--');
        }
        $current_queue = ee('Model')->get(EXT_SHORT_NAME. ':EmailQueue')->all()->last();
        if (isset($current_queue->recipient_count)) {
            $total_to_be_sent = $current_queue->recipient_count ?: 0;
            $total_sent = ee('Model')
                ->get(EXT_SHORT_NAME. ':EmailCachePlus')
                ->filter('parent_id', $current_queue->email_id)
                ->all()
                ->count();
                
            $progress =  round($total_sent / $total_to_be_sent * 100);

            //https://wordpress.stackexchange.com/questions/290488/how-get-exact-time-difference
            $now = ($progress < 100) ? new DateTime() : $current_queue->queue_end;
            $start = $current_queue->queue_start;
            if ($progress <= 100) {
                $current_queue->queue_end = $now->getTimestamp();
                $current_queue->save();
            }
            
            $diff = $start->diff($now);
            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;
        
            $string = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
            foreach ($string as $k => &$v) {
                if ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }
            $elapsed_time = $string ? implode(', ', $string): '';

            $_SESSION['status'] = array(
                'start' => $start,
                'req' => $now,
                'time' => $elapsed_time,
                'current' => $total_sent,
                'total' => $total_to_be_sent,
                'progress' => $progress,
                'messages' => ($total_sent !== $total_to_be_sent) ? $current_queue->messages : $current_queue->messages . "Done!!!\n"
            );
        }

       
        return $_SESSION['status'];
    }

    /**
     *  Returns the html rendered by view_templates
     *
     * @return string html
     */
    public function getTemplateView()
    {
        $vars = $this->view_templates();
        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);
        return  ee('View')->make(EXT_SHORT_NAME.':email/embed_templates')->render($vars);
    }
    /**
     *  Stepped version of composer
     *
     * @param obj $email An EmailCachePlus object for use in re-populating the form (see: resend())
     *
     */
    public function compose2(EmailCachePlus $email = null)
    {
        ee()->dbg->c_log(__FUNCTION__, __METHOD__ . '  ' . __LINE__);
        $default = array(
            'from' => ee()->session->userdata('email'),
            'from_name' => ee()->session->userdata('screen_name'),
            'recipient' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => '',
            'message' => '',
            'plaintext_alt' => '',
            'mailtype' => ee()->config->item('mail_format'),
            'wordwrap' => ee()->config->item('word_wrap'),
            'csv_object' => null,
            'mailKey' => null
        );

        $vars['mailtype_options'] = array(
            'text' => lang('plain_text'),
            'markdown' => lang('markdown'),
            'html' => lang('html'),
        );

        $member_groups = array();

        if (!is_null($email)) {
            $default['from'] = $email->from_email;
            $default['recipient'] = $this->extractBracketedEmail($email->recipient);
            $default['cc'] = $email->cc;
            $default['bcc'] = $email->bcc;
            $default['subject'] = str_replace('', '(TEMPLATE) ', $email->subject);
            $default['message'] = $email->message;
            $default['plaintext_alt'] = $email->plaintext_alt;
            $default['mailtype'] = $email->mailtype;
            $default['wordwrap'] = $email->wordwrap;
            $default['csv_object'] = json_encode($email->csv_object);
            $default['mailKey'] = $email->mailKey;
        }
        // Set up member group emailing options
        if (ee()->cp->allowed_group('can_email_member_groups')) {
            $groups = ee('Model')->get('MemberGroup')
                ->filter('site_id', ee()->config->item('site_id'))
                ->all();

            $member_groups = [];
            $disabled_groups = [];
            foreach ($groups as $group) {
                $member_groups[$group->group_id] = $group->group_title;

                if (ee('Model')->get('Member')
                    ->filter('group_id', $group->group_id)
                    ->count() == 0) {
                    $disabled_groups[] = $group->group_id;
                }
            }
        }

        if ($default['mailtype'] != 'html') {
            ee()->javascript->output('$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).hide();');
        }

        $form_cls = ' class="form-control"';

        $template_view = ee('View')->make(EXT_SHORT_NAME.':email/embed_templates');

        $vars['sections'] = array(
            'sender_info' => array(
                'from_email' => '*'.form_input('from', $default['from'], 'required=true', $form_cls),
                'from_name' => form_input('from_name', $default['from_name']),
            ),
            'recipient_options' => array(
                'recipient_entry' => form_dropdown('recipient_entry', array(
                    'file_recipient' => lang('upload'),
                    'csv_recipient' => lang('manual'),
                ), 'upload').form_hidden('files[]', 0, 'id="files"'),
                'file_recipient' => form_upload('file_recipient').form_hidden('csv_object', json_decode($default['csv_object']), true).form_hidden('mailKey', $default['mailKey']),
                '' => '<span id="csv_errors"></span><hr/>',
                'csv_recipient' => form_textarea(
                    array(
                        'name' => 'csv_recipient',
                        'id' => 'csv_recipient',
                        'rows' => '10',
                    )
                ).BR.form_button('convert_csv', 'Convert CSV', 'class="btn"'),
                'primary_recipients' => '*'.form_input(array(
                    'name' => 'recipient',
                ), $default['recipient']).BR.BR.form_button(array('id' => 'reset'), 'Reset CSV Data', 'class="btn1" '),
                ' ' => '<table class=\'fixed_header\' id=\'csv_content\'></table>'.BR.NBS,
            ),
            'compose_email_detail' => array(
                '' => ($this->u_debug_enabled()) ? form_button('btnDump', 'Dump Hidden Values', 'class="btn" onClick="dumpHiddenVals()"').NBS.form_button('btnDumpForm', 'Dump Form Values', 'class="btn" onClick="dumpFormVals()"') : '',
                'use_templates' => form_yes_no_toggle('use_templates', false).BR.BR.$template_view->render($this->view_templates()).BR.BR,
                ' ' => form_input(array('id' => 'template_name', 'name' => 'template_name')),
                'subject' => '*'.form_input('subject', $default['subject']),
                'message' => '*'.ee('View')->make(EXT_SHORT_NAME.':email/body-field')->render($default + $vars),
                'plaintext_alt' => form_textarea('plaintext_alt', $default['plaintext_alt']),
                'attachment' => form_upload('attachment'),
            ),
            'other_recipient_options' => array(
                'cc_recipients' => form_input('cc', $default['cc']),
                'bcc_recipients' => form_input('bcc', $default['bcc'])//.form_hidden('member_groups[]'),
            ),
        );
        // ee()->dbg->c_log($member_groups, __METHOD__ . '  ' . __LINE__);
        // ee()->dbg->c_log($disabled_groups, __METHOD__ . '  ' . __LINE__);
        // if (ee()->cp->allowed_group('can_email_member_groups')) {
        //     $vars['sections']['other_recipient_options'][] = array(
        //         'add_member_groups' => ee('View')->make('ee:_shared/form/fields/select')->render([
        //             'type' => 'checkbox',
        //             'field_name' => 'add_group[]',
        //             'choices' => $member_groups,
        //             'disabled_choices' => $disabled_groups,
        //         ]),
        //         // 'title' => 'add_member_groups',
        //         // 'desc' => 'add_member_groups_desc',
        //         // 'fields' => array(
        //         // 	'member_groups' => array(
        //         // 		'type' => 'checkbox',
        //         // 		'choices' => $member_groups,
        //         // 		'disabled_choices' => $disabled_groups,
        //         // 	)
        //         // )
        //     );
        // }
        $vars['cp_page_title'] = lang('compose_heading');
        $vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email/send');
        $vars['save_btn_text'] = lang('compose_send_email');
        $vars['save_btn_text_working'] = lang('compose_sending_email');
        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);

        return $vars;
    }

    public function edit_template($template_name = '')
    {
        $message = ee()->session->flashdata('result');
        if ($message) {
            $message = explode(':', ee()->session->flashdata('result'));
            ee()->dbg->c_log('Msg: '.implode(':', $message), __METHOD__ . '  ' . __LINE__);
            ee('CP/Alert')->makeInline('result')
                        ->asIssue()
                        ->withTitle(lang('template_'.$message[0]))
                        ->addToBody(end($message))
                        ->canClose()
                        ->now();

            ee('CP/Alert')->makeInline('saveTemplate_req')
                        ->asIssue()
                        ->withTitle(ee()->session->flashdata('save_endpoint'))
                        ->addToBody(ee()->session->flashdata('save_api_data'))
                        ->canClose()
                        ->now();
        }

        $default = array(
            'template_name' => '',
            'from_email' => ee()->session->userdata('email'),
            'from_name' => ee()->session->userdata('screen_name'),
            'subject' => '',
            'code' => '',
            'text' => '',
            'publish' => false,
            'created_at' => '',
            'labels' => array(),
        );
        ee()->dbg->c_log('TEMP NAME: '.$template_name, __METHOD__ . '  ' . __LINE__);

        if ($template_name !== '') {
            $template_name = str_replace('_', ' ', $template_name);
            $template = $this->_get_service_templates(array('template_name'=>$template_name, 'func' => 'info'));
            ee()->dbg->c_log($template, __METHOD__ . '  ' . __LINE__);
            if (isset($template['status'])) {
                ee()->session->set_flashdata('result', $template['status'].':'.$template['message']);
                ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/edit_template'));
            }
            $default['template_name'] = $template['name'];
            $default['from_email'] = $template['from_email'];
            $default['from_name'] = $template['from_name'];
            $default['code'] = $template['code'];
            $default['subject'] = $template['subject'];
            $default['text'] = $template['text'];
            $default['publish'] = isset($template['publish_code']);
            $default['labels'] = $template['labels'];
            $default['created_at'] = $template['created_at'];
        }
        $has_template_name = ($default['template_name'] !== '');
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'template_name',
                    'desc' => 'template_name_desc',
                    'fields' => array(
                        'orig_template_name' => array(
                            'type' => 'hidden',
                            'value' => $default['template_name'],
                        ),
                        'template_name' => array(
                            'type' => 'text',
                            'value' => $default['template_name'],
                            'disabled' => $has_template_name,
                            'required' => !$has_template_name,
                        ),
                    ),
                ),
                array(
                    'title' => ($default['created_at'] === '') ? '' : 'created_at',
                    'fields' => array(
                        'created_at_hidden' => array(
                            'type' => 'hidden',
                            'value' => $default['created_at'],
                        ),
                        'created_at' => array(
                            'type' => ($default['created_at'] === '') ? 'hidden' : 'text',
                            'value' => $default['created_at'],
                            'disabled' => true,
                        ),
                    ),
                ),
            ),
            'template_info' => array(
                array(
                    'title' => 'from_email',
                    'desc' => 'from_email_desc',
                    'fields' => array(
                        'from_email' => array(
                            'type' => 'text',
                            'value' => $default['from_email'],
                        ),
                    ),
                ),
                array(
                    'title' => 'from_name',
                    'desc' => 'from_name_desc',
                    'fields' => array(
                        'from_name' => array(
                            'type' => 'text',
                            'value' => $default['from_name'],
                        ),
                    ),
                ),
                array(
                    'title' => 'subject',
                    'desc' => 'subject_desc',
                    'fields' => array(
                      'subject' => array(
                        'type' => 'text',
                        'value' => $default['subject'],
                        ),
                    ),
                ),
                array(
                    'title' => 'code',
                    'desc' => 'code_desc',
                    'fields' => array(
                        'code' => array(
                            'type' => 'html',
                            'content' => form_textarea(array('name' => 'code', 'rows' => 15), $default['code']),
                        ),
                    ),
                ),
                array(
                    'title' => 'text',
                    'desc' => 'text_desc',
                    'fields' => array(
                      'text' => array(
                        'type' => 'text',
                        'value' => $default['text'],
                        ),
                    ),
                ),
                array(
                    'title' => 'publish',
                    'desc' => 'publish_desc',
                    'fields' => array(
                        'publish' => array(
                        'type' => 'yes_no',
                        'choices' => array(
                            'y' => true,
                            'n' => false,
                        ),
                        'value' => $default['publish'],
                        ),
                    ),
                ),
            ),
        );

        $vars['cp_page_title'] = lang(__FUNCTION__);
        $vars['base_url'] = ee('CP/URL', EXT_SETTINGS_PATH.'/email/saveTemplate');
        $vars['save_btn_text'] = lang('saveTemplate');
        $vars['save_btn_text_working'] = lang('saving_template');

        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);

        return $vars;
    }

    public function saveTemplate()
    {
        $service = $this->get_service();
        if (!is_null($service)) {
            ee()->{$service}->saveTemplate();
        }
    }

    /**
     * Prepopulate form to send to specific member.
     *
     * @param int $id
     *
     * @return void
     */
    public function member($id)
    {
        $member = ee('Model')->get('Member', $id)->first();
        $this->member = $member;

        if (empty($member)) {
            show_404();
        }

        $cache_data = array(
            'recipient' => $member->email,
            'from_email' => ee()->session->userdata('email'),
        );
        ee()->dbg->c_log(EXT_SHORT_NAME.':EmailCachePlus', __METHOD__ . '  ' . __LINE__);
        $email = ee('Model')->get(EXT_SHORT_NAME. ':EmailCachePlus', $cache_data);

        $email->removeMemberGroups();
        $this->compose($email);
    }

    /**
     * Send Email.
     */
    public function send()
    {
        $tmp = explode('/', $_SERVER['HTTP_REFERER']);
        $last = end($tmp);
        $sender = is_numeric($last) ? $tmp[count($tmp) - 2] . '/' . $last : $last;
        $this->available_services = $this->u_getCurrentSettings()['service_order'];
        ee()->dbg->c_log($_POST, __METHOD__ . ' POST ' . __LINE__);
        ee()->load->library('email');

        $groups = array();

        $form_fields = array(
            'subject',
            'message',
            'plaintext_alt',
            'mailtype',
            'wordwrap',
            'from',
            'attachment',
            'recipient',
            'cc',
            'bcc',
            'mailKey',
            'csv_object'
        );

        $wordwrap = 'n';

        // Fetch $_POST data
        // We'll turn the $_POST data into variables for simplicity
        foreach ($_POST as $key => $val) {
            if ($key == 'member_groups') {
                // filter empty inputs, like a hidden no-value input from React
                $groups = array_filter(ee()->input->post($key));
            } elseif (in_array($key, $form_fields)) {
                $$key = ee()->input->post($key);
            } else {
                $this->extras[$key] = ee()->input->post($key);
            }
        }
        $recipient_array = array_map(
            function ($a) {
                return filter_var($a, FILTER_SANITIZE_EMAIL);
            },
            $this->_recipient_array($recipient)
        );
       
        if (isset($mailKey)) {
            $this->_csv_email_column = preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $mailKey);
        }
        // create lookup array for easy email lookup
        if (isset($csv_object) and $csv_object !== '' and isset($mailKey)) {
            $rows = json_decode($csv_object, true);
    
            foreach ($rows as $row) {
                // ee()->dbg->c_log(in_array($this->_csv_email_column, array_keys($row)), __METHOD__ . ":".$this->_csv_email_column);
                $this->_csv_lookup[trim($row[$this->_csv_email_column])] = $row;
            }
        }
        // ee()->dbg->c_log($this->_csv_lookup, __METHOD__ . '  ' . __LINE__);
        //  Verify privileges
        if (count($groups) > 0 && !ee()->cp->allowed_group('can_email_member_groups')) {
            show_error(lang('not_allowed_to_email_member_groups'));
        }

        ee()->load->helper('email');

        
        // $recipient = $this->extractBracketedEmail($recipient);
        
        $recipient = $this->_recipient_str($recipient_array);
        
        // ee()->dbg->c_log($recipient, __METHOD__ . '  ' . __LINE__);
        // ee()->dbg->c_log((bool) valid_email($recipient), __METHOD__ . '  ' . __LINE__);
        // Set to allow a check for at least one recipient
        $_POST['total_gl_recipients'] = count($groups);

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules('subject', 'lang:subject', 'required|valid_xss_check');
        ee()->form_validation->set_rules('message', 'lang:message', 'required');
        ee()->form_validation->set_rules('recipient', 'lang:recipient', 'valid_emails|callback__check_for_recipients');
        ee()->form_validation->set_rules('from', 'lang:from', 'required|valid_email');
        ee()->form_validation->set_rules('cc', 'lang:cc', 'valid_emails');
        ee()->form_validation->set_rules('bcc', 'lang:bcc', 'valid_emails');
        ee()->form_validation->set_rules('attachment', 'lang:attachment', 'callback__attachment_handler');

        if (ee()->form_validation->run() === false) {
            // ee()->dbg->c_log(ee()->form_validation, __METHOD__, true);
            ee()->view->set_message('issue', lang('compose_error'), lang('compose_error_desc'));
            ee('CP/Alert')->makeInline('issue')
                ->asIssue()
                ->withTitle(lang('compose_error'))
                ->addToBody(ee()->form_validation->error_string())
                ->canClose()
                ->now();
            ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/'.$sender));
        }
        $name = ee()->session->userdata('screen_name');
        
        $debug_msg = '';
        
        switch ($mailtype) {
            case 'text':
                $text_fmt = 'none';
                $plaintext_alt = '';
                break;

            case 'markdown':
                $text_fmt = 'markdown';
                $mailtype = 'html';
                $plaintext_alt = $message;
                break;

            case 'html':
                // If we strip tags and it matches the message, then there was
                // not any HTML in it and we'll format for them.
                if ($message == strip_tags($message)) {
                    $text_fmt = 'xhtml';
                } else {
                    $text_fmt = 'none';
                }
                break;
        }

        $subject = "${subject} (TEMPLATE) ";

        // Assign data for caching
        $cache_data = array(
            'cache_date' => ee()->localize->now,
            'total_sent' => 0,
            'from_name' => $name,
            'from_email' => $from,
            'recipient' => $recipient,
            'cc' => $cc,
            'bcc' => $bcc,
            'recipient_array' => $this->_recipient_array($recipient),
            'subject' => $subject,
            'message' => $message,
            'mailtype' => $mailtype,
            'wordwrap' => $wordwrap,
            'text_fmt' => $text_fmt,
            'total_sent' => 0,
            'plaintext_alt' => $plaintext_alt,
            'attachments' => $this->_attachments,
            'mailKey' => !empty($this->_csv_email_column) ? $this->_csv_email_column : "error",
            'csv_object' => !empty($csv_object) ? json_decode($csv_object, true) : array()
        );
        ee()->dbg->c_log($cache_data, __METHOD__ . ' Cache Presave ' . __LINE__);
        $email = ee('Model')->make(EXT_SHORT_NAME. ':EmailCachePlus', $cache_data);
        $email->save();
        // Get member group emails
        $member_groups = ee('Model')->get('MemberGroup', $groups)
            ->with('Members')
            ->all();

        $email_addresses = array();
        foreach ($member_groups as $group) {
            foreach ($group->getMembers() as $member) {
                $email_addresses[] = $member->email;
            }
        }

        if (empty($email_addresses) and $recipient == '') {
            show_error(lang('no_email_matching_criteria'));
        }

        //** ----------------------------------------
        //**  Do we have any CCs or BCCs?
        /** ----------------------------------------*/

        //  If so, we'll send those separately first
        

        $total_sent = 0;

        if ($cc != '' or $bcc != '') {
            $to = ($recipient == '') ? ee()->session->userdata['email'] : $recipient;
            $debug_msg = $this->deliverOneEmail($email, $to, empty($email_addresses));

            $total_sent = $email->total_sent;
        } else {
            // No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle

            if ($recipient != '') {
                foreach (explode(',', $recipient) as $address) {
                    $address = trim($address);

                    if (!empty($address)) {
                        $email_addresses[] = $address;
                    }
                }
            }
        }
        ee()->dbg->c_log($email_addresses, __METHOD__ . ' Indiv Emails ' . __LINE__);
        //  Store email cache
        $email->recipient_array = $email_addresses;
        // $email->setMemberGroups(ee('Model')->get('MemberGroup', $groups)->all());
        $email->save();
   
        // add to queue
        $queue = ee('Model')->make(
            EXT_SHORT_NAME. ':EmailQueue',
            array(
                'email_id' => $email->cache_id,
                'queue_start' => ee()->localize->now,
                'recipient_count' => count($email_addresses),
                'messages' => ''
            )
        );
       
        $queue->save();


        // Is Batch Mode set?

        $batch_mode = bool_config_item('email_batchmode');
        $batch_size = (int) ee()->config->item('email_batch_size');

        if (count($email_addresses) <= $batch_size) {
            $batch_mode = false;
        }

        //** ----------------------------------------
        //  If batch-mode is not set, send emails
        // ----------------------------------------*/
        
        if ($batch_mode == false) {
            $total_sent = $this->_deliverManyEmails($email);
           
            $debug_msg = ee()->email->print_debugger(array());

            $this->deleteAttachments($email); // Remove attachments now
            $service = $this->get_service();
            // ee()->dbg->c_log($debug_msg != '', __METHOD__, true);
            if ($debug_msg != "") {
                if (!is_null($service)) {
                    $debug_msg .= sprintf(lang('missing_service_credentials'), ucfirst($service), ucfirst($service));
                }
            } else {
                $debug_msg = sprintf(lang('sent_service'), ucfirst($service));
            }
            // ee()->db->query('truncate table exp_email_queue_plus');
            ee()->view->set_message('success', lang('total_emails_sent').' '.$total_sent, $debug_msg, true);
            // ee()->dbg->c_log($debug_msg, __METHOD__. ' Result '.__LINE__, true);
            ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/'.$sender));
        }

        if ($batch_size === 0) {
            show_error(lang('batch_size_is_zero'));
        }

        /* ----------------------------------------
        **  Start Batch-Mode
        ** ----------------------------------------*/

        // ee()->functions->redirect(ee('CP/URL',EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:compose'));
        ee()->view->set_refresh(ee('CP/URL', EXT_SETTINGS_PATH.'/email/batch/'.$email->cache_id)->compile(), 6, true);

        ee('CP/Alert')->makeStandard('batchmode')
            ->asWarning()
            ->withTitle(lang('batchmode_ready_to_begin'))
            ->addToBody(lang('batchmode_warning'))
            ->defer();
        ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/'.$sender));
    }

    /**
     * Batch Email Send.
     *
     * Sends email in batch mode
     *
     * @param int $id The cache_id to send
     */
    public function batch($id)
    {
        ee()->load->library('email');

        if (ee()->config->item('email_batchmode') != 'y') {
            show_error(lang('batchmode_disabled'));
        }

        if (!ctype_digit($id)) {
            show_error(lang('problem_with_id'));
        }

        $email = ee('Model')->get(EXT_SHORT_NAME.':', $id)->first();

        if (is_null($email)) {
            show_error(lang('cache_data_missing'));
        }

        $start = $email->total_sent;

        $this->_deliverManyEmails($email);

        if ($email->total_sent == count($email->recipient_array)) {
            $debug_msg = ee()->email->print_debugger(array());

            $this->deleteAttachments($email); // Remove attachments now

            ee()->view->set_message('success', lang('total_emails_sent').' '.$email->total_sent, $debug_msg, true);
            ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:compose'));
        } else {
            $stats = str_replace('%x', ($start + 1), lang('currently_sending_batch'));
            $stats = str_replace('%y', ($email->total_sent), $stats);

            $message = $stats.BR.BR.lang('emails_remaining').NBS.NBS.(count($email->recipient_array) - $email->total_sent);

            ee()->view->set_refresh(ee('CP/URL', EXT_SETTINGS_PATH.'/'.EXT_SHORT_NAME.'/email:batch/'.$email->cache_id)->compile(), 6, true);

            ee('CP/Alert')->makeStandard('batchmode')
                ->asWarning()
                ->withTitle($message)
                ->addToBody(lang('batchmode_warning'))
                ->defer();

            ee()->functions->redirect(is_valid_uri);
        }
    }

    /**
     * Fetches an email from the cache and presents it to the user for re-sending.
     *
     * @param int $id The cache_id to send
     */
    public function resend($id)
    {
        if (!ctype_digit($id)) {
            show_error(lang('problem_with_id'));
        }

        $caches = ee('Model')->get(EXT_SHORT_NAME. ':EmailCachePlus', $id)
            ->with('MemberGroups')
            ->all();

        $email = $caches[0];

        if (is_null($email)) {
            show_error(lang('cache_data_missing'));
        }

        ee()->dbg->c_log($email->subject, __METHOD__ . '  ' . __LINE__);

        return $this->compose($email);
    }

    /**
     * Sends a single email handling errors.
     *
     * @param obj    $email  An EmailCachePlus object
     * @param string $to     An email address to send to
     * @param bool   $delete Delete email attachments after send?
     *
     * @return string A response messge as a result of sending the email
     */
    private function deliverOneEmail(EmailCachePlus $email, $to, $delete = true)
    {
        $error = false;

        if (!$this->deliverEmail($email, $to, $email->cc, $email->bcc)) {
            $error = true;
        }

        if ($delete) {
            $this->deleteAttachments($email); // Remove attachments now
        }

        $debug_msg = ee()->email->print_debugger(array());

        if ($error == true) {
            $this->_removeMail($email);
        }

        $total_sent = 0;

        foreach (array($to, $email->cc, $email->bcc) as $string) {
            if ($string != '') {
                $total_sent += substr_count($string, ',') + 1;
            }
        }

        // Save cache data
        $email->total_sent = $total_sent;
        $email->save();

        return $debug_msg;
    }

    /**
     * Sends multiple emails handling errors.
     *
     * @param EmailCachePlus $email An EmailCachePlus object
     *
     * @return int The number of emails sent
     */
    private function _deliverManyEmails(EmailCachePlus $email)
    {
        $recipient_array = array_slice($email->recipient_array, $email->total_sent);
        $number_to_send = count($recipient_array);
        $csv_lookup_loaded = (count($this->_csv_lookup) > 0);

        if ($number_to_send < 1) {
            return 0;
        }

        if (ee()->config->item('email_batchmode') == 'y') {
            $batch_size = (int) ee()->config->item('email_batch_size');

            if ($number_to_send > $batch_size) {
                $number_to_send = $batch_size;
            }
        }
        
        $formatted_message = $email->message = $this->formatMessage($email, true);
        for ($x = 0; $x < $number_to_send; ++$x) {
            $email_address = array_shift($recipient_array);
            ee()->dbg->c_log($this->_csv_lookup, __METHOD__ . ' Lookup Table ' . __LINE__);
            ee()->dbg->c_log($email_address, __METHOD__ . ' indiv email ' . __LINE__);
            if ($csv_lookup_loaded) {
                $record = $this->_csv_lookup[$email_address];
                // ee()->dbg->c_log(isset($record['{{first_name}}']) && isset($record['{{last_name}}']), __METHOD__ . '  ' . __LINE__);
                // standard 'First Last <email address> format
                if (isset($record['{{first_name}}']) && isset($record['{{last_name}}'])) {
                    $to = "{$record['{{first_name}}']} {$record['{{last_name}}']}  <{$record[$this->_csv_email_column]}>"; //TODO: https://trello.com/c/1lffhlXm
                } else {
                    $to = $record[$this->_csv_email_column];
                }

                $cache_data = array(
                    'cache_date' => ee()->localize->now,
                    'parent_id' => $email->cache_id,
                    'total_sent' => 0,
                    'from_name' => $email->from_name,
                    'from_email' => $email->from_email,
                    'recipient' => $to,
                    'cc' => $email->cc,
                    'bcc' => $email->bcc,
                    'recipient_array' => $this->_recipient_array($email_address),
                    'subject' => str_replace('(TEMPLATE) ', '', $email->subject),
                    'message' => $formatted_message,
                    'mailtype' => $email->mailtype,
                    'wordwrap' => $email->wordwrap,
                    'text_fmt' => $email->text_fmt,
                    'total_sent' => 0,
                    'plaintext_alt' => $email->message,
                    'attachments' => $this->_attachments,
                    'csv_object' => array($record),
                    'mailKey' => $this->_csv_email_column
                );
                $cache_data['lookup'] = $record;
                $cache_data['html'] = $formatted_message;
                $cache_data['extras'] = $this->extras;
                ee()->dbg->c_log($cache_data, __METHOD__ . ' Indiv Cache Presave ' . __LINE__);
                
                // ee()->dbg->c_log($email->cache_id, __METHOD__ . ' Template Email ' . __LINE__, true);
                if ($this->email_send($cache_data)) {
                    $cache_data['message'] =  strtr($formatted_message, $record);
                    $this->_saveSingleEmail($email, $cache_data);
                } else {
                    $cache_data['message'] =  strtr($formatted_message, $record);
                    if (!$this->deliverEmail($email, $email_address)) {
                        $this->_removeMail($email);
                    }
                    $this->_saveSingleEmail($email, $cache_data);
                }
            } elseif (!$this->deliverEmail($email, $email_address)) {
                $this->_removeMail($email);
            }
            ++$email->total_sent;
        }
        $email->save();

        return $email->total_sent;
    }
    public function addItem($serializedArray, $item)
    {
        $a = unserialize($serializedArray);
        $a[] = $item;
        return serialize($a);
    }
    private function _saveSingleEmail(EmailCachePlus $email, $data)
    {
        $queue = ee('Model')
            ->get(EXT_SHORT_NAME. ':EmailQueue')
            ->filter('email_id', $email->cache_id)->first();
        
        $queue->messages .= "Message sent to ".$data['recipient']."\n";
        $queue->save();
        
        $singleEmail = ee('Model')->make(EXT_SHORT_NAME. ':EmailCachePlus', $data);
        ++$singleEmail->total_sent;
        $singleEmail->save();
        
        ee()->dbg->c_log(sprintf("Parent: %s Child: %s", $data['parent_id'], $singleEmail->cache_id), __METHOD__ . ' Template Email ' . __LINE__);
        return $singleEmail->cache_id;
    }

    private function _removeMail(EmailCachePlus $email)
    {
        $errors[] = ee()->email->print_debugger();
        $email->clear();
        $err_msg = lang('compose_error').BR.BR.implode('\n', $debug_msg);
        ee()->dbg->c_log($err_msg, __METHOD__ . ' email rem dbg ' . __LINE__);
        ee()->logger->developer($err_msg);
        //show_error($err_msg);
        return false;
    }

    
    /**
     * Delivers an email.
     *
     * @param obj    $email An EmailCachePlus object
     * @param string $to    An email address to send to
     * @param string $cc    An email address to cc
     * @param string $bcc   An email address to bcc
     *
     * @return bool True on success; False on failure
     */
    private function deliverEmail(EmailCachePlus $email, $to, $cc = null, $bcc = null)
    {
        ee()->email->clear(true);
        ee()->email->wordwrap = $email->wordwrap;
        ee()->email->mailtype = $email->mailtype;
        ee()->email->from($email->from_email, $email->from_name);
        ee()->email->to($to);

        if (!is_null($cc)) {
            ee()->email->cc($email->cc);
        }

        if (!is_null($bcc)) {
            ee()->email->bcc($email->bcc);
        }

        ee()->email->subject($this->censorSubject($email));
        ee()->email->message($this->formatMessage($email), $email->plaintext_alt);

        foreach ($email->attachments as $attachment) {
            ee()->email->attach($attachment);
        }
        ee()->dbg->c_log(ee()->email->print_debugger(), __METHOD__ . '  ' . __LINE__);

        return ee()->email->send(false);
    }

    /**
     * Formats the message of an email based on the text format type.
     *
     * @param obj $email An EmailCachePlus object
     *
     * @return string The  message
     */
    private function formatMessage(EmailCachePlus $email, $markdown_only = false)
    {
        $message = $email->message;
        if ($email->text_fmt != 'none' && $email->text_fmt != '') {
            ee()->load->library('typography');
            ee()->typography->initialize(array(
                'bbencode_links' => false,
                'parse_images' => false,
                'parse_smileys' => false,
            ));
            if ($markdown_only) {
                $message = ee()->typography->markdown($email->message, array(
                        'convert_curly' => false,
                ));
            } else {
                $message = ee()->typography->parse_type($email->message, array(
                    'text_format' => $email->text_fmt,
                    'convert_curly' => false,
                    'html_format' => 'all',
                    'auto_links' => 'n',
                    'allow_img_url' => 'y',
                ));
            }
        }

        return $message;
    }

    /**
     * Censors the subject of an email if necessary.
     *
     * @param obj $email An EmailCachePlus object
     *
     * @return string The censored subject
     */
    private function censorSubject(EmailCachePlus $email)
    {
        ee()->dbg->c_log($email, __METHOD__ . '  ' . __LINE__);
        $subject = $email->subject;

        if (bool_config_item('enable_censoring')) {
            $subject = (string) ee('Format')->make('Text', $subject)->censor();
        }

        return $subject;
    }

    public function email_send($data)
    {
        $settings = $this->u_getCurrentSettings();
        if (empty($settings['service_order'])) {
            return false;
        }

        ee()->lang->loadfile(EXT_SHORT_NAME);
        ee()->load->library('logger');

        $sent = false;
        $this->email_in = $data;
        unset($data);

        $this->email_out['lookup'] = $this->email_in['lookup'];

        $this->email_in['finalbody'] = $this->email_in['message'];

        $this->email_out['html'] = $this->email_in['html'];

        $this->email_out['extras'] = $this->email_in['extras'];

        if (isset($this->email_in['template_content'])) {
            $this->email_out['template_content'] = $this->email['template_content'];
        }
        // Set X-Mailer
        $this->email_out['headers']['X-Mailer'] = APP_NAME.' (via '.EXT_NAME.' '.EXT_VERSION.')';

        // From (may include a name)
        $this->email_out['from'] = array(
            'name' => $this->email_in['from_name'],
            'email' => $this->email_in['from_email'],
        );

        // Reply-To (may include a name)
        if (!empty($this->email_in['headers']['Reply-To'])) {
            $this->email_out['reply-to'] = $this->_name_and_email($this->email_in['headers']['Reply-To']);
        }

        // To (email-only)
        $this->email_out['to'] = array($this->email_in['recipient']);

        // Cc (email-only)
        if (!empty($this->email_in['cc_array'])) {
            $this->email_out['cc'] = array();
            foreach ($this->email_in['cc_array'] as $cc_email) {
                if (!empty($cc_email)) {
                    $this->email_out['cc'][] = $cc_email;
                }
            }
        } elseif (!empty($this->email_in['cc'])) {
            $this->email_out['cc'] = $this->email_in['cc'];
        }

        // Bcc (email-only)
        if (!empty($this->email_in['bcc_array'])) {
            $this->email_out['bcc'] = array();
            foreach ($this->email_in['bcc_array'] as $bcc_email) {
                if (!empty($bcc_email)) {
                    $this->email_out['bcc'][] = $bcc_email;
                }
            }
        } elseif (!empty($this->email_in['headers']['Bcc'])) {
            $this->email_out['bcc'] = $this->_recipient_array($this->email_in['headers']['Bcc']);
        }

        // Subject
        $subject = '';
        if (!empty($this->email_in['subject'])) {
            $subject = $this->email_in['subject'];
        } elseif (!empty($this->email_in['headers']['Subject'])) {
            $subject = $this->email_in['headers']['Subject'];
        }
        $this->email_out['subject'] = (strpos($subject, '?Q?') !== false) ? $this->_decode_q($subject) : $subject;

        // Set HTML/Text and attachments
        // $this->_body_and_attachments();
       
        $this->log_array = array();
        //TODO: fix whatever is causing a memory leak https://trello.com/c/uSm8oQEO/23-memory-error
        foreach ($this->available_services as $service) {
            if (!empty($settings[$service.'_active']) && $settings[$service.'_active'] == 'y') {
                $missing_credentials = true;
                ee()->dbg->c_log($this->available_services, __METHOD__. ' Available svc ' . memory_get_usage());
                ee()->dbg->c_log($service, __METHOD__ . ' attempt svc ' . __LINE__);
                ee()->dbg->c_log(ee()->load->is_loaded($service), __METHOD__. ' loaded? ' . memory_get_usage());
                if (!ee()->load->is_loaded($service)) {
                    ee()->load->library('TxService/drivers/TxService_'.ucfirst($service), array_merge($settings, array('debug' => $this->u_debug_enabled())), $service);
                    ee()->dbg->c_log(ee()->load->is_loaded($service), __METHOD__. ' reload? ' . memory_get_usage());
                }
                $result = ee()->{$service}->sendEmail($this->email_out);
                $missing_credentials = $result['missing_credentials'];
                $sent = $result['sent'];

                $success = (!$missing_credentials && ($sent === true or count($sent) > 0));
                
                if ($success) {
                    ee()->extensions->end_script = true;
                    ee()->dbg->c_log($success, __METHOD__. ' - ' . memory_get_usage() . ' Success? :' . $service);
                    break;
                } else {
                    ee()->dbg->c_log($result, __METHOD__. ' - ' . memory_get_usage() . ' Faild result:' . $service);
                }
                    
                //collect errors and don't use service again until next "Send";
                if ($missing_credentials === true) {
                    $this->log_array[] = sprintf(lang('missing_service_credentials'), ucfirst($service), ucfirst($service));
                } elseif ($sent == false or count($sent) == 0) {
                    $this->log_array[] = sprintf(lang('could_not_deliver'), ucfirst($service));
                }
                $this->_service_unavailable($service);
            }
        }
        if (count($this->log_array) > 0) {
            ee()->logger->developer(implode('\n', $this->log_array));
        }
        return $success;
    }

    public function _service_unavailable($service)
    {
        $this->available_services = array_splice($this->available_services, array_search($service, $this->available_services), 1);
        ee()->dbg->c_log($this->available_services, __METHOD__. ' ' . memory_get_usage());
    }

    public function get_service()
    {
        if (!isset($this->service)) {
            $service = ee()->mail_svc->get_initial_service();
            $file_path = sprintf(PATH_THIRD.'manymailerplus/libraries/TxService/drivers/TxService_%s.php', ucfirst($service));
            $this->service = strtolower($service);
            ee()->dbg->c_log($file_path, __METHOD__ . ": Path");
            if (!ee()->load->is_loaded($service)) {
                ee()->dbg->c_log(ee()->load->is_loaded(strtolower($service)), __METHOD__. ": ${service}");
                if (file_exists($file_path)) {
                    ee()->load->library('TxService/drivers/TxService_'.ucfirst($service), array(), $this->service);
                } else {
                    ee()->dbg->c_log("Missing Class file for $service", __METHOD__ . '  ' . __LINE__);
                    return null;
                }
            }
        }
        
        return $this->service;
    }

    public function delete_template($template_name)
    {
        $service = $this->get_service();
        if (!is_null($service)) {
            return ee()->{$service}->delete_template(array('template_name' => $template_name));
        }

        return false;
    }
   
    public function _get_service_templates(...$args)
    {
        $templates = array();
        $req_settings = $args[0];

        ee()->dbg->c_log($req_settings, __METHOD__ . '  ' . __LINE__);
        $service = (array_key_exists('service', $req_settings)) ? $req_settings['service'] : $this->get_service();
        if (!is_null($service)) {
            $templates = ee()->{$service}->getTemplates($req_settings);
        }
        ee()->dbg->c_log($templates, __METHOD__ . '  ' . __LINE__);

        return $templates;
    }

    /**
        Remove the Q encoding from our subject line
     **/
    public function _decode_q($subject)
    {
        $r = '';
        $lines = preg_split('/['.$this->email_crlf.']+/', $subject); // split multi-line subjects
        foreach ($lines as $line) {
            $str = '';
            // $line = str_replace('=9', '', $line); // Replace encoded tabs which ratch the decoding
            $parts = imap_mime_header_decode(trim($line)); // split and decode by charset
            foreach ($parts as $part) {
                $str .= $part->text; // append sub-parts of line together
            }
            $r .= $str; // append to whole subject
        }

        return $r;
        // return utf8_encode($r);
    }

    /**
        Breaks the PITA MIME message we receive into its constituent parts
     **/
    public function _body_and_attachments()
    {
        ee()->dbg->c_log($this->protocol, __METHOD__ . '  ' . __LINE__);
        if ($this->protocol == 'mail') {
            // The 'mail' protocol sets Content-Type in the headers
            if (strpos($this->email_in['header_str'], 'Content-Type: text/plain') !== false) {
                $this->email_out['text'] = $this->email_in['finalbody'];
            } elseif (strpos($this->email_in['header_str'], 'Content-Type: text/html') !== false) {
                $this->email_out['html'] = $this->email_in['finalbody'];
            } else {
                preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['header_str'], $matches);
            }
        } else {
            // SMTP and sendmail will set Content-Type in the body
            if (stripos($this->email_in['finalbody'], 'Content-Type: text/plain') === 0) {
                $this->email_out['text'] = $this->_clean_chunk($this->email_in['finalbody']);
            } elseif (stripos($this->email_in['finalbody'], 'Content-Type: text/html') === 0) {
                $this->email_out['html'] = $this->_clean_chunk($this->email_in['finalbody']);
            } else {
                preg_match('/^Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $this->email_in['finalbody'], $matches);
            }
        }

        // Extract content and attachments from multipart messages
        if (!empty($matches) && !empty($matches[1])) {
            $boundary = $matches[1];
            $chunks = explode('--'.$boundary, $this->email_in['finalbody']);
            foreach ($chunks as $chunk) {
                if (stristr($chunk, 'Content-Type: text/plain') !== false) {
                    $this->email_out['text'] = $this->_clean_chunk($chunk);
                }

                if (stristr($chunk, 'Content-Type: text/html') !== false) {
                    $this->email_out['html'] = $this->_clean_chunk($chunk);
                }

                // Attachments
                if (stristr($chunk, 'Content-Disposition: attachment') !== false) {
                    preg_match('/Content-Type: (.*?); name=["|\'](.*?)["|\']/is', $chunk, $attachment_matches);
                    if (!empty($attachment_matches)) {
                        if (!empty($attachment_matches[1])) {
                            $type = $attachment_matches[1];
                        }
                        if (!empty($attachment_matches[2])) {
                            $name = $attachment_matches[2];
                        }
                        $attachment = array(
                            'type' => trim($type),
                            'name' => trim($name),
                            'content' => $this->_clean_chunk($chunk),
                        );
                        $this->email_out['attachments'][] = $attachment;
                    }
                }

                if (stristr($chunk, 'Content-Type: multipart') !== false) {
                    // Another multipart chunk - contains the HTML and Text messages, here because we also have attachments
                    preg_match('/Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"/i', $chunk, $inner_matches);
                    if (!empty($inner_matches) && !empty($inner_matches[1])) {
                        $inner_boundary = $inner_matches[1];
                        $inner_chunks = explode('--'.$inner_boundary, $chunk);
                        foreach ($inner_chunks as $inner_chunk) {
                            if (stristr($inner_chunk, 'Content-Type: text/plain') !== false) {
                                $this->email_out['text'] = $this->_clean_chunk($inner_chunk);
                            }

                            if (stristr($inner_chunk, 'Content-Type: text/html') !== false) {
                                $this->email_out['html'] = $this->_clean_chunk($inner_chunk);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($this->email_out['html'])) {
            // HTML emails will have been run through quoted_printable_encode
            $this->email_out['html'] = quoted_printable_decode($this->email_out['html']);
        }
    }

    /**
        Explodes a string which contains either a name and email address or just an email address into an array
     **/
    public function _name_and_email($str)
    {
        $r = array(
            'name' => '',
            'email' => '',
        );

        $str = str_replace('"', '', $str);
        if (preg_match($this->_email_regex, $str, $email_matches)) {
            $r['email'] = trim($email_matches[1]);
            $str = trim(preg_replace($this->_email_regex, '', $str));
            if (!empty($str) && $str != $r['email']) {
                $r['name'] = utf8_encode($str);
            }
        } else {
            $r['email'] = trim($str);
        }

        return $r;
    }

    /**
        Explodes a comma-delimited string of email addresses into an array
     **/
    public function _recipient_array($recipient_str)
    {
        $recipients = explode(',', $recipient_str);
        $r = array();
        foreach ($recipients as $recipient) {
            $r[] = trim($recipient);
        }

        return $r;
    }

    /**
        Implodes an array of email addresses and names into a comma-delimited string
     **/
    public function _recipient_str($recipient_array, $singular = false)
    {
        if ($singular == true) {
            if (empty($recipient_array['name'])) {
                return $recipient_array['email'];
            } else {
                return $recipient_array['name'].' <'.$recipient_array['email'].'>';
            }
        }
        $r = array();
        foreach ($recipient_array as $k => $recipient) {
            if (!is_array($recipient)) {
                $r[] = $recipient;
            } else {
                if (empty($recipient['name'])) {
                    $r[] = $recipient['email'];
                } else {
                    $r[] = $recipient['name'].' <'.$recipient['email'].'>';
                }
            }
        }

        return implode(',', $r);
    }

    /**
        Removes cruft from a multipart message chunk
     **/
    public function _clean_chunk($chunk)
    {
        return trim(preg_replace('/Content-(Type|ID|Disposition|Transfer-Encoding):.*?'.NL.'/is', '', $chunk));
    }

    /**
        Writes our array of base64-encoded attachments into actual files in the tmp directory
     **/
    public function _write_attachments()
    {
        $r = array();
        ee()->load->helper('file');
        foreach ($this->email_out['attachments'] as $attachment) {
            if (write_file(realpath(sys_get_temp_dir()).'/'.$attachment['name'], base64_decode($attachment['content']))) {
                $r[$attachment['name']] = realpath(sys_get_temp_dir()).'/'.$attachment['name'];
            }
        }

        return $r;
    }

    /**
        Translates a multi-dimensional array into the odd kind of array expected by cURL post
     **/
    public function _http_build_post($arrays, &$new = array(), $prefix = null)
    {
        foreach ($arrays as $key => $value) {
            $k = isset($prefix) ? $prefix.'['.$key.']' : $key;
            if (is_array($value)) {
                $this->_http_build_post($value, $new, $k);
            } else {
                $new[$k] = $value;
            }
        }
    }

    
    /**
     * View sent emails.
     */
    public function sent()
    {
        if (!ee()->cp->allowed_group('can_send_cached_email')) {
            show_error(lang('not_allowed_to_email_cache'));
        }

        if (ee()->input->post('bulk_action') == 'remove') {
            ee('Model')->get(EXT_SHORT_NAME. ':EmailCachePlus', ee()->input->get_post('selection'))->all()->delete();
            ee()->view->set_message('success', lang('emails_removed'), '');
        }

        $table = ee('CP/Table', array('sort_col' => 'date', 'sort_dir' => 'desc'));
        $table->setColumns(
            array(
                'subject',
                'date',
                'total_sent',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR,
                ),
                array(
                    'type' => Table::COL_CHECKBOX,
                ),
            )
        );

        $table->setNoResultsText('no_cached_emails', 'create_new_email', ee('CP/URL', EXT_SETTINGS_PATH.'/email/compose'));

        $page = ee()->input->get('page') ? ee()->input->get('page') : 1;
        $page = ($page > 0) ? $page : 1;

        $offset = ($page - 1) * 50; // Offset is 0 indexed

        $count = 0;

        $emails = ee('Model')->get(EXT_SHORT_NAME. ':EmailCachePlus');
        $search = $table->search;
        if (!empty($search)) {
            $emails = $emails->filterGroup()
                ->filter('subject', 'LIKE', '%'.$table->search.'%')
                ->orFilter('message', 'LIKE', '%'.$table->search.'%')
                ->orFilter('from_name', 'LIKE', '%'.$table->search.'%')
                ->orFilter('from_email', 'LIKE', '%'.$table->search.'%')
                ->orFilter('recipient', 'LIKE', '%'.$table->search.'%')
                ->orFilter('cc', 'LIKE', '%'.$table->search.'%')
                ->orFilter('bcc', 'LIKE', '%'.$table->search.'%')
                ->endFilterGroup();
        }

        $count = $emails->count();

        $sort_map = array(
            'date' => 'cache_date',
            'subject' => 'subject',
            'status' => 'status',
            'total_sent' => 'total_sent',
        );
        
        // $limit = $this->_getConfigValue('default_sent_rows.default', 500);
        // ee()->dbg->c_log($limit, __METHOD__.' '.__LINE__, true);
        $emails = $emails->order($sort_map[$table->sort_col], $table->sort_dir)
            ->limit(500)
            ->offset($offset)
            ->all();
        // $emails = $emails->all();

        $vars['emails'] = array();
        $data = array();
        foreach ($emails as $email) {
            // Prepare the $email object for use in the modal
            $email->text_fmt = ($email->text_fmt != 'none') ?: 'br'; // Some HTML formatting for plain text
            // $email->subject = htmlentities($this->censorSubject($email), ENT_QUOTES, 'UTF-8');

            $data[] = array(
                $email->subject,
                ee()->localize->human_time($email->cache_date->format('U')),
                $email->total_sent,
                array('toolbar_items' => array(
                    'view' => array(
                        'title' => lang('view_email'),
                        'href' => '',
                        'id' => $email->cache_id,
                        'rel' => 'modal-email-'.$email->cache_id,
                        'class' => 'm-link',
                    ),
                    'sync' => array(
                        'title' => lang('resend'),
                        'href' => ee('CP/URL', EXT_SETTINGS_PATH.'/email/resend/'.$email->cache_id),
                    ), ),
                ),
                array(
                    'name' => 'selection[]',
                    'value' => $email->cache_id,
                    'data' => array(
                        'confirm' => lang('view_email_cache').': <b>'.$email->subject.'(x'.$email->total_sent.')</b>',
                    ),
                ),
            );

            ee()->load->library('typography');
            ee()->typography->initialize(
                array(
                'bbencode_links' => false,
                'parse_images' => false,
                'parse_smileys' => false,
                    )
            );

            $email->message = ee()->typography->parse_type(
                $email->message,
                array(
                'text_format' => ($email->text_fmt == 'markdown') ? 'markdown' : 'xhtml',
                'html_format' => 'all',
                'auto_links' => 'n',
                'allow_img_url' => 'y',
                    )
            );

            $vars['emails'][] = $email;
        }

        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);
        $table->setData($data);

        $base_url = ee('CP/URL', EXT_SETTINGS_PATH.'/email/sent');
        $vars['table'] = $table->viewData($base_url);

        // $vars['pagination'] = ee('CP/Pagination', $count)
        //     ->currentPage($page)
        //     ->render($vars['table']['base_url']);

        // Set search results heading
        // if (!empty($vars['table']['search'])) {
        //     ee()->view->cp_heading = sprintf(
        //         lang('search_results_heading'),
        //         $vars['table']['total_rows'],
        //         htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
        //     );
        // }

        $vars['base_url'] = $base_url;
        $vars['cp_page_title'] = lang('view_email_cache');
        ee()->javascript->set_global('lang.remove_confirm', lang('view_email_cache').': <b>### '.lang('emails').'</b>');
        $vars['current_service'] = __FUNCTION__;
        $vars['save_btn_text'] = '';
        $vars['save_btn_text_working'] = '';
        $vars['sections'] = array();

        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);

        return $vars;
    }

    /**
     * View templates.
     */
    public function view_templates()
    {
        if (ee()->input->post('bulk_action') == 'remove') {
            foreach (ee()->input->get_post('selection') as $slug) {
                $selection = str_replace('_', ' ', $slug);
                $return = $this->delete_template($selection);
            }
            ee()->view->set_message('success', lang('templates_removed'), '');
            ee()->functions->redirect(ee('CP/URL', EXT_SETTINGS_PATH.'/email/view_templates/'));
        }

        // $table = ee('CP/Table', array('sort_col' => 'date', 'sort_dir' => 'desc'));
        $table = ee('CP/Table', array('fieldname' => 'templates'));
        $table->setColumns(
            array(
                'name',
                'created_at',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR,
                ),
                array(
                    'type' => Table::COL_CHECKBOX,
                ),
            )
        );

        $table->setNoResultsText('no_cached_templates', 'create_new_template', ee('CP/URL', EXT_SETTINGS_PATH.'/email/edit_template'));

        $page = ee()->input->get('page') ? ee()->input->get('page') : 1;
        $page = ($page > 0) ? $page : 1;

        $offset = ($page - 1) * 50; // Offset is 0 indexed
        $service_name = $this->get_service();
        $templates = (!$service_name) ? array() : $this->_get_service_templates(array('service' => strtolower($service_name)));
        $data = array();
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $template = json_decode(json_encode($template), true);
                $data[] = array(
                    $template['name'],
                    $template['created_at'],
                    array(
                        'toolbar_items' => array(
                            'view' => array(
                                'title' => lang('view_template'),
                                'href' => '',
                                'id' => $template['slug'],
                                'rel' => 'modal-template-'.$template['slug'],
                                'class' => 'm-link',
                            ),
                            'edit' => array(
                                'title' => lang('edit_template'),
                                'href' => ee('CP/URL', EXT_SETTINGS_PATH.'/email/edit_template/'.$template['name']),
                            ),
                        ),
                    ),
                    array(
                        'name' => 'selection[]',
                        'value' => $template['slug'],
                        'data' => array(
                            'confirm' => lang('view_template_cache').': <b>'.$template['subject'].'</b>',
                        ),
                    ),
                );

                $vars['templates'][] = $template;
            }
        }

        $table->setData($data);
        $count = 1;
        $base_url = ee('CP/URL', EXT_SETTINGS_PATH.'/email/view_templates');
        $vars['table'] = $table->viewData($base_url);

        // $vars['pagination'] = ee('CP/Pagination', $count)
        //     ->currentPage($page)
        //     ->render($vars['table']['base_url']);

        // Set search results heading
        if (!empty($vars['table']['search'])) {
            ee()->view->cp_heading = sprintf(
                lang('search_results_heading'),
                $vars['table']['total_rows'],
                htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
            );
        }

        $vars['cp_heading'] = sprintf(lang('view_template_cache'), ucfirst($service_name));
        ee()->javascript->set_global('lang.remove_confirm', lang('view_template_cache').': <b>### '.lang('templates').'</b>');

        // ee()->cp->add_js_script(array( 'file' => array('cp/confirm_remove'),));
        $vars['base_url'] = $base_url;
        $vars['cp_page_title'] = sprintf(lang('view_template_cache'), ucfirst($service_name));
        ee()->javascript->set_global('lang.remove_confirm', lang('view_template_cache').': <b>### '.lang('templates').'</b>');
        $vars['current_service'] = __FUNCTION__;
        $vars['save_btn_text'] = '';
        $vars['save_btn_text_working'] = '';
        $vars['sections'] = array();

        ee()->dbg->c_log($vars, __METHOD__ . '  ' . __LINE__);
        return $vars;
    }

    /**
     * Check for recipients.
     *
     * An internal validation function for callbacks
     *
     * @param	string
     *
     * @return bool
     */
    public function _check_for_recipients($str)
    {
        ee()->dbg->c_log($str, __METHOD__, true);
        if (!$str && ee()->input->post('total_gl_recipients') < 1) {
            ee()->form_validation->set_message('_check_for_recipients', lang('required'));

            return false;
        }

        return true;
    }

    /**
     * Attachment Handler.
     *
     * Used to manage and validate attachments. Must remain public,
     * it's a form validation callback.
     *
     * @return bool
     */
    public function _attachment_handler()
    {
        // File Attachments?
        if (!isset($_FILES['attachment']['name']) or empty($_FILES['attachment']['name'])) {
            return true;
        }

        ee()->load->library('upload');
        ee()->upload->initialize(array(
            'allowed_types' => '*',
            'use_temp_dir' => true,
        ));

        if (!ee()->upload->do_upload('attachment')) {
            ee()->form_validation->set_message('_attachment_handler', lang('attachment_problem'));

            return false;
        }

        $data = ee()->upload->data();

        $this->_attachments[] = $data['full_path'];

        return true;
    }

    /**
     * Delete Attachments.
     */
    private function deleteAttachments($email)
    {
        ee()->dbg->c_log($email, __METHOD__ . '  ' . __LINE__);
        foreach ($email->attachments as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $email->attachments = array();
        $email->save();
    }
}
// EOF
