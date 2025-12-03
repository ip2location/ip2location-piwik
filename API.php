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
		return (Option::get('IP2Location.LookupMode')) ? Option::get('IP2Location.LookupMode') : '';
	}

	public static function getDatabasePath()
	{
		return Option::get('IP2Location.DatabasePath');
	}

	public static function checkDownloadToken($token, $code)
	{
		$response = Http::sendHttpRequest('https://www.ip2location.com/download-info?' . http_build_query([
			'token'   => $token,
			'package' => $code,
		]), 30);

		if (strpos($response, 'OK') !== false) {
			return true;
		}

		return false;
	}

	public static function downloadBinDatabase()
	{
		$token = self::getDownloadToken();
		$code = self::getDatabaseCode();
		$databasePath = (substr(self::getDatabasePath(), -3) == 'BIN') ? dirname(self::getDatabasePath()) : self::getDatabasePath();

		if (empty($databasePath)) {
			$databasePath = str_replace('\\', '/', PIWIK_DOCUMENT_ROOT) . '/misc';
		}

		if (empty($token) || empty($code)) {
			return false;
		}

		$zipFile = $databasePath . '/ip2location.zip';
		$extractPath = $databasePath . '/tmp/';

		if (is_dir($extractPath)) {
			$files = scandir($extractPath);
			foreach ($files as $file) {
				@unlink($extractPath . $file);
			}
		} else {
			mkdir($extractPath);
		}

		try {
			$success = Http::sendHttpRequest('https://www.ip2location.com/download?' . http_build_query([
				'token' => $token,
				'file'  => $code,
			]), 600, null, $zipFile);

			if ($success !== true) {
				return false;
			}

			$zip = new \ZipArchive();
			if ($zip->open($zipFile) === true) {
				$zip->extractTo($extractPath);
				$zip->close();

				// Delete existing BIN files
				$files = scandir(str_replace('/tmp', '', $extractPath));
				foreach ($files as $file) {
					if (substr($file, -4) == '.BIN') {
						@unlink(str_replace('/tmp', '', $extractPath) . $file);
					}
				}

				// Find the BIN file
				$files = scandir($extractPath);
				foreach ($files as $file) {
					if (preg_match('/^IP(V6)?-COUNTRY.*\.BIN$/', $file) || preg_match('/^IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?\.BIN$/', $file)) {
						copy($extractPath . $file, str_replace('/tmp', '', $extractPath) . $file);
						self::setDatabasePath(str_replace('/tmp', '', $extractPath) . $file);
					}

					@unlink($extractPath . $file);
				}
			}

			@unlink($zipFile);
			@rmdir($extractPath);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function getDownloadToken()
	{
		return Option::get('IP2Location.DownloadToken');
	}

	public static function getDatabaseCode()
	{
		return Option::get('IP2Location.DatabaseCode');
	}

	public static function setLookupMode($value)
	{
		Option::set('IP2Location.LookupMode', $value);
	}

	public static function setDatabasePath($file)
	{
		Option::set('IP2Location.DatabasePath', str_replace('\\', '/', $file));
	}

	public static function getApiKey()
	{
		return Option::get('IP2Location.ApiKey');
	}

	public static function setAPIKey($value)
	{
		Option::set('IP2Location.ApiKey', $value);
	}

	public static function checkAPIKey($apiKey)
	{
		if (preg_match('/^[0-9A-Z]{10}$/', $apiKey)) {
			// Legacy API key format
			$response = Http::sendHttpRequest('https://api.ip2location.com/v2/?' . http_build_query([
				'key' => $apiKey,
				'check'  => '1',
			]), 30);

			if (($json = json_decode((string) $response)) === null) {
				return false;
			}

			if (!isset($json->response)) {
				return false;
			}

			if (!preg_match('/^[0-9]+$/', $json->response)) {
				return false;
			}
		} elseif (preg_match('/^[0-9A-F]{32}$/', $apiKey)) {
			$response = Http::sendHttpRequest('https://api.ip2location.io/?' . http_build_query([
				'key' => $apiKey,
				'ip'  => '8.8.8.8',
			]), 30);

			if (($json = json_decode((string) $response)) === null) {
				return false;
			}

			if (!isset($json->country_code)) {
				return false;
			}

			if ($json->country_code != 'US') {
				return false;
			}
		} else {
			return false;
		}

		return true;
	}

	public static function setDownloadToken($value)
	{
		Option::set('IP2Location.DownloadToken', $value);
	}

	public static function setDatabaseCode($value)
	{
		Option::set('IP2Location.DatabaseCode', $value);
	}

	public static function setScheduledTask($value)
	{
		Option::set('IP2Location.ScheduledTask', $value);
	}

	public static function getScheduledTask()
	{
		return Option::get('IP2Location.ScheduledTask');
	}

	public static function getDownloadedDatabaseSize()
	{
		return Option::get('IP2Location.DownloadedDatabaseSize');
	}

	public static function setDownloadedDatabaseSize($value)
	{
		Option::set('IP2Location.DownloadedDatabaseSize', $value);
	}

	public static function getLastScheduledTaskDate()
	{
		return Option::get('IP2Location.LastScheduledTaskDate');
	}

	public static function setLastScheduledTaskDate($value)
	{
		Option::set('IP2Location.LastScheduledTaskDate', $value);
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
