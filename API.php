<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\IP2Location;

use Piwik\DataTable;
use Piwik\DataTable\Row;

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

		$db = new Database($dbFile, Database::FILE_IO);
		$records = $db->lookup($ipAddress, Database::ALL);

		return array(
			'countryCode'=>$records['countryCode'],
			'countryName'=>$records['countryName'],
			'regionName'=>$records['regionName'],
			'cityName'=>$records['cityName'],
			'latitude'=>$records['latitude'],
			'longitude'=>$records['longitude'],
			'zipCode'=>$records['zipCode'],
			'timeZone'=>$records['timeZone'],
			'usageType'=>$records['usageType'],
		);
	}

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getIP2LocationReport($idSite, $period, $date, $segment = false)
    {
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

		if(!$dbFile) {
			return false;
		}

        $data = \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
			'idSite' => $idSite,
			'period' => $period,
			'date' => $date,
			'segment' => $segment,
			'numLastVisitorsToFetch' => 100,
			'minTimestamp' => false,
			'flat' => false,
			'doNotFetchActions' => true
		));
		$data->applyQueuedFilters();

		// Create a new database
		$result = $data->getEmptyClone($keepFilters = false);

		foreach ($data->getRows() as $visitRow) {
			// Get the visitor IP
			$visitor_ip = $visitRow->getColumn('visitIp');

			// Look up for geo location information using IP2Location plugin
			$geodata = $this->lookup($visitor_ip, $dbFile);

			if(strpos($geodata['usageType'], '/')){
				$parts = explode('/', $geodata['usageType']);

				foreach($parts as $part){
					$usageType .= $this->getUsageType($part) . ', ';
				}

				$usageType = rtrim($usageType, ' ,');
			}
			else{
				$usageType = $this->getUsageType($geodata['usageType']);
			}

			$result->addRowFromSimpleArray(array(
				'date'			=> $visitRow->getColumn('lastActionDateTime'),
				'ip_address'	=> $visitor_ip,
				'ip_country'	=> $geodata['countryName'],
				'ip_region'		=> $geodata['regionName'],
				'ip_city'		=> $geodata['cityName'],
				'ip_latitude'	=> (preg_match('/unavailable/', $geodata['latitude'])) ? '-' : ' ' . $geodata['latitude'] . ' ',
				'ip_longitude'	=> (preg_match('/unavailable/', $geodata['longitude'])) ? '-' : ' ' . $geodata['longitude'] . ' ',
				'ip_zip_code'	=> (preg_match('/unavailable/', $geodata['zipCode'])) ? '-' : ' ' . $geodata['zipCode'] . ' ',
				'ip_time_zone'	=> (preg_match('/unavailable/', $geodata['timeZone'])) ? '-' : $geodata['timeZone'],
				'ip_usage_type'	=> $usageType,
			));
		}

		return $result;
    }

	private function getUsageType($type){
		switch($type){
			case 'COM':
				return '(COM) Commercial';

			case 'ORG':
				return '(ORG) Organization';

			case 'GOV':
				return '(GOV) Government';

			case 'MIL':
				return '(MIL) Military';

			case 'EDU':
				return '(EDU) University/College/School';

			case 'LIB':
				return '(LIB) Library';

			case 'CDN':
				return '(CDN) Content Delivery Network';

			case 'ISP':
				return '(ISP) Fixed Line ISP';

			case 'MOB':
				return '(MOB) Mobile ISP';

			case 'DCH':
				return '(DCH) Data Center/Web Hosting/Transit';

			case 'SES':
				return '(SES) Search Engine Spider';

			case 'RSV':
				return '(RSV) Reserved';

			default:
				return '-';
		}
	}

}
