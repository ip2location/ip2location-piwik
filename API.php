<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Container\StaticContainer;
use Piwik\Http;
use Piwik\Option;

class API extends \Piwik\Plugin\API
{
	private static $instance = null;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function getDatabaseFile()
	{
		$files = scandir(StaticContainer::get('path.ip2location'));

		foreach ($files as $file) {
			if (preg_match('/^(IP(V6)?-COUNTRY.+|IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?)\.BIN$/', $file)) {
				Option::set('IP2Location.BIN', $file);

				return $file;
			}
		}

		foreach ($files as $file) {
			if (strtoupper(substr($file, -4)) == '.BIN') {
				Option::set('IP2Location.BIN', $file);

				return $file;
			}
		}

		return null;
	}

	public static function getDatabaseDate($file)
	{
		if (!is_file(StaticContainer::get('path.ip2location') . $file)) {
			return;
		}

		require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Location/lib/IP2Location.php';

		$db = new \IP2Location\Database(StaticContainer::get('path.ip2location') . $file, \IP2Location\Database::FILE_IO);

		return $db->getDate();
	}

	public static function getDatabaseSize($file)
	{
		if (!file_exists(StaticContainer::get('path.ip2location') . $file)) {
			return 0;
		}

		return self::displayBytes(filesize(StaticContainer::get('path.ip2location') . $file));
	}

	public static function getLookupMode()
	{
		return (Option::get('IP2Location.LookupMode')) ? Option::get('IP2Location.LookupMode') : 'BIN';
	}

	public static function getAPIKey()
	{
		return (Option::get('IP2Location.APIKey')) ? Option::get('IP2Location.APIKey') : '';
	}

	public static function setLookupMode($value)
	{
		Option::set('IP2Location.LookupMode', ($value == 'WS') ? 'WS' : 'BIN');
	}

	public static function setAPIKey($value)
	{
		Option::set('IP2Location.APIKey', $value);
	}

	public static function getWebServiceCredit($apiKey = '')
	{
		if (!$apiKey) {
			$apiKey = self::getAPIKey();
		}

		if (!$apiKey) {
			return 0;
		}

		if (($json = json_decode(Http::sendHttpRequest('https://api.ip2location.com/v2/?key=' . $apiKey . '&check=1', 30))) === null) {
			return 0;
		}

		if (preg_match('/^[0-9]+$/', $json->response)) {
			return (int) $json->response;
		}

		return 0;
	}

	private static function displayBytes($bytes)
	{
		$ext = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		$unitCount = 0;

		for (; $bytes > 1024; ++$unitCount) {
			$bytes /= 1024;
		}

		return number_format($bytes, 2, '.', ',') . ' ' . $ext[$unitCount];
	}
}
