<?php
namespace ManyMailerPlus\libraries;

/**
 * Utility function for MMP
 */
trait Utility_Functions
{   
    static function _getExtModel()
    {
        $id = Utility_Functions::u_getSiteID();
        return ee('Model')
            ->get('Extension')
            ->filter('class', ucfirst(EXT_SHORT_NAME).'_ext')
            ->first();
    }
    /**
     * Returns current save settings
     *
     * @return array
     */
    static function u_getCurrentSettings()
    {
        $model = Utility_Functions::_getExtModel();
        $id = Utility_Functions::u_getSiteID();
        return (array_key_exists($id, $model->settings)) ? $model->settings[$id] : $model->settings;
    }

    public function u_debug_enabled()
    {
        $model = Utility_Functions::_getExtModel();
        return (array_key_exists('debug_mode', $model->settings) && $model->settings['debug_mode'] === 'y');
    }

    static function u_getSiteID()
    {
        return ee()->config->item('site_id');
    }

    static function u_saveSettings($settings) {
         $model = Utility_Functions::_getExtModel();
         $model->settings = $settings;
         return $model->save();
    }

    public function u_setDEBUG($val = false) {
        $model = Utility_Functions::_getExtModel();
        $id = Utility_Functions::u_getSiteID();
        $setting = ($val) ? 'y' : 'n';
        if (array_key_exists($id, $model->settings)) {
            $current = $model->settings[$id];
        // ee()->dbg->c_log($model->settings[$id], __METHOD__, true);
            if (array_key_exists('debug_mode', $model->settings[$id])) {
                $current['debug_mode'] = $setting;
                Utility_Functions::u_saveSettings($current);
                ee()->dbg->c_log($current, __METHOD__, true);
            }
        }
        return Utility_Functions::u_getCurrentSettings();
    }
}