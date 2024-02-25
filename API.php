<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Http;
use Piwik\Option;

class API extends \Piwik\Plugin\API
{
	private static $instance;

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function getDatabaseFile()
	{
		if (is_file(self::getDatabasePath())) {
			Option::set('IP2Location.BIN', basename(self::getDatabasePath()));

			return basename(self::getDatabasePath());
		}

		$files = scandir(PIWIK_DOCUMENT_ROOT . '/misc');

		foreach ($files as $file) {
			if (preg_match('/^(IP(V6)?-COUNTRY.+|IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?)\.BIN$/', $file)) {
				Option::set('IP2Location.BIN', $file);

				return $file;
			}
		}
	}

	public static function getDatabaseDate()
	{
		if (!is_file(self::getDatabasePath())) {
			return '';
		}

		require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Location/lib/IP2Location.php';

		$db = new \IP2Location\Database(self::getDatabasePath(), \IP2Location\Database::FILE_IO);

		if (!$db) {
			return '';
		}

		$parts = explode('.', $db->getDatabaseVersion());

		return $parts[0] . '-' . str_pad($parts[1], 2, '0', \STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', \STR_PAD_LEFT);
	}

	public static function getDatabaseSize()
	{
		if (!file_exists(self::getDatabasePath())) {
			return 0;
		}

		return self::displayBytes(filesize(self::getDatabasePath()));
	}

	public static function getLookupMode()
	{
		return (Option::get('IP2Location.LookupMode')) ? Option::get('IP2Location.LookupMode') : 'BIN';
	}

	public static function getDatabasePath()
	{
		$databasePath = Option::get('IP2Location.DatabasePath');

		if (empty($databasePath)) {
			$databasePath = PIWIK_DOCUMENT_ROOT . '/misc/' . Option::get('IP2Location.BIN');
		}

		return Option::get('IP2Location.DatabasePath');
	}

	public static function getWsApiKey()
	{
		return Option::get('IP2Location.APIKey');
	}

	public static function getIoApiKey()
	{
		return Option::get('IP2Location.IOAPIKey');
	}

	public static function setLookupMode($value)
	{
		Option::set('IP2Location.LookupMode', $value);
	}

	public static function setDatabasePath($file)
	{
		if (is_file($file)) {
			Option::set('IP2Location.DatabasePath', $file);
		}
	}

	public static function setAPIKey($value, $service = 'WS')
	{
		if ($service == 'WS') {
			Option::set('IP2Location.APIKey', $value);
		} else {
			Option::set('IP2Location.IOAPIKey', $value);
		}
	}

	public static function getWebServiceCredit($apiKey = '')
	{
		if (!$apiKey) {
			$apiKey = self::getWsApiKey();
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
