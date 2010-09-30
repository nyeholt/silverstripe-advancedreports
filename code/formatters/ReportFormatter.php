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
	const DEFAULT_TABLE_NAME = '__report_formatter__default__';

	const ADD_IN_ROWS_TOTAL = '__AddInRows_Total';

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

		$vals = $this->report->AddInRows;
		// if there's something in "AddInRows", we need a "Total" column
		if ($vals && count($vals->getValues())) {
			$this->headers[self::ADD_IN_ROWS_TOTAL] = _t('ReportFormatter.HEADER_TOTAL', 'Total');
		}
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

		foreach ($this->data as $tableName => $data) {
			$thisReport = array();

			$thisReport['Header'] = $this->settings['ShowHeader'] ? $this->createHeader($tableName) : '';
			$thisReport['Body'] = $this->createBody($tableName, $data);

			$report[$tableName] = $thisReport;
		}
		
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

		$tableName = self::DEFAULT_TABLE_NAME;

		$paginateBy = $this->report->PaginateBy;

		$addCols = $this->report->AddInRows && count($this->report->AddInRows->getValues()) ? $this->report->AddInRows->getValues() : null;

		foreach ($dataObjects as $item) {

			// lets check to see whether this item has the paginate variable, if so we want to be
			// adding this result to that table entry
			if ($paginateBy) {
				$pageVar = is_object($item) ? $item->$paginateBy : $item[$paginateBy];
				if ($pageVar) {
					$tableName = $pageVar;
				} else {
					$tableName = sprintf(_t('ReportFormatter.NO_PAGINATE_VALUE', 'No %s'), $paginateBy);
				}
			}

			$row = array();

			$addToTable = isset($this->data[$tableName]) ? $this->data[$tableName] : array();

			$rowSum = 0;
			
			foreach ($this->headers as $field => $display) {
				// Account for our total summation of things. 
				if ($field == self::ADD_IN_ROWS_TOTAL) {
					if (is_object($item)) {
						$item->$field = $rowSum;
					} else {
						$item[$field] = $rowSum;
					}
				}

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

				if ($addCols && in_array($field, $addCols)) {
					$rowSum += $value;
				}
			}

			$addToTable[] = $row;
			$this->data[$tableName] = $addToTable;
			$i++;
		}

		// now that the tables have been created, need to do column summation on each. We could do this during
		// the above looping, but because we don't FORCE the items to be properly sorted first, we can't guarantee
		// that the columns that need summation are in the right places
		$addCols = $this->report->AddCols ? $this->report->AddCols->getValues() : array();
		if (count($addCols)) {
			// if we had a row total, we'll add it in by default; it only makes sense!
			if (isset($this->headers[self::ADD_IN_ROWS_TOTAL])) {
				$addCols[] = self::ADD_IN_ROWS_TOTAL;
			}

			
			foreach ($this->data as $tableName => $data) {
				$sums = array();

				$titleColumn = null;
				foreach ($data as $row) {
					$prevField = null;
					foreach ($row as $field => $value) {
						if (in_array($field, $addCols)) {
							// if we haven't already figured it out, we now know that we want the field BEFORE
							// this as the column we put the Total text into
							if (!$titleColumn) {
								$titleColumn = $prevField;
							}
							$cur = isset($sums[$field]) ? $sums[$field] : 0;
							$sums[$field] = $cur + $value;
						} else {
							$sums[$field] = '';
						}
						$prevField = $field;
					}
				}

				// figure out the name of the field we want to stick the Total text

				$sums[$titleColumn] = _t('ReportFormatter.ROW_TOTAL', 'Total');
				$data[] = $sums;
				$this->data[$tableName] = $data;
			}
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
	abstract protected function createHeader($tableName);

	/**
	 * Create a body for the report
	 */
	abstract protected function createBody($tableName, $tableData);

	/**
	 * Format the header and body into a complete report output.
	 *
	 * @param array $reportPieces
	 *				The pieces of the report in an array indexed with 'Header' and 'Body'
	 */
	abstract protected function formatReport($reportPieces);
}