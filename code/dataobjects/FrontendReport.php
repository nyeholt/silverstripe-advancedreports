<?php

/**
 * A representation of a report in the system
 *
 * Note that this class is abstract, but SilverStripe means it can't be declared
 * as such.
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class FrontendReport extends DataObject {

	/**
	 * What conversion needs to occur? 
	 * 
	 * @var array
	 */
	public static $conversion_formats = array('pdf' => 'html');

    public static $db = array(
		'Title' => 'Varchar(128)',
		'Description' => 'Text',
	);

	public static $has_one = array(
		'Report' => 'FrontendReport',			// never set for the 'template' report for a page, but used to
												// list all the generated reports. 
		'HTMLFiles' => 'Folder',
		'CSVFiles' => 'Folder',
		'PDFFiles' => 'Folder',
	);

	public function getReportName() {
		throw new Exception("Abstract method called; please implement getReportName()");
	}

	/**
	 * Set any custom fields for the report here. 
	 *
	 * @param FieldSet $fields
	 */
	public function updateReportFields($fields) {
	}

	/**
	 * Please override this in child classes!
	 *
	 * @return array
	 */
	public function getHeaders() {
		throw new Exception("Abstract method called; please implement getHeaders()");
	}

	/**
	 * Retrieve the raw data objects set for this report
	 */
	public function getDataObjects() {
		throw new Exception("Abstract method called; please implement getDataObjects()");
	}


	/**
	 * Get a list of columns that should have subsequent duplicated entries 'blanked' out
	 *
	 * This is used in cases where there is a table of data that might have 3 different values in
	 * the left column, and for each of those 3 values, many entries in the right column. What will happen
	 * (if the array here returns 'LeftColFieldName') is that any immediately following column that
	 * has the same value as current is blanked out. 
	 */
	public function getDuplicatedBlankingFields() {
		return array();
	}

	/**
	 * Creates a report in a specified format, returning a string which contains either
	 * the raw content of the report, or an object that encapsulates the report (eg a PDF). 
	 * 
	 * @param String $format
	 * @param boolean $store
	 *				Whether to store the created report. 
	 */
	public function createReport($format='html', $store = false) {
		
		$convertTo = null;
		if (isset(self::$conversion_formats[$format])) {
			$convertTo = 'pdf';
			$format = self::$conversion_formats[$format];
		}

		$template = get_class($this) . '_' . $format;
		$content = "Formatter for $format not found!";
		$formatter = ucfirst($format).'ReportFormatter';
		if (class_exists($formatter)) {
			$formatter = new $formatter($this);
			$content = $formatter->format();
		}

		$output = $this->customise(array('ReportContent' => $content))->renderWith($template);

		if (!$convertTo) {
			return $output;
		}


		// hard coded for now, need proper content transformations....
		switch ($convertTo) {
			case 'pdf': {
				if ($store) {
					return singleton('PdfRenditionService')->render($output);
				} else {
					singleton('PdfRenditionService')->render($output, 'browser');
				}
				break;
			}
			default: {
				break;
			}
		}
	}

	/**
	 * Generates an actual report file.
	 *
	 * @param string $format
	 */
	public function generateReport($format='html') {

	}

	/**
	 * Gets the report folder needed for storing the given format
	 * files
	 *
	 * @param String $format
	 */
	protected function getReportFolderFor($format) {
		
	}
}

