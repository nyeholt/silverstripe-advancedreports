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
    public static $db = array(
		'Title' => 'Varchar(128)',
		'Description' => 'Text',
	);

	public static $has_one = array(
		'Report' => 'FrontendReport',			// never set for the 'template' report for a page, but used to
												// list all the generated reports. 
		'HTMLFile' => 'File',
		'CSVFile' => 'File',
		'PDFFile' => 'File',
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
	 */
	public function createReport($format='html') {
		$template = get_class($this) . '_' . $format;

		$content = "Formatter for $format not found!";

		$formatter = ucfirst($format).'ReportFormatter';
		if (class_exists($formatter)) {
			$formatter = new $formatter($this);
			$content = $formatter->format();
		}

		$output = $this->customise(array('ReportContent' => $content))->renderWith($template);

		if (in_array($format, array('html', 'csv'))) {
			return $output;
		}
	}
}

