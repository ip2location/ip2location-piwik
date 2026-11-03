<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Plugins\UserCountry\LocationProvider;

class IP2Location extends \Piwik\Plugin
{
    /**
     * Registers plugin events.
     *
     * @return array
     */
    public function registerEvents()
    {
        return [
            'Tracker.setTrackerCacheGeneral' => 'setTrackerCacheGeneral',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
        ];
    }

    /**
     * Adds the location provider to the tracker cache.
     *
     * @param array $cache
     */
    public function setTrackerCacheGeneral(&$cache)
    {
        $cache['currentLocationProviderId'] = LocationProvider::getCurrentProviderId();
    }

    /**
     * Adds plugin stylesheets.
     *
     * @param array $files
     */
    public function getStylesheetFiles(&$files)
    {
        $files[] = "plugins/IP2Location/stylesheets/style.css";
    }

    /**
     * Adds plugin scripts.
     *
     * @param array $files
     */
    public function getJavaScriptFiles(&$files)
    {
        $files[] = "plugins/IP2Location/javascripts/script.js";
    }
}
