<?php  if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once(PATH_THIRD.EXT_SHORT_NAME.'/config.php');

class Manymailerplus_upd
{
    public $version = EXT_VERSION;

    // function __construct(){
    // 	if (!ee()->load->is_loaded('dbg')) ee()->load->library('debughelper', array('debug'=>true), 'dbg');
    // }
    public function ee_version()
    {
        return substr(APP_VER, 0, 1);
    }
    

    public function install()
    {
        
        $this->settings = array();
        
        // ADD EXTENSION FOR SERVICES INTEGRATION
        $ext_data = array(
            'class'     => ucfirst(EXT_SHORT_NAME).'_ext',
            'method'    => 'email_send',
            'hook'      => 'email_send',
            'settings'  => serialize($this->settings),
            'version'   => $this->version,
            'priority'  => 1,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $ext_data);

        $mod_data = array(
            'module_name' => EXT_NAME,
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        );

        $previousInstall = ee()->db->get_where('modules', $mod_data);
        if ($previousInstall->num_rows() == 0) {
            ee()->db->insert('modules', $mod_data);
        }
        return $this->createCache();
    }
    

    public function uninstall()
    {
        // ADD EXTENSION FOR SERVICES INTEGRATION
        ee()->db->where('class', ucfirst(EXT_SHORT_NAME).'_ext');
        ee()->db->delete('extensions');

        ee()->db->where('module_name', EXT_NAME);
        ee()->db->delete('modules');

        ee()->db->delete('modules', array( 'module_name' => EXT_NAME));

        ee()->load->dbforge();
        $sql[] = "DROP TABLE IF EXISTS exp_email_cache_plus";

        foreach ($sql as $query) {
            ee()->db->query($query);
        }
        return true;
    }


    public function update($version = '')
    {
        if (version_compare($version, '0.1.4', '>=')) {
            return $this->createCache();
        }
        if (version_compare($version, $this->version) === 0) {
            return false;
        }
        return true;
    }

    public function createCache()
    {
        ee()->load->dbforge();
        $sql[] = "CREATE TABLE IF NOT EXISTS `exp_email_cache_plus`(
			`cache_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  			`cache_date` int(10) unsigned NOT NULL DEFAULT '0',
			`total_sent` int(6) unsigned NOT NULL,
			`from_name` varchar(70) COLLATE utf8mb4_unicode_ci NOT NULL,
			`from_email` varchar(75) COLLATE utf8mb4_unicode_ci NOT NULL,
			`recipient` text COLLATE utf8mb4_unicode_ci NOT NULL,
			`cc` text COLLATE utf8mb4_unicode_ci NOT NULL,
			`bcc` text COLLATE utf8mb4_unicode_ci NOT NULL,
			`recipient_array` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
			`subject` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
			`message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
			`plaintext_alt` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
			`mailtype` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
			`text_fmt` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
			`wordwrap` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'y',
			`attachments` mediumtext COLLATE utf8mb4_unicode_ci,
			`csv_object` json DEFAULT NULL,
			`mailKey` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			PRIMARY KEY (`cache_id`)
			) ENGINE=InnoDB AUTO_INCREMENT=2570 DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);

        foreach ($sql as $query) {
            ee()->db->query($query);
        }
        return true;
    }
}
