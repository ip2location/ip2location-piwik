<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id: API.php 4448 2011-04-14 08:20:49Z matt $
 *
 * @category Piwik_Plugins
 * @package Piwik_IP2Location
 */
namespace Piwik\Plugins\IP2Location;

use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\Site;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Option;
use Piwik\Http;

/**
 * @package Piwik_IP2Location
 */
class API extends \Piwik\Plugin\API
{
	static private $instance = null;

	static public function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	static public function getDatabaseFile()
	{
		$files = scandir(PIWIK_DOCUMENT_ROOT . '/misc');

		foreach ( $files as $file ) {
			if (strtoupper(substr($file, -4)) == '.BIN') {
				Option::set('IP2Location.BIN', $file);
				return $file;
			}
		}

		return null;
	}

	static public function getDatabaseDate($file)
	{
		if (!is_file(PIWIK_DOCUMENT_ROOT . '/misc/' . $file))
			return;

		require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Location/lib/IP2Location.php';

		$db = new \IP2Location\Database(PIWIK_DOCUMENT_ROOT . '/misc/' . $file, \IP2Location\Database::FILE_IO);

		return $db->getDate();
	}

	static public function getDatabaseSize($file)
	{
		if (!file_exists(PIWIK_DOCUMENT_ROOT . '/misc/' . $file))
			return 0;

		return self::displayBytes(filesize(PIWIK_DOCUMENT_ROOT . '/misc/' . $file));
	}

	static public function getLookupMode()
	{
		return (Option::get('IP2Location.LookupMode')) ? Option::get('IP2Location.LookupMode') : 'BIN';
	}

	static public function getAPIKey()
	{
		return (Option::get('IP2Location.APIKey')) ? Option::get('IP2Location.APIKey') : '';
	}

	static public function setLookupMode($value)
	{
		Option::set('IP2Location.LookupMode', ($value == 'WS') ? 'WS' : 'BIN');
	}

	static public function setAPIKey($value)
	{
		Option::set('IP2Location.APIKey', $value);
	}

	static public function getWebServiceCredit($apiKey = '')
	{
		if (!$apiKey)
			$apiKey = self::getAPIKey();

		if (!$apiKey) {
			return 0;
		}

		$response = Http::sendHttpRequest('https://api.ip2location.com/?key=' . $apiKey . '&check=1', 30);

		if (preg_match('/^[0-9]+$/', $response)) {
			return (int) $response;
		}

		return 0;
	}

	static private function displayBytes($bytes)
	{
		$ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$unitCount = 0;

		for(; $bytes > 1024; $unitCount++)
			$bytes /= 1024;

		return number_format($bytes, 2, '.', ',') . ' ' . $ext[$unitCount];
	}
}
