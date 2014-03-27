<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\IP2Location;

use Piwik\Plugins\IP2Location\IP2Location as IP2LocationPHP;

/**
 * API for plugin IP2Location
 *
 * @method static \Piwik\Plugins\IP2Location\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Example method. Please remove if you do not need this API method.
     * You can call this API method like this:
     * /index.php?module=API&method=IP2Location.getAnswerToLife
     * /index.php?module=API&method=IP2Location.getAnswerToLife?truth=0
     *
     * @param  bool $truth
     *
     * @return bool
     */
    static function lookup($ipAddress, $dbFile)
	{
		require_once(PIWIK_INCLUDE_PATH . '/plugins/IP2Location/Lookup.php');

		$db = new Lookup($dbFile);
		$records = $db->lookup($ipAddress, Lookup::ALL);

		return array(
			'countryCode'=>$records->countryCode,
			'countryName'=>$records->countryName,
			'regionName'=>$records->regionName,
			'cityName'=>$records->cityName,
			'latitude'=>$records->latitude,
			'longitude'=>$records->longitude,
		);
	}
}
