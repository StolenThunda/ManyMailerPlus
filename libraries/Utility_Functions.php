<?php
namespace ManyMailerPlus\libraries;

/**
 * Utility functions for MMP modules
 */
trait Utility_Functions
{
    public static function _getExtModel()
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
    public static function u_getCurrentSettings()
    {
        $model = Utility_Functions::_getExtModel();
        $id = Utility_Functions::u_getSiteID();
        return (array_key_exists($id, $model->settings)) ? $model->settings[$id] : $model->settings;
    }

    public function u_debug_enabled()
    {
        $model = Utility_Functions::_getExtModel();
        return (array_key_exists('debug_mode', $model->settings) && $model->settings['config']['debug_mode'] === 'y');
    }

    public static function u_getSiteID()
    {
        return ee()->config->item('site_id');
    }

    public static function u_saveSettings($settings)
    {
        $model = Utility_Functions::_getExtModel();
        $merge_settings =  empty($settings) ? $settings : array_merge(Utility_Functions::u_getcurrentSettings(), $settings);
        $model->settings = $merge_settings;
        // ee()->dbg->c_log($merge_settings, __METHOD__, true);
        $model->save();
        return true;
    }

    
    static function u_messages($name, $title, $body, $success = true, $defer = true)
    {
        $alert = ee('CP/Alert')->makeInline($name);

        
        if ($success) {
            $alert->asSuccess();
        } else { 
            $alert->asWarning();
        }
        $alert->canClose()->withTitle($title)->addToBody($body);

        if ($defer) {
            $alert->defer();
        } else {
            $alert->now();
        }
    }
    //DEPRECATED 
    public function u_setDEBUG($val = false)
    {
        $model = Utility_Functions::_getExtModel();
        $id = Utility_Functions::u_getSiteID();
        $setting = ($val) ? 'y' : 'n';
        if (array_key_exists($id, $model->settings)) {
            $current = $model->settings[$id];
            if (array_key_exists('debug_mode', $current)) {
                $current['debug_mode'] = $setting;
                Utility_Functions::u_saveSettings($current);
                // ee()->dbg->c_log($current, __METHOD__, true);
            }
        }
        return Utility_Functions::u_getCurrentSettings();
    }
}
