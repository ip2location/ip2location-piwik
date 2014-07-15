<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\IP2Location;

use Piwik\Common;
use Piwik\Notification;
use Piwik\Notification\Manager as NotificationManager;
use Piwik\Piwik;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\View;
use Piwik\Plugins\IP2Location\API as IP2LocationAPI;
use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Menu\MenuMain;

/**
 *
 */
class Controller extends \Piwik\Plugin\Controller
{

    public function index()
    {
		Piwik::checkUserHasSuperUserAccess();

		$view = new View('@IP2Location/index');
		$view->language = LanguagesManager::getLanguageCodeForCurrentUser();

		$this->setBasicVariablesView($view);
		$view->currentAdminMenuName = MenuAdmin::getInstance()->getCurrentAdminMenuName();
		$view->adminMenu = MenuAdmin::getInstance()->getMenu();
		$view->topMenu = MenuTop::getInstance()->getMenu();
		$view->notifications = NotificationManager::getAllNotificationsToDisplay();
		$view->phpVersion = phpversion();
		$view->phpIsNewEnough = version_compare($view->phpVersion, '5.3.0', '>=');

		$view->assign('userMenu', 'IP2Location');
		$view->assign('dbNotFound', false);
		$view->assign('dbOutDated', false);
		$view->assign('showResults', false);

		$view->assign('fileName', '-');
		$view->assign('date', '-');

		$view->assign('country', '');
		$view->assign('regionName', '');
		$view->assign('cityName', '');
		$view->assign('position', '');

		$ipAddress = trim(Common::getRequestVar('ipAddress', $_SERVER['REMOTE_ADDR']));
		$view->assign('ipAddress', $ipAddress);

		$dbPath = PIWIK_INCLUDE_PATH . '/plugins/IP2Location/data/';
		$dbFile = '';

		if ($handle = opendir($dbPath)) {
			while (false !== ($file = readdir($handle))){
				if(strtoupper(substr($file, -4)) == '.BIN'){
					$dbFile = $dbPath . $file;
					break;
				}
			}
			closedir($handle);
		}

		if(!$dbFile) $view->assign('dbNotFound', true);

		if($dbFile){
			$view->assign('fileName', $file);

			if(filemtime($dbFile) < strtotime('-2 months')){
				$view->assign('dbOutDated', true);
			}
			else{
				$view->assign('date', date('d M, Y', filemtime($dbFile)));
			}

			if(!empty($_POST)){
				$view->assign('showResults', true);

				$result = IP2LocationAPI::lookup($ipAddress, $dbFile);

				$view->assign('country', ($result['countryCode'] != '-') ? ($result['countryName'] . ' (' . $result['countryCode'] . ')') : '-');
				$view->assign('regionName', (!preg_match('/not supported/', $result['regionName'])) ? $result['regionName'] : '-');
				$view->assign('cityName', (!preg_match('/not supported/', $result['cityName'])) ? $result['cityName'] : '-');
				$view->assign('position', (!preg_match('/not supported/', $result['latitude']) && $result['latitude'] != '-') ? ($result['latitude'] . ', ' . $result['longitude']) : '-');
			}

		}

		echo $view->render();
    }
}
