<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Http;
use Piwik\Plugin\Tasks as PluginTasks;
use Piwik\Plugins\IP2Location\API as IP2LocationPlugin;

class Tasks extends PluginTasks
{
	public function schedule()
	{
		$this->monthly('downloadBinDatabase');
	}

	public function downloadBinDatabase()
	{
		$token = IP2LocationPlugin::getDownloadToken();
		$code = IP2LocationPlugin::getDatabaseCode();

		if (empty($token) || empty($code)) {
			return;
		}

		$url = "https://www.ip2location.com/download?token=$token&file=$code";
		$zipFile = PIWIK_DOCUMENT_ROOT . '/misc/ip2location.zip';
		$extractPath = PIWIK_DOCUMENT_ROOT . '/misc/tmp/';

		if (is_dir($extractPath)) {
			$files = scandir($extractPath);
			foreach ($files as $file) {
				@unlink($extractPath . $file);
			}
		} else {
			mkdir($extractPath);
		}

		try {
			$success = Http::sendHttpRequest($url, 600, null, $zipFile);

			if ($success !== true) {
				return;
			}

			$zip = new \ZipArchive();
			if ($zip->open($zipFile) === true) {
				$zip->extractTo($extractPath);
				$zip->close();

				// Find the BIN file
				$files = scandir($extractPath);
				foreach ($files as $file) {
					if (preg_match('/^IP(V6)?-COUNTRY.*\.BIN$/', $file) || preg_match('/^IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?\.BIN$/', $file)) {
						copy($extractPath . $file, str_replace('/tmp', '', $extractPath) . $file);
						IP2LocationPlugin::setDatabasePath(str_replace('/tmp', '', $extractPath) . $file);
					}

					@unlink($extractPath . $file);
				}
			}

			@unlink($zipFile);
			@rmdir($extractPath);
		} catch (\Exception $e) {
		}
	}
}
