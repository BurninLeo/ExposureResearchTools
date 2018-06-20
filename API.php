<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\ExposureResearchTools;

use Piwik\DataTable;
use Piwik\DataTable\Row;

/**
 * API for plugin ExposureResearchTools
 *
 * @method static \Piwik\Plugins\ExposureResearchTools\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
	
	
	/**
	 * From: https://en.wikibooks.org/wiki/Algorithm_Implementation/Strings/Longest_common_substring#PHP
	 * @param string $string_1
	 * @param string $string_2
	 * @return string
	 */
	private static function get_longest_common_subsequence($string_1, $string_2)
	{
		$string_1_length = strlen($string_1);
		$string_2_length = strlen($string_2);
		$return          = '';
	
		if ($string_1_length === 0 || $string_2_length === 0)
		{
			// No similarities
			return $return;
		}
	
		$longest_common_subsequence = array();
	
		// Initialize the CSL array to assume there are no similarities
		$longest_common_subsequence = array_fill(0, $string_1_length, array_fill(0, $string_2_length, 0));
	
		$largest_size = 0;
	
		for ($i = 0; $i < $string_1_length; $i++)
		{
			for ($j = 0; $j < $string_2_length; $j++)
			{
				// Check every combination of characters
				if ($string_1[$i] === $string_2[$j])
				{
					// These are the same in both strings
					if ($i === 0 || $j === 0)
					{
						// It's the first character, so it's clearly only 1 character long
						$longest_common_subsequence[$i][$j] = 1;
					}
					else
					{
						// It's one character longer than the string from the previous character
						$longest_common_subsequence[$i][$j] = $longest_common_subsequence[$i - 1][$j - 1] + 1;
					}
	
					if ($longest_common_subsequence[$i][$j] > $largest_size)
					{
						// Remember this as the largest
						$largest_size = $longest_common_subsequence[$i][$j];
						// Wipe any previous results
						$return       = '';
						// And then fall through to remember this new value
					}
	
					if ($longest_common_subsequence[$i][$j] === $largest_size)
					{
						// Remember the largest string(s)
						$return = substr($string_1, $i - $largest_size + 1, $largest_size);
					}
				}
				// Else, $CSL should be set to 0, which it was already initialized to
			}
		}
	
		// Return the list of matches
		return $return;
	}

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getData($idSite, $period, $date, $segment=false)
    {
		// Not: \Piwik is \core
		// TODO: Zugriffssteuerung !!!
    	\Piwik\Piwik::checkUserHasViewAccess($idSite);
    	
    	// Read additional variables
    	$aLimit = (int)\Piwik\Common::getRequestVar('vlimit', 100, 'int');
    	if ($aLimit === 0) {
    		$aLimit = 100;
    	}
    	$getVar = trim((string)\Piwik\Common::getRequestVar('casevar', '', 'string'));
    	$clipServer = ((string)\Piwik\Common::getRequestVar('server', 'no', 'string') !== 'yes');
    	$clipExtension = ((string)\Piwik\Common::getRequestVar('noclip', 'no', 'string') !== 'yes');
    	$recodeIndex = ((string)\Piwik\Common::getRequestVar('disindex', 'no', 'string') !== 'yes');
    	$skipNoID = ((string)\Piwik\Common::getRequestVar('skipid', 'no', 'string') === 'yes');
    	$addAggregate = ((string)\Piwik\Common::getRequestVar('aggregate', 'no', 'string') === 'yes');
    	$structure = (string)\Piwik\Common::getRequestVar('structure', 'case', 'string');  // [case|page]
    	$var64 = ((string)\Piwik\Common::getRequestVar('var64', 'no', 'string') === 'yes');

		// https://developer.piwik.org/guides/security-in-piwik
		// https://developer.piwik.org/guides/persistence-and-the-mysql-backend

		$sql =
			'SELECT v.idvisit, l.server_time, l.time_spent_ref_action, a.name, a.idaction
			FROM '.\Piwik\Common::prefixTable('log_visit').' v
			LEFT JOIN '.\Piwik\Common::prefixTable('log_link_visit_action').' l ON (v.idvisit = l.idvisit)
			LEFT JOIN '.\Piwik\Common::prefixTable('log_action').' a ON (a.idaction = l.idaction_url)
			WHERE (v.idsite = ?) AND (a.type = 1)
			ORDER BY l.idvisit, l.server_time';
		
		$res = \Piwik\Db::query($sql, array($idSite));
		
		// error_log(var_export($idSite, true));
		
		if (empty($getVar)) {
			$getVarLen = 0;
			$skipNoID = false;
		} else {
			$getVarLen = strlen($getVar) + 1;
		}
		
		$nAction = 0;
		$nActionMax = 0; // Number of actions per visit (max.)
    	$cvVisitID = null;
    	$cvCaseID = null;
    	$cvCaseStart = null;
    	$cvTimeSum = 0;
    	$cvCaseActions = array();
    	$cvCaseTimes = array();
    	$cvCaseStamps = array();
    	$visits = array();
    	$actionIDs = array();  // Collect IDs per action
		do {
			$row = $res->fetch();
			
			// Stop processing for empty results
			if ($row === false) {
				// ... after the previous visit was registered
				if ($cvVisitID === null) {
					// No data at all
					$table->addRowFromSimpleArray(array('error' => 'no data available', 'site' => $idSite));
					return $table;
				}
			}
			$cvFirst = false;
			
			// Store new visit
			if (($row === false) or (($row['idvisit'] !== $cvVisitID) and ($cvVisitID !== null))) {
				if ($skipNoID and ($cvCaseID === null)) {
					// Skip this case
				} else {
					$visits[] = array(
						'id' => $cvVisitID,
						'case' => $cvCaseID,
						'start' => $cvCaseStart,
						'actions' => $cvCaseActions,
						'times' => $cvCaseTimes,
						'stamps' => $cvCaseStamps,
						'time sum' => $cvTimeSum
					);
					if (count($cvCaseActions) > $nAction) {
						$nAction = count($cvCaseActions);
					}
					if ($nAction > $nActionMax) {
						$nActionMax = $nAction;
					}
				}
			}
			// Stop, if there is no more data
			if ($row === false) {
				break;
			}
			
			// Register new visit (group of actions/page views)
			if ($row['idvisit'] !== $cvVisitID) {
				$nAction = 1;
				$cvVisitID = $row['idvisit'];
				$cvCaseStart = $row['server_time'];
				$cvTimeSum = 0;
				$cvCaseID = null;
				$cvCaseActions = array();
    			$cvCaseTimes = array();
    			$cvCaseStamps = array();
			} else {
				$nAction++;
			}
			
			// Decode the URL
			$url = $row['name'];
			if (($cvCaseID === null) and ($getVarLen !== 0)) {
				if (($p = strpos($url, '?')) !== false) {
					$qs = substr($url, $p + 1);
					$items = explode('&', $qs);
					foreach ($items as $item) {
						if (substr($item, 0, $getVarLen) === $getVar.'=') {
							$s = trim(substr($item, $getVarLen));
							if ($s !== '') {
								$cvCaseID = $s;
							}
						}
					}
				}
			}
			if ($recodeIndex) {
				$t10 = substr($url, -10);
				$t11 = substr($url, -11);
				if (($t10 === '/index.php') or ($t10 === '/index.htm') or ($t11 === '/index.html')) {
					// No index in subdirectories
					if (substr_count($url, '/') === 1) {
						$p = strpos($url, '/');
						$url = substr($url, 0, $p+1);
					}
				}
			}
			if ($clipServer) {
				$p = strpos($url, '/');
				if ($p !== false) {
					$url = substr($url, $p+1);
					if ($url === false) {
						$url = '';
					}
				}
			}
			if ($clipExtension) {
				$p1 = strrpos($url, '.');
				$p2 = strrpos($url, '?');
				if (($p1 === false) and ($p2 === false)) {
					// No extension
					$p = false;
				} elseif ($p1 === false) {
					$p = $p2;
				} elseif ($p2 === false) {
					$p = $p1;
				} else {
					$p = min($p1, $p2);
				}
				if ($p !== false) {
					$url = substr($url, 0, $p);
				}
			}
			if ($url === '') {
				$url = '/';
			}
			
			$cvCaseActions[] = $url;
			$aTime = (int)$row['time_spent_ref_action'];
			$cvCaseTimes[] = $aTime;
			$cvCaseStamps[] = $row['server_time'];
			$cvTimeSum+= $aTime;
			
			// Already know this action?
			if (!isset($actionIDs[$url])) {
				$actionIDs[$url] = (int)$row['idaction'];
			}
		} while ($row !== false);
		
		// Variable names for actions
		$actionNames = array();
		foreach ($visits as $visit) {
			foreach ($visit['actions'] as $i => $action) {
				if (!isset($actionNames[$action])) {
					if ($action === '/') {
						$actionNames[$action] = 'index';
					} else {
						// Some basic cleaning
						$actionNames[$action] = preg_replace(array(
								'/[^a-z0-9]+/i',
								'/^_+/',
								'/_+$/',
								'/__+/'
							), array(
								'_',
								'',
								'',
								'_'
							),
							$action
						);
					}
				}
			}
		}
		// Sort by original URL
		ksort($actionNames);
		// Rename, if applicable
		if ($var64) {
			// Find common parts in all actions (except index) and remove
			$first = array();
			$common = '';
			foreach ($actionNames as $id => $name) {
				if ($id !== 'index') {
					$first[] = $name;
				}
				if (count($first) === 2) {
					$common = self::get_longest_common_subsequence($first[0], $first[1]);
					break;
				}
			}
			if (strlen($common) >= 3) {
				foreach ($actionNames as $id => $name) {
					if ($id === 'index') {
						continue;
					}
					$common = self::get_longest_common_subsequence($common, $name);
					if (strlen($common) < 3) {
						break;
					}
				}
			}
			// Remove common part
			if (strlen($common) >= 3) {
				foreach ($actionNames as $id => $name) {
					$actionNames[$id] = str_replace($common, '', $name);
				}
			}
			// Then also clip to 60 characters
			// (makes 63 with prefix AT_
			foreach ($actionNames as $id => $name) {
				if (strlen($name) > 60) {
					// TODO: Some time find a better algorith, using word boundaries (_) and skipping word in the middle
					$actionNames[$id] = substr($name, -60);
				}
			}
		}

		// Result table
		$table = new DataTable();
			
		if ($structure === 'page') {
			foreach ($visits as $visit) {
				$basic = array(
					'id' => $visit['id'],
					'CASE' => $visit['case']
				);
				$t0 = strtotime($visit['start']);
				
				// One row per action
				$aData = array();
				foreach ($visit['actions'] as $i => $action) {
					$aData['pos'] = ($i + 1);
					$aData['aID'] = (isset($actionIDs[$action]) ? $actionIDs[$action] : -1);
					$aData['url'] = $action;
					if (isset($visit['times'][$i+1])) {
						$aData['time'] = $visit['times'][$i+1];
					} else {
						$aData['time'] = null;
					}
					$aData['ontime'] = strtotime($visit['stamps'][$i]) - $t0;
					$aData['astime'] = $visit['stamps'][$i];
						
					$table->addRowFromSimpleArray(array_merge(
						$basic,
						$aData
					));
				}
			}
			
		} else {
			// Number of actions
			if ($nActionMax > $aLimit) {
				$nActionMax = $aLimit;
			}
			
			// Variable names for the page-time-variables
			if ($addAggregate) {
				$aggVarNames = [];
				foreach ($actionNames as $url => $actionID) {
					$aggVarNames[$url] = 'AT_'.$actionID;
				}
			}

			// Sort and package into table
			foreach ($visits as $visit) {
				$basic = array(
					'id' => $visit['id'],
					'CASE' => $visit['case'],
					'T0' =>  $visit['start']
				);
					
				// Rearrange data, if necessary
				$actions = [];
				$timesT = [];
				$timesAT = [];
				$pages = [];
				for ($i=0; $i<$nActionMax; $i++) {
					if (isset($visit['actions'][$i])) {
						$actions['A'.($i+1)] = $visit['actions'][$i];
					} else {
						$actions['A'.($i+1)] = null;
					}
					if (isset($visit['times'][$i+1])) {
						$timesT['T'.($i+1)] = $visit['times'][$i+1];
					} else {
						$timesT['T'.($i+1)] = null;
					}
					
				}
				
				if ($addAggregate) {
					foreach (array_keys($aggVarNames) as $url) {
						$timesAT[$aggVarNames[$url]] = null;
					}
					foreach ($visit['actions'] as $i => $url) {
						if ($timesAT[$aggVarNames[$url]] === null) {
							$timesAT[$aggVarNames[$url]] = (isset($visit['times'][$i+1]) ? $visit['times'][$i+1] : 0);
						} elseif (isset($visit['times'][$i+1])) {
							$timesAT[$aggVarNames[$url]]+= $visit['times'][$i+1];
						}
					}
				}
				
				$table->addRowFromSimpleArray(array_merge(
					$basic,
					$actions,
					$timesT,
					array('AT' => $visit['time sum']),
					$timesAT,
					$pages
				));
			}
		}
	        
        return $table;
    }
}
