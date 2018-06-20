<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExposureResearchTools\Reports;

use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Actions\Columns\ExitPageUrl;
use Piwik\View;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetExposureResearchTools extends Base
{
    protected function init()
    {
        parent::init();
        
        // Piwik 2.x
        $this->category = 'Research Tools';
        $this->name          = 'Export Visits'; // Piwik::translate('ExposureResearchToolsName');
        
        // Piwik 3.x
        $this->categoryId = 'Research Tools';
        $this->subcategoryId = 'Export Visits';
        
        $this->dimension     = new ExitPageUrl();
        $this->documentation = Piwik::translate('ExposureResearchToolsDocumentation');

        // This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
        $this->order = 999;

        // By default standard metrics are defined but you can customize them by defining an array of metric names
        // $this->metrics       = array('nb_visits', 'nb_hits');

        // Uncomment the next line if your report does not contain any processed metrics, otherwise default
        // processed metrics will be assigned
        // $this->processedMetrics = array();

        // Uncomment the next line if your report defines goal metrics
        // $this->hasGoalMetrics = true;

        // Uncomment the next line if your report should be able to load subtables. You can define any action here
        // $this->actionToLoadSubTables = $this->action;

        // Uncomment the next line if your report always returns a constant count of rows, for instance always
        // 24 rows for 1-24hours
        // $this->constantRowsCount = true;

        // If a menu title is specified, the report will be displayed in the menu
        $this->menuTitle = $this->name;

        // If a widget title is specified, the report will be displayed in the list of widgets and the report can be
        // exported as a widget
        // $this->widgetTitle  = '';
    }

    /**
     * Here you can configure how your report should be displayed. For instance whether your report supports a search
     * etc. You can also change the default request config. For instance change how many rows are displayed by default.
     *
     * @param ViewDataTable $view
     */
    public function configureView(ViewDataTable $view)
    {
        if (!empty($this->dimension)) {
            $view->config->addTranslations(array('label' => $this->dimension->getName()));
        }

        // $view->config->show_search = false;
        // $view->requestConfig->filter_sort_column = 'nb_visits';
        // $view->requestConfig->filter_limit = 10';

        $view->config->columns_to_display = array_merge(array('label'), $this->metrics);
    }

    /**
     * Here you can define related reports that will be shown below the reports. Just return an array of related
     * report instances if there are any.
     *
     * @return \Piwik\Plugin\Report[]
     */
    public function getRelatedReports()
    {
        return array(); // eg return array(new XyzReport());
    }
    
    /**
     * A report is usually completely automatically rendered for you but you can render the report completely
     * customized if you wish. Just overwrite the method and make sure to return a string containing the content of the
     * report. Don't forget to create the defined twig template within the templates folder of your plugin in order to
     * make it work. Usually you should NOT have to overwrite this render method.
     *
     * @return string
     */
    public function render()
    {
    	/*
        $view = new View('@ExposureResearchTools/GetExposureResearchTools');
        $view->myData = array();
        return $view->render();
        */
        
    	$aLimit = (int)\Piwik\Common::getRequestVar('vlimit', 100, 'int');
    	if ($aLimit === 0) {
    		$aLimit = 100;
    	}
    	$getVar = (string)\Piwik\Common::getRequestVar('casevar', 'num', 'string');
        
        /*
        if ($send === 'yes') {
        	$api = \Piwik\Plugins\ExposureResearchTools\API::getInstance();
	        $data = $api->getExposureResearchTools(
	        	\Piwik\Common::getRequestVar('idSite'),
			    \Piwik\Common::getRequestVar('date'),
			    \Piwik\Common::getRequestVar('period'),
			    \Piwik\Common::getRequestVar('segment')
	        );
	        
	        var_dump($data);
	        
	        exit;
        } else {
        */
        
        	// Copy all parameters
        	/*
        	$params = '';
        	foreach ($_REQUEST as $key => $value) {
        		if (in_array($key, array('vlimit', 'casevar', 'format'))) {
        			continue;
        		}
        		$monkey = "\r\n".'<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'" />';
        	}
        	*/
        	
	        return
				'<form action="index.php" method="GET">
				<div>
					<input type="hidden" name="module" value="API" />
					<input type="hidden" name="method" value="ExposureResearchTools.getData" />
					<input type="hidden" name="idSite" value="'.\Piwik\Common::getRequestVar('idSite').'" />
					<input type="hidden" name="date" value="'.\Piwik\Common::getRequestVar('date').'" />
					<input type="hidden" name="period" value="'.\Piwik\Common::getRequestVar('period').'" />
					<!-- <input type="hidden" name="segment" value="'.\Piwik\Common::getRequestVar('segment', '').'" /> -->
					<input type="hidden" name="token_auth" value="'.\Piwik\Piwik::getCurrentUserTokenAuth().'" />
					<input type="hidden" name="format" value="csv" />
					<input type="hidden" name="filter_limit" value="-1" />
				</div>
				<table cellspacing="0" cellpadding="0">
				<colgroup>
					<col width="320">
					</col>
				</colgroup>
				<tr>
					<td style="padding-right: 1em">Limit activities per visit:</td>
					<td><input type="text" name="vlimit" value="'.$aLimit.'" style="width: 64px" /></td>
				</tr><tr>
					<td style="padding-right: 1em">Read subject ID from GET variable:</td>
					<td><input type="text" name="casevar" value="'.htmlspecialchars($getVar).'" style="width: 180px" /></td>
				</tr><tr>
					<td style="padding-right: 1em">Data structure:</td>
					<td>
						<select name="structure" size="1" style="max-width: 100%">
							<option value="case">Per participant (many variables, few rows)</option>
							<option value="page">Per page view (few variables, may rows)</option>
						</select>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="aggregate" name="aggregate" value="yes" checked="checked" />
						<label for="aggregate">
							Include aggregate reading times per page (sum per page)<br>
							<span style="font-size: 85%">(applicable in per-participant structure, only)</span>
						</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="var64" name="var64" value="yes" checked="checked" />
						<label for="var64">SPSS-compatible variable names</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="server" name="server" value="yes" />
						<label for="server">Retain domain name in URLs</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="noclip" name="noclip" value="yes" />
						<label for="noclip">Retain extension and query string in URLs (anything after the file name)</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="disindex" name="disindex" value="yes" />
						<label for="disindex">Distinguish index.html, index.htm, index.php, and homepage (/)</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<input type="checkbox" id="skipid" name="skipid" value="yes" />
						<label for="skipid">Skip visits without subject ID (only applicable if GET variable for subject ID is set)</label>
					</td>
				</tr><tr>
					<td colspan="2" style="padding-top: 0.5em; padding-bottom: 0.5em">
						<strong>Note:</strong> All available data will be exported, regardless of the period defined above.
					</td>
				</tr>
				</table>
							
				<div style="margin-top: 1em">
					<button type="submit">Download CSV</button>
				</div>
				</form>

				<h2>File Structure</h2>
				<p>Depending on the file structure selected above, the variables in the result will be:</p>
				<ul style="list-style: circle; padding-left: 24px; margin-bottom: 30px">
					<li>Case identification (both structures)
						<ul style="list-style: circle; padding-left: 24px">
							<li><strong style="width: 60px; display: inline-block">id</strong> Matomo\'s ID for the visit</li>
							<li><strong style="width: 60px; display: inline-block">CASE</strong> Case ID retrieved from the URL (see setting GET variable)</li>
						</ul>
					</li>
					<li>Structured &quot;per participant&quot;
						<ul style="list-style: circle; padding-left: 24px">
							<li><strong style="width: 60px; display: inline-block">T0</strong> Date and time when the first page was retrieved</li>
							<li><span style="width: 60px; display: inline-block"><strong>A1</strong>&ndash;<strong>A<i>n</i></strong></span> Activities performed (pages viewed) during the visit: ID of the first viewed page stored in A1, etc.</li>
							<li><span style="width: 60px; display: inline-block"><strong>T1</strong>&ndash;<strong>T<i>n</i></strong></span> Times spent per activity (time between retrieving one page and the following one): T1 is the time for A1 in seconds, etc.</li>
							<li><span style="width: 60px; display: inline-block"><strong>AT</strong></span> Times spent during the visit overall.</li>
							<li><span style="width: 60px; display: inline-block"><strong>AT_<i>xyz</i></strong></span> Aggregate time spent per activity <i>xyz</i> (e.g., page <i>xyz</i>), in seconds. This block of variables will only be available, if &quot;Include aggregate reading times&quot; has been checked above.</li>
						</ul>
					</li>
					<li>Structured &quot;per action&quot;
						<ul style="list-style: circle; padding-left: 24px">
							<li><span style="width: 60px; display: inline-block"><strong>pos</strong></span> Order of actions (page views) during the visit</li>
							<li><span style="width: 60px; display: inline-block"><strong>aID</strong></span> Unique ID for the action (page)</li>
							<li><span style="width: 60px; display: inline-block"><strong>url</strong></span> Description of the action (page URL, usually shortend)</li>
							<li><span style="width: 60px; display: inline-block"><strong>time</strong></span> Time spent on the action (page)</li>
							<li><span style="width: 60px; display: inline-block"><strong>ontime</strong></span> Beginning of action [sec], relative to the visit\'s first page retrieval</li>
							<li><span style="width: 60px; display: inline-block"><strong>astime</strong></span> Absolute timestamp of the action (page view)</li>
						</ul>
					</li>
				</ul>
				<p><strong>Note:</strong> Matomo will not record the time spent on the most recent page (activity). This affects <strong>T<i>n</i></strong>, <strong>AT</strong>, <strong>AT_<i>xyz</i></strong>, and <strong>time</strong>.</p>
				<p><strong>Note:</strong> If Excel won\'t open the file correctly (all data in one cell), download the CSV file to disk,
					then start Excel and open via menu &rarr; file &rarr; open. OpenOffice Calc will cause less trouble.</p>
							
				<h2>Additional Information</h2>
				<p>If you\'re interested in doing selective exposure research with this tool, the following may be helpful:</p>
				<ul style="list-style: circle; padding-left: 24px; margin-bottom: 30px">
					<li>
						<p>We published a paper about this tool and how to collect SE data with Matomo:</p>
						<p>Leiner, D. J., Scherr, S., & Bartsch, A. (2016). Using Open-Source Tools to Measure Online Selective Exposure in Naturalistic Settings. Communication Methods and Measures, 10(4), 199–216. doi:<a href="http://doi.org/10.1080/19312458.2016.1224825">10.1080/19312458.2016.1224825</a></p>
					</li>
					<li>
						<p>There are additional resources on the <a href="https://github.com/BurninLeo/ExposureResearchTools" target="_blank">Plugin Website</a> (on GitHub).</p>
						<p>Specifically, there\'s a template for SoSci Survey to embed a stimulus presentation (via pop-up) between a pre and post questionnaire.
							The template takes care of transmitting the respondent ID, so that it appears in the SE data,
							and to store the times when the pop-up was opened and closed.</p>
					</li>
				</ul>';
    }

    /**
     * By default your report is available to all users having at least view access. If you do not want this, you can
     * limit the audience by overwriting this method.
     *
     * @return bool
    public function isEnabled()
    {
        return Piwik::hasUserSuperUserAccess()
    }
     */
}