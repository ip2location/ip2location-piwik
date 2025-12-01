<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Http;
use Piwik\Plugin\Tasks as PluginTasks;
use Piwik\Plugins\IP2Location\API as IP2LocationPlugin;

class Tasks extends PluginTasks
{
	public function schedule()
	{
		if (IP2LocationPlugin::getScheduledTask() == 'monthly') {
			$this->monthly('downloadBinDatabase');
		} elseif (IP2LocationPlugin::getScheduledTask() == 'weekly') {
			$this->weekly('downloadBinDatabase');
		}
	}

	public function downloadBinDatabase()
	{
		$token = IP2LocationPlugin::getDownloadToken();
		$code = IP2LocationPlugin::getDatabaseCode();

		if (empty($token) || empty($code)) {
			return;
		}

		$response = Http::sendHttpRequest('https://www.ip2location.com/download-info?' . http_build_query([
			'token'   => $token,
			'package' => $code,
		]), 30);

		if (strpos($response, 'OK') === false) {
			return;
		}

		$parts = explode(';', $response);
		$size = $parts[3] ?? 0;

		if ($size == IP2LocationPlugin::getDownloadedDatabaseSize()) {
			return;
		}

		$databasePath = IP2LocationPlugin::getDatabasePath();
		$databasePath = rtrim((substr($databasePath, -3) == 'BIN') ? dirname($databasePath) : $databasePath, '/');

		$zipFile = $databasePath . '/ip2location.zip';
		$extractPath = $databasePath . '/tmp/';

		echo $zipFile;
		exit;

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
						copy($extractPath . $file, $databasePath . $file);
						IP2LocationPlugin::setDatabasePath($databasePath . $file);
						IP2LocationPlugin::setDownloadedDatabaseSize($size);
						IP2LocationPlugin::setLastScheduledTaskDate(date('Y-m-d H:i:s'));
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
