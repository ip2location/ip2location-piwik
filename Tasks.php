<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Http;
use Piwik\Option;
use Piwik\Plugin\Tasks as PluginTasks;

class Tasks extends PluginTasks
{
    /**
     * Registers the database update task.
     */
    public function schedule()
    {
        $scheduledTask = Option::get('IP2Location.ScheduledTask');

        if ($scheduledTask == 'monthly') {
            $this->monthly('downloadBinDatabase');
        } elseif ($scheduledTask == 'weekly') {
            $this->weekly('downloadBinDatabase');
        }
    }

    /**
     * Updates the BIN database when a new build is available.
     */
    public function downloadBinDatabase()
    {
        $token = Option::get('IP2Location.DownloadToken');
        $code = Option::get('IP2Location.DatabaseCode');

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

        $databasePath = Option::get('IP2Location.DatabasePath');
        $hasLocalDatabase = is_file($databasePath);

        if (!$hasLocalDatabase && is_dir($databasePath)) {
            foreach ((array) scandir($databasePath) as $localFile) {
                if (preg_match('/^IP(V6)?-COUNTRY.*\.BIN$/', $localFile) || preg_match('/^IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?\.BIN$/', $localFile)) {
                    $hasLocalDatabase = true;

                    break;
                }
            }
        }

        if ($size == Option::get('IP2Location.DownloadedDatabaseSize') && $hasLocalDatabase) {
            return;
        }

        $databasePath = rtrim((substr($databasePath, -3) == 'BIN') ? dirname($databasePath) : $databasePath, '/');

        if (empty($databasePath)) {
            $databasePath = str_replace('\\', '/', PIWIK_DOCUMENT_ROOT) . '/misc';
        }

        $zipFile = $databasePath . '/ip2location.zip';
        $extractPath = $databasePath . '/tmp/';

        if (is_dir($extractPath)) {
            $files = scandir($extractPath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                @unlink($extractPath . $file);
            }
        } else {
            @mkdir($extractPath);
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

                $files = scandir($extractPath);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (preg_match('/^IP(V6)?-COUNTRY.*\.BIN$/', $file) || preg_match('/^IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?\.BIN$/', $file)) {
                        copy($extractPath . $file, $databasePath . '/' . $file);
                        Option::set('IP2Location.DatabasePath', str_replace('\\', '/', $databasePath . '/' . $file));
                        Option::set('IP2Location.DownloadedDatabaseSize', $size);
                        Option::set('IP2Location.LastScheduledTaskDate', date('Y-m-d H:i:s'));
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
