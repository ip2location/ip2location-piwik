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
			'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
			'AssetManager.getJavaScriptFiles' => 'getJavaScriptFiles',
		];
	}

	public function setTrackerCacheGeneral(&$cache)
	{
		$cache['currentLocationProviderId'] = LocationProvider::getCurrentProviderId();
	}

	public function getStylesheetFiles(&$files)
	{
		$files[] = "plugins/IP2Location/stylesheets/style.css";
	}

	public function getJavaScriptFiles(&$files)
	{
		$files[] = "plugins/IP2Location/javascripts/script.js";
	}
}
