<?php

/**
 * A representation of a report in the system
 *
 * Provides several fields for specifying basic parameters of reports,
 * and functionality for (relatively) simply building an SQL query for
 * retrieving the report data.
 *
 * A ReportPage makes use of a reportformatter to actually generate the
 * report that gets displayed to the user; this report formatter uses
 * one of these AdvancedReport objects to actually get all the relevant
 * information to be displayed. 
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class AdvancedReport extends DataObject {

	/**
	 * What conversion needs to occur? 
	 * 
	 * @var array
	 */
	public static $conversion_formats = array('pdf' => 'html');

	public static $allowed_conditions = array('=' => '=', '<>' => '!=', '>=' => '>=', '>' => '>', '<' => '<', '<=' => '<=', 'IN' => 'In List');

    public static $db = array(
		'Title' => 'Varchar(128)',
		'Description' => 'Text',
		'ReportFields' => 'MultiValueField',
		'ReportHeaders' => 'MultiValueField',
		'ConditionFields' => 'MultiValueField',
		'ConditionOps' => 'MultiValueField',
		'ConditionValues' => 'MultiValueField',
		'PaginateBy' => 'Varchar(64)',						// a field used to separate tables (eg financial years)
		'PageHeader' => 'Varchar(64)',						// used as a keyworded string for pages
		'SortBy' => 'MultiValueField',
		'SortDir' => 'MultiValueField',
		'ClearColumns' => 'MultiValueField',
		'AddInRows' => 'MultiValueField',					// which fields in each row should be added?
		'AddCols' => 'MultiValueField'						// Which columns should be added ?
	);

	public static $has_one = array(
		'Report' => 'AdvancedReport',			// never set for the 'template' report for a page, but used to
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
		$reportFields = $this->getReportableFields();

		$fieldsGroup = new FieldGroup(_t('AdvancedReport.SELECTED_FIELDS', 'Fields'),
			new MultiValueDropdownField('ReportFields', _t('AdvancedReport.REPORT_FIELDS', 'Report Fields'), $reportFields),
			new MultiValueTextField('ReportHeaders', _t('AdvancedReport.REPORT_HEADERS', 'Headers'))
		);

		$fields->push($fieldsGroup);

		$conditions = new FieldGroup('Conditions', 
			new MultiValueDropdownField('ConditionFields', _t('AdvancedReport.CONDITION_FIELDS', 'Condition Fields'), $reportFields),
			new MultiValueDropdownField('ConditionOps', _t('AdvancedReport.CONDITION_OPERATIONS', 'Op'), self::$allowed_conditions),
			new MultiValueTextField('ConditionValues', _t('AdvancedReport.CONDITION_VALUES', 'Value'))
		);
		$fields->push($conditions);
		
		$combofield = new FieldGroup('Sorting',
			new MultiValueDropdownField('SortBy', _t('AdvancedReport.SORTED_BY', 'Sorted By'), $reportFields),
			new MultiValueDropdownField('SortDir', _t('AdvancedReport.SORT_DIRECTION', 'Sort Direction'), array('ASC' => _t('AdvancedReport.ASC', 'Ascending'), 'DESC' => _t('AdvancedReport.DESC', 'Descending')))
		);
		$fields->push($combofield);

		$paginateFields = $reportFields;
		array_unshift($paginateFields, '');
		$fields->push(new DropdownField('PaginateBy', _t('AdvancedReport.PAGINATE_BY', 'Paginate By'), $paginateFields));
		$fields->push(new TextField('PageHeader', _t('AdvancedReport.PAGED_HEADER', 'Header text (use $name for the page name)'), '$name'));

		$fields->push(new MultiValueDropdownField('AddInRows', _t('AdvancedReport.ADD_IN_ROWS', 'Add these columns for each row'), $reportFields));
		$fields->push(new MultiValueDropdownField('AddCols', _t('AdvancedReport.ADD_IN_ROWS', 'Provide totals for these columns'), $reportFields));
		$fields->push(new MultiValueDropdownField('ClearColumns', _t('AdvancedReport.CLEARED_COLS', '"Cleared" columns'), $reportFields));
	}

	/**
	 * Gets an array of field names that can be used in this report
	 *
	 * Override to specify your own values. 
	 */
	protected function getReportableFields() {
		return array('Title' => 'Title');
	}

	/**
	 * Converts a field in dotted notation (as used in some report selects) to a unique name
	 * that can be used for, eg "Table.Field AS Table_Field" so that we don't have problems with
	 * duplicity in queries, and mapping them back and forth
	 *
	 * We keep this as a method to ensure that we're explicity as to what/why we're doing
	 * this so that when someone comes along later, it's not toooo wtfy
	 *
	 * @param string $field
	 */
	protected function dottedFieldToUnique($field) {
		return str_replace('.', '_', $field);
	}

	/**
	 * Return the 'included fields' list. 
	 *
	 * @return
	 */
	public function getHeaders() {
		$headers = array();
		$reportFields = $this->getReportableFields();
		$sel = $this->ReportFields->getValues();
		foreach ($sel as $field) {
			$fieldName = $this->dottedFieldToUnique($field);
			$headers[$fieldName] = $reportFields[$field];
		}
		return $headers;
	}

	/**
	 * Get the selected report fields in a format suitable to be put in an
	 * SQL select (an array format)
	 *
	 * @return array
	 */
	protected function getReportFieldsForQuery() {

		$fields = $this->ReportFields->getValues();
		$reportFields = $this->getReportableFields();
		$toSelect = array();
		foreach ($fields as $field) {
			if (isset($reportFields[$field])) {
				if (strpos($field, '.')) {
					$field = $field . ' AS ' . $this->dottedFieldToUnique($field);
				}
				$toSelect[] = $field;
			}
		}
		return $toSelect;
	}


	/**
	 * Retrieve the raw data objects set for this report
	 *
	 * Note that the "DataObjects" don't necessarily need to implement DataObjectInterface;
	 * we can return whatever objects (or array maps) that we like.
	 * 
	 */
	public function getDataObjects() {
		throw new Exception("Abstract method called; please implement getDataObjects()");
	}


	/**
	 * Generate a WHERE clause based on the input the user provided.
	 *
	 * Assumes the user has provided some values for the $this->ConditionFields etc. Converts
	 * everything to an array that is run through the dbQuote() util method that handles all the
	 * escaping
	 */
	protected function getConditions() {
		$reportFields = $this->getReportableFields();
		$fields = $this->ConditionFields->getValues();
		if (!$fields || !count($fields)) {
			return '';
		}

		$ops = $this->ConditionOps->getValues();
		$vals = $this->ConditionValues->getValues();

		$filter = array();
		for ($i = 0, $c = count($fields); $i < $c; $i++) {
			$field = $fields[$i];
			if (!$ops[$i] || !$vals[$i]) {
				break;
			}

			$op = $ops[$i];
			if (!isset(self::$allowed_conditions[$op])) {
				break;
			}

			$val = $vals[$i];

			if ($op == 'IN') {
				$val = explode(',', $val);
			}

			$filter[$field . ' ' . $op] = $val;
		}

		return singleton('FRUtils')->dbQuote($filter);
	}


	/**
	 * Gets a string that represents the possible 'sort' options. 
	 *
	 * @return string 
	 */
	protected function getSort() {
		$sortBy = '';
		$sortVals = $this->SortBy->getValues();
		$dirs = $this->SortDir->getValues();

		$dir = 'ASC';

		$reportFields = $this->getReportableFields();
		$numericSort = $this->getNumericSortFields();

		if (count($sortVals)) {
			$sep = '';
			$index = 0;
			foreach ($sortVals as $sortOpt) {
				// check we're not injecting an invalid sort
				if (isset($reportFields[$sortOpt])) {
					// update the dir to match, if available, otherwise just use the last one
					if (isset($dirs[$index])) {
						if (in_array($dirs[$index], array('ASC', 'DESC'))) {
							$dir = $dirs[$index];
						}
					}

					$sortOpt = $this->dottedFieldToUnique($sortOpt);

					// see http://blog.feedmarker.com/2006/02/01/how-to-do-natural-alpha-numeric-sort-in-mysql/
					// for why we're + 0 here. Basically, coercing an alphanum sort instead of straight string
					if (in_array($sortOpt, $numericSort)) {
						$sortOpt .= '+ 0';
					}
					$sortBy .= $sep . $sortOpt . ' ' . $dir;
					$sep = ', ';
				}
				$index++;
			}
		} else {
			$sortBy = 'ID '.$dir;
		}

		return $sortBy;
	}

	/**
	 * Return any fields that need special 'numeric' sorting
	 */
	protected function getNumericSortFields() {
		return array();
	}

	/**
	 * Get any field formatting options.
	 */
	public function getFieldFormats() {
		return array();
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
		if ($this->ClearColumns && $this->ClearColumns->getValues()) {
			$fields = $this->ClearColumns->getValues();
			$ret = array();
			foreach ($fields as $field) {
				if (strpos($field, '.')) {
					$field = $this->dottedFieldToUnique($field);
				}
				$ret[] = $field;
			}
			return $ret;
		}
		return array();
	}

	/**
	 * Get the mappings to be used for values of this report.
	 *
	 * This is an array of field names to mappings - where the mapping is evaluated as PHP code. Use '$rawValue'
	 * in your mapping to refer to the raw field value. 
	 *
	 * array(
	 *		'FieldName' => 'Mapping'
	 * );
	 */
	public function getFieldMapping() {
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

		
		$content = "Formatter for $format not found!";
		$formatter = ucfirst($renderFormat).'ReportFormatter';
		if (class_exists($formatter)) {
			$formatter = new $formatter($this);
			$content = $formatter->format();
		}

		$classes = array_reverse(ClassInfo::ancestry(get_class($this)));
		$templates = array();
		foreach ($classes as $cls) {
			if ($cls == 'AdvancedReport') {
				// catchall
				$templates[] = 'AdvancedReport';
				break;
			}
			$templates[] = $cls . '_' . $renderFormat;
		}

		$output = $this->customise(array('ReportContent' => $content, 'Format' => $format))->renderWith($templates);

		if (!$convertTo) {
			if ($store) {
				// stick it in a temp file?
				$outputFile = tempnam(TEMP_FOLDER, $format);
				if (file_put_contents($outputFile, $output)) {
					return new AdvancedReportOutput(null, $outputFile);
				} else {
					throw new Exception("Failed creating report"); 
				}

			} else {
				return new AdvancedReportOutput($output);
			}
		}

		// hard coded for now, need proper content transformations....
		switch ($convertTo) {
			case 'pdf': {
				if ($store) {
					$filename = singleton('PdfRenditionService')->render($output);
					return new AdvancedReportOutput(null, $filename);
				} else {
					singleton('PdfRenditionService')->render($output, 'browser');
					return new AdvancedReportOutput();
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
class AdvancedReportOutput {
	public $filename;
	public $content;

	public function __construct($content = null, $filename=null) {
		$this->filename = $filename;
		$this->content = $content;
	}
}
