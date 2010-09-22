<?php

/**
 * An abstract representation of a report formatter.
 *
 * A report formatter takes in a given data object set and converts it into
 * an appropriate representation for output to the browser / file. 
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
abstract class ReportFormatter {
    protected $settings = array(
		'ShowDuplicateColValues'	=> false,	// rolls up columns that have duplicate values so that only the
												// first instance is displayed.
		'ShowHeader'				=> true
	);

	/**
	 * The report we're formatting
	 *
	 * @var FrontendReport
	 */
	protected $report;

	/**
	 * The headers. Should be in a format of
	 * array(
	 *		'Field' => 'Display'
	 * );
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * The raw dataobjects to display
	 *
	 * @var DataObjectSet
	 */
	protected $dataObjects;

	/**
	 * The formatted data
	 * 
	 * @var array
	 */
	protected $data;

	/**
	 * Create a new report formatter for the given report
	 *
	 * @param FrontendReport $report
	 */
	public function __construct(FrontendReport $report) {
		$this->report = $report;
		$this->headers = $report->getHeaders();
	}

	/**
	 * Set or retrieve a configuration variable
	 *
	 * @param String $setting
	 * @param mixed $val
	 */
	public function config($setting, $val=null) {
		if (!$val) {
			return isset($this->settings[$setting]) ? $this->settings[$setting] : null;
		}
		$this->settings[$setting] = $val;
	}

	/**
	 * Do whatever processing necessary to output the report.
	 */
	public function format() {
		$this->reformatDataObjects();

		$report = array();
		$report['Header'] = $this->settings['ShowHeader'] ? $this->createHeader() : '';
		$report['Body'] = $this->createBody();

		$output = $this->formatReport($report);

		return $output;
	}
	
	/**
	 * Restructures the data objects according to the settings of the report. 
	 */
	protected function reformatDataObjects() {
		$this->data = array();

		$dataObjects = $this->report->getDataObjects();
		$colsToBlank = $this->report->getDuplicatedBlankingFields();
		$mapping = $this->report->getFieldMapping();

		$i = 0;
		$previousVals = array();
		foreach ($dataObjects as $item) {
			$row = array();
			foreach ($this->headers as $field => $display) {
				$rawValue = is_object($item) ? $item->$field : $item[$field];

				$value = '';

				// based on the field name we've been given, lets
				// see if we can resolve it to a value on our data object
				if (isset($mapping[$field]) && $rawValue) {
					$format = $mapping[$field];
					eval('$value = ' . $format . ';');
				} else if ($rawValue) {
					$value = $rawValue;
				}

				if (in_array($field, $colsToBlank)) {
					if (!isset($previousVals[$field]) || $previousVals[$field] != $value) {
						$row[$field] = $value;

						// if this value that has changed is the 'first' value, then we need to reset all the other
						// 'previous' values from left to right from this position
						$previousVals = $this->resetPreviousVals($previousVals, $field);

						$previousVals[$field] = $value;
					} else {
						$row[$field] = '';
					}
				} else {
					$row[$field] = $value;
				}
			}

			$this->data[] = $row;
			$i++;
		}
	}

	/**
	 * Finds the 'row position' of a given field name in the current report structure
	 */
	protected function resetPreviousVals($vals, $fieldName) {
		$newVals = array();
		foreach ($vals as $field => $val) {
			if ($field == $fieldName) {
				break;
			}
			$newVals[$field] = $val;
		}
		return $newVals;
	}

	/**
	 * Create a header for the report
	 */
	abstract protected function createHeader();

	/**
	 * Create a body for the report
	 */
	abstract protected function createBody();

	/**
	 * Format the header and body into a complete report output.
	 *
	 * @param array $reportPieces
	 *				The pieces of the report in an array indexed with 'Header' and 'Body'
	 */
	abstract protected function formatReport($reportPieces);
}