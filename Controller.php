<?php

/**
 * Piwik - Open source web analytics.
 *
 * @see http://piwik.org
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @version $Id: Controller.php 4336 2011-04-06 01:52:11Z matt $
 *
 * @category Piwik_Plugins
 */

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
	private $translator;

	public function __construct(Translator $translator)
	{
		$this->translator = $translator;

		parent::__construct();
	}

	public function config($textSuccess = '', $errors = [])
	{
		Piwik::checkUserHasSuperUserAccess();

		$request = \Piwik\Request::fromRequest();

		$saved = (empty($errors) && $request->getStringParameter('submit', '')) ?: false;

		$lookupMode = $request->getStringParameter('lookupMode', IP2LocationPlugin::getLookupMode());
		$databasePath = $request->getStringParameter('databasePath', IP2LocationPlugin::getDatabasePath());
		$databasePath = (substr($databasePath, -3) == 'BIN') ? dirname($databasePath) : $databasePath;
		$apiKey = $request->getStringParameter('apiKey', IP2LocationPlugin::getApiKey());
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

		// Check if can download DB3 commercial database
		if (!IP2LocationPlugin::checkDownloadToken($downloadToken, 'DB1BIN')) {
			// Check if can download DB3 LITE database
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

	public function saveBinDatabase()
	{
		Piwik::checkUserHasSuperUserAccess();
		$request = \Piwik\Request::fromRequest();

		Nonce::checkNonce('IP2Location.saveBinDatabase', $request->getStringParameter('nonce', ''));

		$downloadToken = $request->getStringParameter('downloadToken', '');
		$databaseCode = $request->getStringParameter('databaseCode', '');
		$databasePath = $request->getStringParameter('databasePath', '');
		$includeIPv6 = $request->getStringParameter('includeIPv6', '');

		if (!$downloadToken) {
			$this->config('', [
				Piwik::translate('IP2Location_InvalidDownloadToken'),
			]);
			return;
		}

		if (!$databasePath) {
			$this->config('', [
				Piwik::translate('IP2Location_InvalidDatabasePath'),
			]);
			return;
		}

		// Check for download permission
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

		IP2LocationPlugin::setDownloadToken($downloadToken);
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

	public function saveGeolocationAPIService()
	{
		Piwik::checkUserHasSuperUserAccess();
		$request = \Piwik\Request::fromRequest();

		Nonce::checkNonce('IP2Location.saveGeolocationAPIService', $request->getStringParameter('nonce', ''));

		$apiKey = $request->getStringParameter('apiKey', '');

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
