<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Plugins\UserCountry\LocationProvider;

class IP2Location extends \Piwik\Plugin
{
	/**
	 * @see Piwik\Plugin::registerEvents
	 */
	public function registerEvents()
	{
		return [
			'Tracker.setTrackerCacheGeneral' => 'setTrackerCacheGeneral',
		];
	}

	public function setTrackerCacheGeneral(&$cache)
	{
		$cache['currentLocationProviderId'] = LocationProvider::getCurrentProviderId();
	}
}
