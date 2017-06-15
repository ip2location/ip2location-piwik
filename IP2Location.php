<?php
namespace Piwik\Plugins\IP2Location;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Plugins\IP2Location\API as IP2LocationAPI;

class IP2Location extends \Piwik\Plugin
{
	/**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Tracker.setTrackerCacheGeneral'	=> 'setTrackerCacheGeneral',
        );
    }

	public function setTrackerCacheGeneral(&$cache)
    {
        $cache['currentLocationProviderId'] = LocationProvider::getCurrentProviderId();
    }
}
