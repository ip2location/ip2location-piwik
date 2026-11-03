<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Nonce;
use Piwik\Notification\Manager as NotificationManager;
use Piwik\Piwik;
use Piwik\Plugins\IP2Location\API as IP2LocationPlugin;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Translation\Translator;
use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    private const API_KEY_MASK = '********';

    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();
    }

    /**
     * Renders the configuration page.
     *
     * @param string $textSuccess
     * @param array $errors
     */
    public function config($textSuccess = '', $errors = [])
    {
        Piwik::checkUserHasSuperUserAccess();

        $request = \Piwik\Request::fromRequest();

        $saved = (empty($errors) && $request->getStringParameter('submit', '')) ?: false;

        $lookupMode = $request->getStringParameter('lookupMode', IP2LocationPlugin::getLookupMode());
        $databasePath = $request->getStringParameter('databasePath', IP2LocationPlugin::getDatabasePath());
        $databasePath = (substr($databasePath, -3) == 'BIN') ? dirname($databasePath) : $databasePath;

        $apiKey = IP2LocationPlugin::getApiKey() ? self::API_KEY_MASK : '';
        $downloadToken = $request->getStringParameter('downloadToken', IP2LocationPlugin::getDownloadToken());
        $databaseCode = $request->getStringParameter('databaseCode', IP2LocationPlugin::getDatabaseCode());
        $includeIPv6 = $request->getStringParameter('includeIPv6', preg_match('/IPV6/', $databaseCode));
        $scheduledTask = $request->getStringParameter('scheduledTask', IP2LocationPlugin::getScheduledTask());
        $lastScheduledTaskDate = IP2LocationPlugin::getLastScheduledTaskDate();
        $lastScheduledTaskDate = (!$lastScheduledTaskDate) ? Piwik::translate('IP2Location_NeverExecuted') : $lastScheduledTaskDate;

        $binFile = IP2LocationPlugin::getDatabaseFile();

        $date = ($binFile) ? IP2LocationPlugin::getDatabaseDate($binFile) : '';
        $size = ($binFile) ? IP2LocationPlugin::getDatabaseSize($binFile) : 0;

        $binFileIsMissing = ($lookupMode == 'BIN' && !$binFile);

        $view = new View('@IP2Location/config');
        $view->language = LanguagesManager::getLanguageCodeForCurrentUser();

        $this->setBasicVariablesView($view);
        $view->assign('saved', $saved);
        $view->assign('textSuccess', $textSuccess);
        $view->assign('errors', $errors);
        $view->assign('lookupMode', $lookupMode);
        $view->assign('binFileIsMissing', $binFileIsMissing);
        $view->assign('databasePath', $databasePath);
        $view->assign('apiKey', $apiKey);
        $view->assign('downloadToken', $downloadToken);
        $view->assign('databaseCode', $databaseCode);
        $view->assign('includeIPv6', $includeIPv6);

        $view->assign('database', $binFile);
        $view->assign('date', $date);
        $view->assign('size', $size);

        $view->assign('scheduledTask', $scheduledTask);
        $view->assign('lastScheduledTaskDate', $lastScheduledTaskDate);

        $view->nonceSaveLookupMode = Nonce::getNonce('IP2Location.saveLookupMode');
        $view->nonceDownloadBinDatabase = Nonce::getNonce('IP2Location.downloadBinDatabase');
        $view->nonceSaveBinDatabase = Nonce::getNonce('IP2Location.saveBinDatabase');
        $view->nonceSaveScheduledTask = Nonce::getNonce('IP2Location.saveScheduledTask');
        $view->nonceSaveGeolocationAPIService = Nonce::getNonce('IP2Location.saveGeolocationAPIService');

        $view->adminMenu = MenuAdmin::getInstance()->getMenu();
        $view->topMenu = MenuTop::getInstance()->getMenu();
        $view->notifications = NotificationManager::getAllNotificationsToDisplay();

        echo $view->render();
    }

    /**
     * Saves the lookup mode.
     */
    public function saveLookupMode()
    {
        Piwik::checkUserHasSuperUserAccess();
        $request = \Piwik\Request::fromRequest();

        Nonce::checkNonce('IP2Location.saveLookupMode', $request->getStringParameter('nonce', ''));

        $lookupMode = $request->getStringParameter('lookupMode', '');

        if (in_array($lookupMode, ['BIN', 'WS'])) {
            IP2LocationPlugin::setLookupMode($lookupMode);
        }

        $this->config(Piwik::translate('IP2Location_LookupModeHasBeenSaved'));
    }

    /**
     * Downloads the database using the submitted token.
     */
    public function downloadBinDatabase()
    {
        Piwik::checkUserHasSuperUserAccess();
        $request = \Piwik\Request::fromRequest();

        Nonce::checkNonce('IP2Location.downloadBinDatabase', $request->getStringParameter('nonce', ''));

        $downloadToken = $request->getStringParameter('downloadToken', '');

        if (!$downloadToken) {
            $this->config('', [
                Piwik::translate('IP2Location_InvalidDownloadToken'),
            ]);
            return;
        }

        if (!IP2LocationPlugin::checkDownloadToken($downloadToken, 'DB1BIN')) {

            if (!IP2LocationPlugin::checkDownloadToken($downloadToken, 'DB1LITEBIN')) {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidDownloadToken'),
                ]);
                return;
            }

            IP2LocationPlugin::setDatabaseCode('DB3LITEBIN');
        } else {
            IP2LocationPlugin::setDatabaseCode('DB3BIN');
        }

        IP2LocationPlugin::setDownloadToken($downloadToken);

        if (!IP2LocationPlugin::downloadBinDatabase()) {
            $this->config('', [
                Piwik::translate('IP2Location_InvalidDownloadToken'),
            ]);
            return;
        }

        $this->config(Piwik::translate('IP2Location_DatabaseHasBeenDownloaded'));
    }

    /**
     * Saves the download settings and downloads the database.
     */
    public function saveBinDatabase()
    {
        Piwik::checkUserHasSuperUserAccess();
        $request = \Piwik\Request::fromRequest();

        Nonce::checkNonce('IP2Location.saveBinDatabase', $request->getStringParameter('nonce', ''));

        $downloadToken = trim($request->getStringParameter('downloadToken', ''));
        $databaseCode = trim($request->getStringParameter('databaseCode', ''));
        $databasePath = trim($request->getStringParameter('databasePath', ''));
        $includeIPv6 = trim($request->getStringParameter('includeIPv6', ''));

        if (!preg_match('/^[a-zA-Z0-9]{64}$/', $downloadToken)) {
            $downloadToken = '';
        }

        IP2LocationPlugin::setDownloadToken($downloadToken);

        if ($downloadToken) {
            if (!$databasePath) {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidDatabasePath'),
                ]);
                return;
            }

            if (!IP2LocationPlugin::checkDownloadToken($downloadToken, $databaseCode . ($includeIPv6 ? 'IPV6' : ''))) {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidDownloadToken'),
                ]);
                return;
            }

            if (!is_writable($databasePath)) {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidDatabasePath'),
                ]);
                return;
            }

            IP2LocationPlugin::setDatabaseCode($databaseCode . ($includeIPv6 ? 'IPV6' : ''));
            IP2LocationPlugin::setDatabasePath($databasePath);

            if (!IP2LocationPlugin::downloadBinDatabase()) {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidDownloadToken'),
                ]);
                return;
            }

            $this->config(Piwik::translate('IP2Location_DatabaseHasBeenDownloaded'));
        }

        $this->config(Piwik::translate('IP2Location_DownloadTokenIsRemoved'));
    }

    /**
     * Saves the scheduled update setting.
     */
    public function saveScheduledTask()
    {
        Piwik::checkUserHasSuperUserAccess();
        $request = \Piwik\Request::fromRequest();

        Nonce::checkNonce('IP2Location.saveScheduledTask', $request->getStringParameter('nonce', ''));

        $scheduledTask = $request->getStringParameter('scheduledTask', '');

        if (in_array($scheduledTask, ['off', 'monthly'])) {
            IP2LocationPlugin::setScheduledTask($scheduledTask);
        }

        $this->config(Piwik::translate('IP2Location_ScheduledTaskHasBeenSaved'));
    }

    /**
     * Saves and validates the geolocation API key.
     */
    public function saveGeolocationAPIService()
    {
        Piwik::checkUserHasSuperUserAccess();
        $request = \Piwik\Request::fromRequest();

        Nonce::checkNonce('IP2Location.saveGeolocationAPIService', $request->getStringParameter('nonce', ''));

        $apiKey = $request->getStringParameter('apiKey', '');

        if ($apiKey === self::API_KEY_MASK) {
            if (IP2LocationPlugin::getApiKey()) {
                $this->config(Piwik::translate('IP2Location_GeolocationAPIServiceHasBeenSaved'));
            } else {
                $this->config('', [
                    Piwik::translate('IP2Location_InvalidAPIKey'),
                ]);
            }

            return;
        }

        if (!$apiKey) {
            $this->config('', [
                Piwik::translate('IP2Location_InvalidAPIKey'),
            ]);
            return;
        }

        if (!IP2LocationPlugin::checkAPIKey($apiKey)) {
            $this->config('', [
                Piwik::translate('IP2Location_InvalidAPIKey'),
            ]);
            return;
        }

        IP2LocationPlugin::setApiKey($apiKey);

        $this->config(Piwik::translate('IP2Location_GeolocationAPIServiceHasBeenSaved'));
    }
}
