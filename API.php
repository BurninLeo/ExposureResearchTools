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
    	$clipExtension = ((string)\Piwik\Common::getRequestVar('clip', 'no', 'string') === 'yes');
    	$skipNoID = ((string)\Piwik\Common::getRequestVar('skipid', 'no', 'string') === 'yes');
    	$addAggregate = ((string)\Piwik\Common::getRequestVar('aggregate', 'no', 'string') === 'yes');

		// https://developer.piwik.org/guides/security-in-piwik
		// https://developer.piwik.org/guides/persistence-and-the-mysql-backend

		$sql =
			'SELECT v.idvisit, l.server_time, l.time_spent_ref_action, a.name
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
    	$visits = array();
		do {
			$row = $res->fetch();
			
			// Stop processing for empty results
			if ($row === false) {
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
			
			// Register new visit
			if ($row['idvisit'] !== $cvVisitID) {
				$nAction = 1;
				$cvVisitID = $row['idvisit'];
				$cvCaseStart = $row['server_time'];
				$cvTimeSum = 0;
				$cvCaseID = null;
				$cvCaseActions = array();
    			$cvCaseTimes = array();
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
			$cvTimeSum+= $aTime;
		} while ($row !== false);

		if ($nActionMax > $aLimit) {
			$nActionMax = $aLimit;
		}
		
		// Collect all possible activities
		if ($addAggregate) {
			$hash = array();
			foreach ($visits as $visit) {
				foreach ($visit['actions'] as $name) {
					$hash[$name] = true;
				}
			}
			$allActivities = array_keys($hash);
			sort($allActivities);
			
			// Create table for variable names
			$aggVarNames = array();
			foreach ($allActivities as $url) {
				$aggVarNames[$url] = 'AT_'.preg_replace('/[^a-z0-9]+/i', '_', $url);
			}
		}

		// Sort and package into table
		$table = new DataTable();
		foreach ($visits as $visit) {
			$basic = array(
				'id' => $visit['id'],
				'CASE' => $visit['case'],
				'T0' =>  $visit['start']
			);
				
			// Rearrange data, if ncessary
			$actions = array();
			$timesT = array();
			$timesAT = array();
			$pages = array();
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
				foreach ($allActivities as $url) {
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
        
        return $table;
    }
}
