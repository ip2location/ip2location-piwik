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

use Piwik\Common;
use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Nonce;
use Piwik\Notification\Manager as NotificationManager;
use Piwik\Piwik;
use Piwik\Plugins\IP2Location\API as APIIP2Location;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Site;
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

	public function config($siteId = 0, $errors = [])
	{
		Piwik::checkUserHasSuperUserAccess();

		if ($siteId == 0) {
			$siteId = Common::getRequestVar('idSite');
		}

		$saved = (empty($errors) && Common::getRequestVar('submit', '')) ?: false;

		$lookupMode = (Common::getRequestVar('lookupMode', '')) ? trim(Common::getRequestVar('lookupMode', '')) : APIIP2Location::getLookupMode();
		$apiKey = (Common::getRequestVar('apiKey', '')) ? trim(Common::getRequestVar('apiKey', '')) : APIIP2Location::getAPIKey();

		$file = APIIP2Location::getDatabaseFile();

		$date = ($file) ? APIIP2Location::getDatabaseDate($file) : '';
		$size = ($file) ? APIIP2Location::getDatabaseSize($file) : 0;

		if ($lookupMode == 'BIN') {
			if (!$file) {
				$errors[] = 'There is no IP2Location database file found in ' . PIWIK_DOCUMENT_ROOT . \DIRECTORY_SEPARATOR . 'misc.';
			}

			if ($date && strtotime($date) < strtotime('-2 months')) {
				$errors[] = 'Your IP2Location BIN file version is outdated. Please visit http://www.ip2location.com to download the latest BIN file."';
			}
		}

		$view = new View('@IP2Location/config');
		$view->language = LanguagesManager::getLanguageCodeForCurrentUser();

		$this->setBasicVariablesView($view);
		$view->defaultReportSiteName = Site::getNameFor($siteId);
		$view->assign('idSite', $siteId);
		$view->assign('saved', $saved);
		$view->assign('errors', $errors);

		$view->assign('lookupMode', $lookupMode);
		$view->assign('apiKey', $apiKey);

		$view->assign('database', $file);
		$view->assign('date', $date);
		$view->assign('size', $size);
		$view->assign('credit', number_format(APIIP2Location::getWebServiceCredit(), 0, '', ','));

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
		try {
			Piwik::checkUserHasSuperUserAccess();
			$siteID = Common::getRequestVar('siteID', 0);
			if ($siteID == 0) {
				$siteID = Common::getRequestVar('idSite');
			}

			$errors = [];

			$lookupMode = trim(Common::getRequestVar('lookupMode', ''));
			$apiKey = trim(Common::getRequestVar('apiKey', ''));

			/*if ($lookupMode == 'BIN') {
				$file = APIIP2Location::getDatabaseFile();

				if (!$file) {
					$errors[] = Piwik::translate('IP2Location_NoIP2LocationDatabaseFile');
				}
			}*/

			if ($lookupMode == 'WS') {
				if (!$apiKey) {
					$errors[] = Piwik::translate('IP2Location_PleaseEnterAValidAPIKey');
				} elseif (!APIIP2Location::getWebServiceCredit($apiKey)) {
					$errors[] = Piwik::translate('IP2Location_PleaseEnterAValidAPIKey');
				}
			}

			if (empty($errors)) {
				APIIP2Location::setLookupMode($lookupMode);
				APIIP2Location::setAPIKey($apiKey);
			}

			$this->config($siteID, $errors);
		} catch (Exception $e) {
			echo $e;
		}
	}
}
