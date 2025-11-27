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

	public function config($errors = [])
	{
		Piwik::checkUserHasSuperUserAccess();

		$request = \Piwik\Request::fromRequest();

		$saved = (empty($errors) && $request->getStringParameter('submit', '')) ?: false;

		$lookupMode = $request->getStringParameter('lookupMode', IP2LocationPlugin::getLookupMode());
		$databasePath = $request->getStringParameter('databasePath', IP2LocationPlugin::getDatabasePath());
		$apiKey = $request->getStringParameter('apiKey', IP2LocationPlugin::getWsApiKey());
		$ioApiKey = $request->getStringParameter('ioApiKey', IP2LocationPlugin::getIoApiKey());
		$downloadToken = $request->getStringParameter('downloadToken', IP2LocationPlugin::getDownloadToken());
		$databaseCode = $request->getStringParameter('databaseCode', IP2LocationPlugin::getDatabaseCode());

		$file = IP2LocationPlugin::getDatabaseFile();

		$date = ($file) ? IP2LocationPlugin::getDatabaseDate($file) : '';
		$size = ($file) ? IP2LocationPlugin::getDatabaseSize($file) : 0;

		if ($lookupMode == 'BIN') {
			if (!$file) {
				$errors[] = 'No IP2Location BIN found in "' . dirname(IP2LocationPlugin::getDatabasePath()) . '"';
			}

			if ($date && strtotime($date) < strtotime('-2 months')) {
				$errors[] = 'Your IP2Location BIN file version is outdated. Please visit http://www.ip2location.com to download the latest BIN file."';
			}
		}

		$view = new View('@IP2Location/config');
		$view->language = LanguagesManager::getLanguageCodeForCurrentUser();

		$this->setBasicVariablesView($view);
		$view->assign('saved', $saved);
		$view->assign('errors', $errors);

		$view->assign('lookupMode', $lookupMode);
		$view->assign('databasePath', $databasePath);
		$view->assign('examplePath', PIWIK_DOCUMENT_ROOT . '/misc/IP-COUNTRY.BIN');
		$view->assign('apiKey', $apiKey);
		$view->assign('apiKey', $apiKey);
		$view->assign('ioApiKey', $ioApiKey);
		$view->assign('downloadToken', $downloadToken);
		$view->assign('databaseCode', $databaseCode);

		$view->assign('database', $file);
		$view->assign('date', $date);
		$view->assign('size', $size);
		$view->assign('credit', number_format(IP2LocationPlugin::getWebServiceCredit(), 0, '', ','));

		$view->nonce = Nonce::getNonce('IP2Location.saveConfig');
		$view->adminMenu = MenuAdmin::getInstance()->getMenu();
		$view->topMenu = MenuTop::getInstance()->getMenu();
		$view->notifications = NotificationManager::getAllNotificationsToDisplay();
		$view->phpVersion = PHP_VERSION;
		$view->phpIsNewEnough = version_compare($view->phpVersion, '5.3.0', '>=');

		echo $view->render();
	}

	public function saveConfig()
	{
		Piwik::checkUserHasSuperUserAccess();
		$request = \Piwik\Request::fromRequest();

		$errors = [];

		$lookupMode = $request->getStringParameter('lookupMode', '');
		$databasePath = $request->getStringParameter('databasePath', '');
		$apiKey = $request->getStringParameter('apiKey', '');
		$apiKey = $request->getStringParameter('apiKey', '');
		$ioApiKey = $request->getStringParameter('ioApiKey', '');
		$downloadToken = $request->getStringParameter('downloadToken', '');
		$databaseCode = $request->getStringParameter('databaseCode', '');

		if ($lookupMode == 'BIN') {
			if (!is_file($databasePath)) {
				$errors[] = Piwik::translate('IP2Location_NoIP2LocationDatabaseFile');
			}
		}

		if (!empty($_POST)) {
			if ($lookupMode == 'WS') {
				if (!$apiKey) {
					$errors[] = Piwik::translate('IP2Location_PleaseEnterAValidAPIKey');
				} elseif (!IP2LocationPlugin::getWebServiceCredit($apiKey)) {
					$errors[] = Piwik::translate('IP2Location_PleaseEnterAValidAPIKey');
				}
			}

			if ($lookupMode == 'IO') {
				if (!preg_match('/^[0-9A-Z]{32}$/', $ioApiKey)) {
					$errors[] = Piwik::translate('IP2Location_PleaseEnterAValidAPIKey');
				}
			}

			if (empty($errors)) {
				IP2LocationPlugin::setLookupMode($lookupMode);
				IP2LocationPlugin::setDatabasePath($databasePath);
				IP2LocationPlugin::setDownloadToken($downloadToken);
				IP2LocationPlugin::setDatabaseCode($databaseCode);

				if ($lookupMode == 'WS') {
					IP2LocationPlugin::setAPIKey($apiKey);
				} elseif ($lookupMode == 'IO') {
					IP2LocationPlugin::setAPIKey($ioApiKey, $lookupMode);
				}
			}
		}

		$this->config($errors);
	}
}
