<?php
/* 
 *  @license http://silverstripe.org/bsd-license/
 */

/**
 *
 * @author marcus@silverstripe.com.au
 */
class FrontendReportService {

	protected $reportTypes = array();

    public function __construct() {}

	/**
	 * Register a report type with this service
	 *
	 * @param String $class
	 */
	public function registerReportType($class) {
		if (ClassInfo::exists($class)) {
			$dummy = singleton($class);
			if ($dummy instanceof FrontendReport) {
				$this->reportTypes[$class] = $dummy->getReportName();
			}
		}
	}

	public function getReportTypes() {
		return $this->reportTypes;
	}

	
	/**
	 * Create a report for the given frontend report, with the given format
	 *
	 * If a filename is passed in, it will save the report in that file, if not it will
	 * return the raw data of the report (text in the case of HTML or CSV reports). 
	 *
	 * @param FrontendReport $report
	 */
	public function createReport(FrontendReport $report, $format = 'html', $filename = null) {

	}
	
}