<?php
/**
 * ManyMailerPlus English Language file
 *
 * @category Language_File
 * @package  ManyMailerPlus
 * @author   Tony Moses <tonymoses@texasbluesalley.com>
 * @license  MIT http://url.com
 * @link     http://url.com
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
$email_detail = EXT_NAME.'\'s ';
$email_detail .= <<<HERE
Composer Is just like the built in "Communicate" utility with one enhancement. It provides options for: <blockquote>
	<ol>
		<li>Pasting the contents of a csv file</li>
		<li>Upload a csv file to be scanned</li>
	</ol>
</blockquote>
<h3>Required Columns: </h3>
<dl>
	<dt>An Email Column:</dt>
	<dd>column title is some form of the following string(email, mail, e-mail, address)</dd>
	<dt>An First Name Column:</dt><dd>column title is some form of the following string(first, given, forename)</dd>
	<dt>An Last Name Column:</dt><dd>column title is some form of the following string(last, surname)</dd>
</dl>
<p>The rest of the column headings will be parsed and provided as "tokenized" placeholders after: pasting/uploading file</p> 


HERE;
$sent_mail_title = 'View Sent Mail';
$saved_temps_title = 'View Saved Templates';
$lang = array(
    'add_member_groups_desc' => 'Send Email to all members in chosen group(s).',
    'add_member_groups' => 'Add member group(s)',
    'attachment_desc' => 'Attachments are not saved, after sending.',
    'attachment' => 'Attachment',
    'bcc_recipients_desc' => 'BCC Email(s). Separate multiple recipients with a comma.',
    'bcc_recipients' => 'BCC recipient(s)',
    'cc_recipients_desc' => 'CC Email(s). Separate multiple recipients with a comma.',
    'cc_recipients' => 'CC recipient(s)',
    'code_desc' => 'the HTML code for the template with mc:edit attributes for the editable elements',
    'code' => 'Html Code',
    'compose_csv_recipient_type' => ($recip_csv_type = 'CSV'),
    'compose_default_recipient_type' => 'Default',
    'compose_desc' => EXT_NAME.' should be familiar. It is the same as the default ExpressionEngine "Communicate"',
    'compose_detail' => EXT_NAME.' provides a few methods for adding recipents: default, csv pasting, csv import',
    'compose_email_detail' => 'Email Detail',
    'compose_error_desc' => 'We were unable to send this Email, please review and fix errors below.',
    'compose_error' => 'Attention: Email not sent',
    'compose_file_recipient_type' => ($recip_file_type = 'Upload CSV'),
    'compose_heading' => 'Compose Email ',
    'compose_name' => ($compose_name = 'Compose New Email'),
    'compose2_name' => $compose_name.' (Stepped)',
    'compose_send_email' => 'Send Email',
    'compose_sending_email' => 'Sending Email',
    'compose_title' => EXT_NAME.'\'s Composer',
    'config_warning_heading' => EXT_SHORT_NAME.' is configured elsewhere',
    'config_warning_text' => 'You appear to have '.EXT_NAME.' configured via config.php, so changes you make here may be overridden.',
    'could_not_deliver' => EXT_NAME.' tried to deliver your email with %s but the service failed.',
    'create_new_email' => 'Create New Email?',
    'create_new_template' => 'Create New Template',
    'created_at' => 'Created',
    'csv_recipient_desc' => 'Paste Raw CSV data',
    'csv_recipient' => 'CSV data',
    'description' => 'Description',
    'debug_mode' => 'Debugging Mode',
    'debug_mode_desc' => 'Use the developer tools to view logged info (experimental)',
    'default_sent_rows' => 'Sent Rows',
    'default_sent_rows_desc' => "# of row to show on '${sent_mail_title}' screen",
    'default_template_rows' => 'Template Rows',
    'default_template_rows_desc' => "# of row to show on '${saved_temps_title}' screen",
    'details' => ' Details',
    'disabled' => 'Disabled',
    'edit_template_name' => 'Create/Edit Templates',
    'edit_template' => 'Create/Edit Template',
    'elapsed' => 'Run Time',
    'email_heading' => 'Email Functions',
    'email_subject' => 'Email Subject',
    'email_text' => $email_detail,
    'email_title' => 'Email Functions',
    'email_title' => 'Email Functions',
    'emails' => ' emails',
    'emails_removed' => 'Emails Removed',
    'enabled' => 'Enabled',
    'fakeservice_description' => 'FakeService is a service offered by MailChimp as an add-on to their paid monthly accounts. Sign-up at <a href="%s">http://mandrill.com</a>.',
    'fakeservice_name' => 'FakeService',
    'file_recipient_desc' => 'Upload csv file below',
    'file_recipient' => 'CSV file upload',
    'from_email_desc' => 'a default sending address for emails sending. <b><i>(Validation: email)</i></b>',
    'from_email' => 'Sender&apos;s Email',
    'from_name_desc' => 'a default name to be used',
    'from_name' => 'Sender&apos;s Name',
    'index_heading' => EXT_DISPLAY_NAME.' Overview',
    'index_text' => ($test = '<p>'.EXT_NAME.' will route all emails generated by ExpressionEngine&reg; through a supported third-party transactional email service.</p>'),
    'index_title' => 'Overview',
    'init' => 'Initializing...',
    'labels_desc' => 'an optional array of up to 10 labels to use for filtering purposes',
    'labels' => 'Labels',
    'language_options' => 'Language Options',
    'mailgun_api_key' => 'API Key',
    'mailgun_description' => 'MailGun is run by RackSpace, and offers 10,000 email sends per month on their free plan. Sign-up at <a href="%s">http://mailgun.com</a>.',
    'mailgun_domain' => 'Domain Name',
    'mailgun_link' => 'http://mailgun.com',
    'mailgun_name' => 'MailGun',
    'mandrill_api_key' => 'API Key',
    'mandrill_description' => 'Mandrill is a service offered by MailChimp as an add-on to their paid monthly accounts. Sign-up at <a href="%s">http://mandrill.com</a>.',
    'mandrill_link' => 'http://mandrill.com',
    'mandrill_name' => 'Mandrill',
    'mandrill_subaccount' => 'Subaccount <i>(optional)</i>',
    'mandrill_test_api_key' => 'Test API Key <i>(optional)</i>',
    'mandrill_testmode__yes_no' => 'Test Mode',
    'manual' => 'Manual Entry of CSV Data',
    'message' => 'Email Body',
    'message_desc' => '',
    'message_success' => 'Saved successfully',
    'missing_service_credentials' => 'You have %s activated as a service in '.EXT_NAME.', but you are missing some required credentials to send email with this service.'.BR.BR.' Please visit your '.EXT_NAME.' %s services screen to fix this.',
    'missing_service_class' => 'You have %s activated as a service in '.EXT_NAME.', but you are missing this service\'s class file.',
    'no_cached_emails' => 'No Sent Email',
    'no_cached_templates' => 'No <b>mail templates</b> found!',
    'no_csv' => 'Individual Emails',
    'no_mail_processes' => 'No running processes at this time',
    'of' => ' of ',
    'optional' => '(Optional)',
    'option_name' => '',
    'other_recipient_options' => 'Additional Recipient Options',
    'plaintext_alt_desc' => 'Alternate content for your HTML Email, will be delivered in Plain Text, when an Email application cannot render HTML',
    'plaintext_alt' => 'Alternate content for your HTML Email, will be delivered in Plain Text, when an Email application cannot render HTML.',
    'plaintext_body' => 'Plain Text Body',
    'postageapp_api_key' => 'API Key',
    'postageapp_description' => 'PostageApp offers 100 email sends per day on their free plan. Sign-up at <a href="%s">http://postageapp.com</a>.',
    'postageapp_link' => 'http://postageapp.com',
    'postageapp_name' => 'PostageApp',
    'postmark_api_key' => 'API Key',
    'postmark_description' => 'Postmark offers your first 25,000 email sends free, followed by pay-as-you-go pricing. Sign-up at <a href="%s">http://postmarkapp.com</a>. <i>Note: the From address of each email must be approved in your Postmark dashboard in order to send successfully.</i>',
    'postmark_link' => 'http://postmarkapp.com',
    'postmark_name' => 'Postmark',
    'preferences_updated' => 'Settings updated!',
    'primary_recipient_type_desc' => 'Enable CSV recipient option?',
    'primary_recipient_type' => 'Recipent Entry Type',
    'primary_recipients_desc' => 'To Email(s). Separate multiple recipients with a comma.',
    'primary_recipients' => 'Recipient(s) Emails',
    'publish_desc' => 'set to false to add a draft template without publishing',
    'publish' => 'Publish',
    'recipient_entry_desc' => 'Choose a method of entry',
    'recipient_entry' => 'Recipient Entry Methods',
    'recipient_options' => 'CSV Recipient Options',
    'recipient_review_desc' => ' ',
    'recipient_review' => 'CSV Data',
    'recipient_type_desc' => "Default: Type email <br/>{$recip_csv_type}: Paste contents of CSV File <br/> {$recip_file_type}: Upload local CSV File",
    'recipient_type' => 'Recipient Entry Method',
    'recipients' => 'Recipient(s)',
    'reset_success_title' => 'Success',
    'reset_success_body' => 'Preferences have been successfully cleared',
    'reset_error_title' => 'Error',
    'reset_error_body' => 'Preferences have not been cleared',
    'save_settings' => 'Save Settings',
    'saveTemplateErrorDesc' => 'We were unable to save this template in it\'s current state. Please fix errors below',
    'saveTemplateError' => 'Error Saving Template',
    'saveTemplate' => 'Save Template',
    'search_emails_button' => 'Search Emails',
    'search_templates_button' => 'Search Templates',
    'send_as' => 'Send As: ',
    'sender_info' => 'Sender Info',
    'sendgrid_api_key' => 'API Key',
    'sendgrid_description' => 'SendGrid offers 12,000 email sends per month on their free plan. Sign-up at <a href="%s">http://sendgrid.com</a>.',
    'sendgrid_link' => 'http://sendgrid.com',
    'sendgrid_name' => 'SendGrid',
    'sent' => 'Sent ',
    'sent_name' => $sent_mail_title,
    'sent_service' => 'Your message has been successfully sent using the following service: %s ',
    'services_heading' => 'Services Overview',
    'services_name' => 'Services',
    'services_text' => '<p>Activate and configure as many services as you like via the links in the sidebar.</p>
		<p>If the topmost activated service fails to send a particular email, the next active service will be used.<p> If all active services fail, your email will be sent via ExpressionEngine&reg;&rsquo;s default email method.</p>',//If the topmost activated service fails to send a particular email, the next active service will be used.
    'services_title' => 'Services',
    'services' => 'Services',
    'settings' => 'Module Settings',
    'settings_title' => 'Settings/Preferences',
    'sparkpost_api_key' => 'API Key',
    'sparkpost_description' => 'SparkPost offers 100,000 email sends per month on their free plan. Sign-up at <a href="%s">http://sparkpost.com</a>.',
    'sparkpost_link' => 'http://sparkpost.com',
    'sparkpost_name' => 'SparkPost',
    'status' => 'Service Status',
    'subject_desc' => 'Default Subject Line',
    'subject' => 'Subject',
    'template_error' => 'Error Saving Template',
    'template_name_desc' => 'the name for the new template - must be unique. <b><i> (Validation: required)</i></b>',
    'template_name' => 'Template Name',
    '_template_name' => '',
    'text_desc' => 'a default text part to be used when sending with this template.',
    'text' => 'Default Text',
    'total_emails_sent' => 'Totals Emails Sent: ',
    'upload' => 'Upload CSV File',
    'use_template' => 'Use Template',
    'use_templates_desc' => 'Templates from the first service in the service order will be available',
    'use_templates' => 'Use Template',
    'using_alt_credentials' => 'You have %s activated as a service in '.EXT_NAME.' and are using test credentials: %s.'.BR.BR.'Current <u>"%s"</u> Settings as follows: '.BR.BR.'%s',
    'view_email_cache' => 'Sent Emails',
    'view_template' => 'Quick View',
    'view_template_cache' => 'Saved %s Templates',
    'view_templates_name' => $saved_temps_title,
    'word_wrap' => 'Word Wrap',
    'your_email' => 'Email of Sender',
);
