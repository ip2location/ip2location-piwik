<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Http;
use Piwik\Option;
use Piwik\Piwik;

class API extends \Piwik\Plugin\API
{
    private static $instance;

    /**
     * Returns the plugin API instance.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns the BIN database filename.
     *
     * @return string
     */
    public static function getDatabaseFile()
    {
        Piwik::checkUserHasSuperUserAccess();

        if (is_file(self::getDatabasePath())) {
            Option::set('IP2Location.BIN', basename(self::getDatabasePath()));

            return basename(self::getDatabasePath());
        }

        $files = @scandir(PIWIK_DOCUMENT_ROOT . '/misc');

        if (!is_array($files)) {
            return '';
        }

        foreach ($files as $file) {
            if (preg_match('/^(IP(V6)?-COUNTRY.+|IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?)\.BIN$/', $file)) {
                Option::set('IP2Location.BIN', $file);

                return $file;
            }
        }

        return '';
    }

    /**
     * Returns the database release date.
     *
     * @return string
     */
    public static function getDatabaseDate()
    {
        Piwik::checkUserHasSuperUserAccess();

        if (!is_file(self::getDatabasePath())) {
            return '';
        }

        require_once PIWIK_INCLUDE_PATH . '/plugins/IP2Location/lib/IP2Location.php';

        try {
            $db = new \IP2Location\Database(self::getDatabasePath(), \IP2Location\Database::FILE_IO);
        } catch (\Exception $e) {
            return '';
        }

        $parts = explode('.', $db->getDatabaseVersion());

        if (count($parts) < 3) {
            return '';
        }

        return $parts[0] . '-' . str_pad($parts[1], 2, '0', \STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', \STR_PAD_LEFT);
    }

    /**
     * Returns the database file size.
     *
     * @return string|int
     */
    public static function getDatabaseSize()
    {
        Piwik::checkUserHasSuperUserAccess();

        if (!file_exists(self::getDatabasePath())) {
            return 0;
        }

        return self::displayBytes(filesize(self::getDatabasePath()));
    }

    /**
     * Returns the lookup mode.
     *
     * @return string
     */
    public static function getLookupMode()
    {
        Piwik::checkUserHasSuperUserAccess();

        return (Option::get('IP2Location.LookupMode')) ? Option::get('IP2Location.LookupMode') : '';
    }

    /**
     * Returns the configured database path.
     *
     * @return string|false
     */
    public static function getDatabasePath()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.DatabasePath');
    }

    /**
     * Checks a download token with IP2Location.
     *
     * @param string $token
     * @param string $code
     * @return bool
     */
    public static function checkDownloadToken($token, $code)
    {
        Piwik::checkUserHasSuperUserAccess();

        $response = Http::sendHttpRequest('https://www.ip2location.com/download-info?' . http_build_query([
            'token'   => $token,
            'package' => $code,
        ]), 30);

        if (strpos(ltrim($response), 'OK') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Downloads and installs the BIN database.
     *
     * @return bool
     */
    public static function downloadBinDatabase()
    {
        Piwik::checkUserHasSuperUserAccess();

        $token = self::getDownloadToken();
        $code = self::getDatabaseCode();
        $databasePath = (substr(self::getDatabasePath(), -3) == 'BIN') ? dirname(self::getDatabasePath()) : self::getDatabasePath();

        if (empty($databasePath)) {
            $databasePath = str_replace('\\', '/', PIWIK_DOCUMENT_ROOT) . '/misc';
        }

        if (empty($token) || empty($code)) {
            return false;
        }

        $databasePath = rtrim(str_replace('\\', '/', $databasePath), '/');

        if (!is_dir($databasePath) || !is_writable($databasePath)) {
            return false;
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
                return false;
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                $files = scandir($databasePath);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (substr($file, -4) == '.BIN') {
                        @unlink($databasePath . '/' . $file);
                    }
                }

                $files = scandir($extractPath);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (preg_match('/^IP(V6)?-COUNTRY.*\.BIN$/', $file) || preg_match('/^IP2LOCATION-LITE-DB[0-9]+(\.IPV6)?\.BIN$/', $file)) {
                        copy($extractPath . $file, $databasePath . '/' . $file);
                        self::setDatabasePath($databasePath . '/' . $file);
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

    /**
     * Returns the download token.
     *
     * @return string|false
     */
    public static function getDownloadToken()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.DownloadToken');
    }

    /**
     * Returns the database package code.
     *
     * @return string|false
     */
    public static function getDatabaseCode()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.DatabaseCode');
    }

    /**
     * Saves the lookup mode.
     *
     * @param string $value
     */
    public static function setLookupMode($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.LookupMode', $value);
    }

    /**
     * Saves the database path.
     *
     * @param string $file
     */
    public static function setDatabasePath($file)
    {
        Piwik::checkUserHasSuperUserAccess();

        if (!self::isValidPath($file)) {
            return;
        }

        Option::set('IP2Location.DatabasePath', str_replace('\\', '/', $file));
    }

    /**
     * Returns the geolocation API key.
     *
     * @return string|false
     */
    public static function getApiKey()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.ApiKey');
    }

    /**
     * Saves the geolocation API key.
     *
     * @param string $value
     */
    public static function setAPIKey($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.ApiKey', $value);
    }

    /**
     * Validates a geolocation API key.
     *
     * @param string $apiKey
     * @return bool
     */
    public static function checkAPIKey($apiKey)
    {
        Piwik::checkUserHasSuperUserAccess();

        if (preg_match('/^[0-9A-Z]{10}$/', $apiKey)) {

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

    /**
     * Saves the download token.
     *
     * @param string $value
     */
    public static function setDownloadToken($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.DownloadToken', $value);
    }

    /**
     * Saves the database package code.
     *
     * @param string $value
     */
    public static function setDatabaseCode($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.DatabaseCode', $value);
    }

    /**
     * Saves the update schedule.
     *
     * @param string $value
     */
    public static function setScheduledTask($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.ScheduledTask', $value);
    }

    /**
     * Returns the update schedule.
     *
     * @return string|false
     */
    public static function getScheduledTask()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.ScheduledTask');
    }

    /**
     * Returns the last downloaded database size.
     *
     * @return string|false
     */
    public static function getDownloadedDatabaseSize()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.DownloadedDatabaseSize');
    }

    /**
     * Saves the last downloaded database size.
     *
     * @param string $value
     */
    public static function setDownloadedDatabaseSize($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.DownloadedDatabaseSize', $value);
    }

    /**
     * Returns the last update date.
     *
     * @return string|false
     */
    public static function getLastScheduledTaskDate()
    {
        Piwik::checkUserHasSuperUserAccess();

        return Option::get('IP2Location.LastScheduledTaskDate');
    }

    /**
     * Saves the last update date.
     *
     * @param string $value
     */
    public static function setLastScheduledTaskDate($value)
    {
        Piwik::checkUserHasSuperUserAccess();

        Option::set('IP2Location.LastScheduledTaskDate', $value);
    }

    /**
     * Returns whether a path is safe to store.
     *
     * @param mixed $path
     * @return bool
     */
    private static function isValidPath($path)
    {
        if (!is_string($path) || $path === '' || strpos($path, "\0") !== false) {
            return false;
        }

        return !preg_match('#^[a-z][a-z0-9+.-]*://#i', $path);
    }

    /**
     * Formats a byte count for display.
     *
     * @param int|float $bytes
     * @return string
     */
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
