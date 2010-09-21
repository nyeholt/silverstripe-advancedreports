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

	/**
	 * Get the list of available report types registered in the system
	 *
	 * @return array
	 */
	public function getReportTypes() {
		return $this->reportTypes;
	}

	
	/**
	 * Create a report for the given frontend report, with the given format
	 *
	 * If a filename is passed in, it will save the report in that file, if not it will
	 * return the raw data of the report (text in the case of HTML or CSV reports).
	 *
	 * Supported formats are html, csv and pdf
	 *
	 * @param FrontendReport $report
	 */
	public function createReport(FrontendReport $report, $format = 'html', $filename = null) {
		$template = get_class($report) . '_' . $format;

		$output = $report->renderWith($template);

		if (in_array($format, array('html', 'csv'))) {
			return $output;
		}

	}
}