<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Plugins\UserCountry\LocationProvider;

class IP2Location extends \Piwik\Plugin
{
	public function registerEvents()
	{
		return [];
	}

	public function isTrackerPlugin()
	{
		return true;
	}

	public function deactivate()
	{
		// Switch to default provider if IP2Location provider was in use
		if (LocationProvider::getCurrentProvider() instanceof \Piwik\Plugins\IP2Location\LocationProvider\IP2Location) {
			LocationProvider::setCurrentProvider(LocationProvider\DefaultProvider::ID);
		}
	}
}
