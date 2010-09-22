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
	 * @param boolean $store
	 *				Whether to store the created report. 
	 */
	public function createReport($format='html', $store = false) {
		Requirements::clear();
		
		$convertTo = null;
		$renderFormat = $format;
		if (isset(self::$conversion_formats[$format])) {
			$convertTo = 'pdf';
			$renderFormat = self::$conversion_formats[$format];
		}

		$template = get_class($this) . '_' . $renderFormat;
		$content = "Formatter for $format not found!";
		$formatter = ucfirst($renderFormat).'ReportFormatter';
		if (class_exists($formatter)) {
			$formatter = new $formatter($this);
			$content = $formatter->format();
		}

		$output = $this->customise(array('ReportContent' => $content, 'Format' => $format))->renderWith($template);

		if (!$convertTo) {
			if ($store) {
				// stick it in a temp file?
				$outputFile = tempnam(TEMP_FOLDER, $format);
				if (file_put_contents($outputFile, $output)) {
					return new FrontendReportOutput(null, $outputFile);
				} else {
					throw new Exception("Failed creating report"); 
				}

			} else {
				return new FrontendReportOutput($output);
			}
		}

		// hard coded for now, need proper content transformations....
		switch ($convertTo) {
			case 'pdf': {
				if ($store) {
					$filename = singleton('PdfRenditionService')->render($output);
					return new FrontendReportOutput(null, $filename);
				} else {
					singleton('PdfRenditionService')->render($output, 'browser');
					return new FrontendReportOutput();
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
		$field = strtoupper($format).'FileID';
		$storeIn = $this->getReportFolder();

		$name = $this->Title . '.' . $format;
		
		$childId = $storeIn->constructChild($name);
		$file = DataObject::get_by_id('File', $childId);

		// okay, now we should copy across... right?
		$file->setName($name);
		$file->write();

		// create the raw report file
		$output = $this->createReport($format, true);
		
		if (file_exists($output->filename)) {
			copy($output->filename, $file->getFullPath());
		}

		// make sure to set the appropriate ID
		$this->$field = $file->ID;
		$this->write();
	}

	/**
	 * Gets the report folder needed for storing the report files
	 *
	 * @param String $format
	 */
	protected function getReportFolder() {
		$id = $this->ReportID;
		if (!$id) {
			$id = 'preview';
		}
		$folderName = 'frontend-reports/'.$this->ReportID.'/'.$this->ID;
		return Folder::findOrMake($folderName);
	}
}

/**
 * Wrapper around a report output that might be raw content or a filename to the
 * report
 *
 */
class FrontendReportOutput {
	public $filename;
	public $content;

	public function __construct($content = null, $filename=null) {
		$this->filename = $filename;
		$this->content = $content;
	}
}
