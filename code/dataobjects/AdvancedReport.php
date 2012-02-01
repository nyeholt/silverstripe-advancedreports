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
class AdvancedReport extends DataObject implements PermissionProvider {

	/**
	 * What conversion needs to occur? 
	 * 
	 * @var array
	 */
	public static $conversion_formats = array('pdf' => 'html');
	
	/**
	 * Allows users to disable pdf gen, which requires a 3rd party module
	 *
	 * @var boolean
	 */
	public static $generate_pdf = true;

	public static $allowed_conditions = array('=' => '=', '<>' => '!=', '>=' => '>=', '>' => '>', '<' => '<', '<=' => '<=', 'IN' => 'In List', 'IS' => 'IS', 'IS NOT' => 'IS NOT');

	public static $db = array(
		'Title'						=> 'Varchar(128)',
		'GeneratedReportTitle'		=> 'Varchar(128)',
		'Description'				=> 'Text',
		'ReportFields'				=> 'MultiValueField',
		'ReportHeaders'				=> 'MultiValueField',
		'ConditionFields'			=> 'MultiValueField',
		'ConditionOps'				=> 'MultiValueField',
		'ConditionValues'			=> 'MultiValueField',
		'PaginateBy'				=> 'Varchar(64)',		// a field used to separate tables (eg financial years)
		'PageHeader'				=> 'Varchar(64)',		// used as a keyworded string for pages
		'SortBy'					=> 'MultiValueField',
		'SortDir'					=> 'MultiValueField',
		'ClearColumns'				=> 'MultiValueField',
		'AddInRows'					=> 'MultiValueField',	// which fields in each row should be added?
		'AddCols'					=> 'MultiValueField',	// Which columns should be added ?
		'NumericSort'				=> 'MultiValueField',	// columns to be numericly sorted
		'ReportParams'				=> 'MultiValueField',	// provide some defaults for parameterised reports
	);

	static $field_labels = array(
		'ReportFields' => 'Fields',
		'ReportHeaders' => 'Field Headers',
		'ConditionFields' => 'Conditions',
		'PaginateBy' => 'Paginate By',						
		'SortBy' => 'Sort Field',
		'SortDir' => 'Sort Order',
		);	
	
	public static $has_one = array(
		'Report' => 'AdvancedReport',			// never set for the 'template' report for a page, but used to
												// list all the generated reports. 
		'HTMLFile' => 'File',
		'CSVFile' => 'File',
		'PDFFile' => 'File',
	);
	
	public static $has_many = array(
		'Reports'		=> 'AdvancedReport',
	);
	
	static $default_sort = "Title ASC";
	
	public static $searchable_fields = array(
		'Title',
		'Description',
	);
	
	public static $summary_fields = array(
		'Title',
		'Description'
	);
	

	/**
	 * Empty method for now - need to hook it up with fields properly 
	 * to be able to display the frontend reporting page still. If in fact that will 
	 * be needed moving forward...
	 *
	 * @param FieldSet $fields 
	 */
	public function updateReportFields($fields) {
		$reportFields = $this->getReportableFields();
		
		$fieldsGroup = new FieldGroup('Fields',
			$reportFieldsSelect = new MultiValueDropdownField('ReportFields', _t('AdvancedReport.REPORT_FIELDS', 'Report Fields'), $reportFields),
			$reportHeadersEntry = new MultiValueTextField('ReportHeaders', _t('AdvancedReport.REPORT_HEADERS', 'Headers'))
		);

		$fieldsGroup->addExtraClass('reportMultiField');
		$reportFieldsSelect->addExtraClass('reportFieldsSelection');
		$reportHeadersEntry->addExtraClass('reportHeadersEntry');

		$conditions = new FieldGroup('Conditions',
			new MultiValueDropdownField('ConditionFields', _t('AdvancedReport.CONDITION_FIELDS', 'Condition Fields'), $reportFields),
			new MultiValueDropdownField('ConditionOps', _t('AdvancedReport.CONDITION_OPERATIONS', 'Op'), self::$allowed_conditions),
			new MultiValueTextField('ConditionValues', _t('AdvancedReport.CONDITION_VALUES', 'Value'))
		);
		
		$conditions->addExtraClass('reportMultiField');
		
		$params = new FieldGroup('Parameters',
			new KeyValueField('ReportParams', _t('AdvancedReport.REPORT_PARAMETERS', 'Default report parameters'))
		);
		$params->addExtraClass('reportMultiField');

		
		$combofield = new FieldGroup('Sorting',
			new MultiValueDropdownField('SortBy', _t('AdvancedReport.SORTED_BY', 'Sorted By'), $reportFields),
			new MultiValueDropdownField('SortDir', _t('AdvancedReport.SORT_DIRECTION', 'Sort Direction'), array('ASC' => _t('AdvancedReport.ASC', 'Ascending'), 'DESC' => _t('AdvancedReport.DESC', 'Descending')))
		);
		$combofield->addExtraClass('reportMultiField');

		$paginateFields = $reportFields;
		array_unshift($paginateFields, '');
		
		$fields->push($fieldsGroup);
		$fields->push($conditions);
		$fields->push($params);
		$fields->push($combofield);
		
		$fields->push(new MultiValueDropdownField('NumericSort', _t('AdvancedReports.SORT_NUMERICALLY', 'Sort these fields numerically'), $this->getReportableFields()));
		
		$convertedFields = array();
		foreach ($reportFields as $k => $v) {
			$convertedFields[$this->dottedFieldToUnique($k)] = $v;
		}
		
		$fields->push(new FieldGroup('Formatting', 
			new DropdownField('PaginateBy', _t('AdvancedReport.PAGINATE_BY', 'Paginate By'), $paginateFields),
			new TextField('PageHeader', _t('AdvancedReport.PAGED_HEADER', 'Header text (use $name for the page name)'), '$name'),
			new MultiValueDropdownField('AddInRows', _t('AdvancedReport.ADD_IN_ROWS', 'Add these columns for each row'), $convertedFields),
			new MultiValueDropdownField('AddCols', _t('AdvancedReport.ADD_IN_ROWS', 'Provide totals for these columns'), $convertedFields),
			new MultiValueDropdownField('ClearColumns', _t('AdvancedReport.CLEARED_COLS', '"Cleared" columns'), $convertedFields)
		));
	}
	
	/**
	 * Overwrites SiteTree.getCMSFields.
	 *
	 * This method creates a customised CMS form for back-end user.
	 *
	 * @return fieldset
	 */ 
	function getCMSFields() {
		
		$fields = new FieldSet();
		$fields->push(new TabSet("Root"));
		
		$fields->addFieldsToTab('Root.Main', array(
			new TextField('Title', _t('AdvancedReport.TITLE', 'Title')),
			new TextareaField('Description', _t('AdvancedReport.DESCRIPTION', 'Description'))
		));

		$reportField = new TableListField(
			'Reports', 
			'AdvancedReport',
			array(
				'Title'					=> 'Title',
				'Created'				=> 'Generated',
				'ID'					=> 'Links'
			),
			'"ReportID" = '.((int) $this->ID),
			'"Created" DESC'
		);
		
		$reportField->setShowPagination(true);
		
		$links = '<a class=\'reportDownloadLink\' target=\'blank\' href=\'".$getFileLink("csv")."\'>CSV</a> '.
					'<a class=\'reportDownloadLink\' target=\'blank\' href=\'".$getFileLink("html")."\'>HTML</a> ';
		
		if (self::$generate_pdf) {
			$links .= '<a class=\'reportDownloadLink\' target=\'blank\' href=\'".$getFileLink("pdf")."\'>PDF</a> ';
		}
		
		$reportField->setFieldFormatting(array(
			'ID' => $links,
		));

		$fields->addFieldsToTab('Root.Reports', array(
			$reportField,
			new TextField('GeneratedReportTitle', _t('AdvancedReport.GENERATED_TITLE', 'Title for generated report'))
		));

		if (Permission::check('GENERATE_ADVANCED_REPORT')) {
			$fields->addFieldToTab('Root.Reports', new CheckboxField('GenerateReport', _t('AdvancedReport.GENERATE_REPORT', 'Generate Report')));
		}

		if (Permission::check('EDIT_ADVANCED_REPORT')) {
			$reportFields = new FieldSet();
			$this->updateReportFields($reportFields);
			$fields->addFieldsToTab("Root.Settings", $reportFields);
		}

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}
	
	/**
	 * Always set generate report to '0' whenever we save. 
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if ($this->GenerateReport) {
			$this->GenerateReport = 0;
			$this->prepareAndGenerate();
		}
		
		$this->GeneratedReportTitle = $this->Title;
	}
	
	/**
	 * Prepare and generate this report into report instances
	 */
	public function prepareAndGenerate() {
		$report = $this->duplicate(false);
		$report->ReportID = $this->ID;
		$report->Title = $this->GeneratedReportTitle;
		$report->write();

		$report->generateReport('html');
		$report->generateReport('csv');
		if (self::$generate_pdf) {
			$report->generateReport('pdf');
		}
	}

	/**
	 * Get a link to a specific instance of this report. 
	 * 
	 * @param String $type 
	 */
	public function getFileLink($type) {
		$prop = strtoupper($type).'File';
		if (isset(self::$has_one[$prop])) {
			$file = $this->$prop();
			return $file->Link();
		}
	}

	/**
	 * Abstract method; actual reports should define this. 
	 */
	public function getReportName() {
		throw new Exception("Abstract method called; please implement getReportName()");
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
	public function dottedFieldToUnique($field) {
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
		$headerTitles = $this->ReportHeaders->getValues();
		$selected = array();
		
		for ($i = 0, $c = count($sel); $i < $c; $i++) {
			$field = $sel[$i];
			$fieldName = $this->dottedFieldToUnique($field);
			
			if (isset($selected[$field])) {
				$selected[$field]++;
				$fieldName .= '_' . $selected[$field];
			}
			
			$headers[$fieldName] = isset($headerTitles[$i]) ? $headerTitles[$i] : $reportFields[$field];
			
			if (!isset($selected[$field])) {
				$selected[$field] = 1;
			}
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
		$sortVals = $this->SortBy->getValues();
		
		if (!$sortVals) {
			$sortVals = array();
		}

		$toSelect = array();
		$selected = array();
		
		// make sure our sortvals are in the query too
		foreach ($sortVals as $sortOpt) {
			if (!in_array($sortOpt, $fields)) {
				$fields[] = $sortOpt;
			}
		}
		
		foreach ($fields as $field) {
			if (isset($reportFields[$field])) {
				$fieldName = $field;
				if (strpos($field, '.')) {
					$parts = explode('.', $field);
					$sep = '';
					$quotedField = implode('"."', $parts);
					
					if (isset($selected[$fieldName])) {
						$selected[$fieldName]++;
						$field = $field . '_' . $selected[$fieldName];
					}
					
					$field = '"'.$quotedField . '" AS "' . $this->dottedFieldToUnique($field) . '"';
				} else {
					if (isset($selected[$fieldName])) {
						$selected[$fieldName]++;
						$field = '"'.$field.'" AS "'.$field . '_' . $selected[$fieldName].'"';
					} else {
						$field = '"'.$field.'"';
					}
				}
				$toSelect[] = $field;
			}

			if (!isset($selected[$fieldName])) {
				$selected[$fieldName] = 1;
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
	 * Return an array of FieldValuePrefix => Callable 
	 * filters for changing the values of the condition value
	 * 
	 * This is so that you can do things like strtotime() in conditions for 
	 * a date field, for example. 
	 * 
	 * Everything AFTER the prefix given here is passed through to the
	 * callable, so you can handle the passing of parameters manually
	 * if needed
	 * 
	 * @return array
	 */
	protected function getConditionFilters() {
		$defaultFilters = new ConditionFilters();

		return array(
			'strtotime:'		=> array($defaultFilters, 'strtotimeDateValue'),
			'param:'			=> array($defaultFilters, 'paramValue'),
		);
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
		
		$conditionFilters = $this->getConditionFilters();
		
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

			switch ($op) {
				case 'IN': {
					$val = explode(',', $val);
					break;
				}
				case 'IS': 
				case 'IS NOT': {
					if (strtolower($val) == 'null') {
						$val = null;
					}
					break;
				}
			}
			
			if (is_array($conditionFilters) && count($conditionFilters)) {
				foreach ($conditionFilters as $prefix => $callable) {
					if (strpos($val, $prefix) === 0) {
						$val = substr($val, strlen($prefix));
						$val = call_user_func($callable, $val, $this);
					}
				}
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
					if (is_array($numericSort) && in_array($sortOpt, $numericSort)) {
						$sortOpt .= '+0';
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
	 * Return any fields that need special 'numeric' sorting. This allows sorting of numbers
	 * in strings, so that
	 * 
	 * 1-document.txt
	 * 2-document.txt
	 * 11-document.txt 
	 * 
	 * are sorted in their correct order, and the '11' document doesn't come immediately
	 * after the '1' document. 
	 * 
	 */
	protected function getNumericSortFields() {
		if ($this->NumericSort) {
			return $this->NumericSort->getValue();
		}
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
	 * Get any field mapping options.
	 * 
	 * These should be of the form
	 * 
	 * <pre>
	 * array(
	 *		'FieldName' => 'functioncall($rawValue)'
	 * );
	 * </pre>
	 * 
	 * The value of the array will be eval'd with the $rawValue available for
	 * formatting the default value.
	 * 
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
	 * @param array $parameters
	 *				An array of parameters that will be used as dynamic replacements
	 */
	public function createReport($format='html', $store = false) {
		Requirements::clear();
		$convertTo = null;
		$renderFormat = $format;
		if (isset(self::$conversion_formats[$format])) {
			$convertTo = 'pdf';
			$renderFormat = self::$conversion_formats[$format];
		}

		$formatter = $this->getReportFormatter($renderFormat);

		if($formatter) {
			$content = $formatter->format();
		} else {
			$content = "Formatter for '$renderFormat' not found.";
		}

		$classes = array_reverse(ClassInfo::ancestry(get_class($this)));
		$templates = array();
		foreach ($classes as $cls) {
			if ($cls == 'AdvancedReport') {
				// catchall
				$templates[] = 'AdvancedReport' . '_' . $renderFormat;
				break;
			}
			$templates[] = $cls . '_' . $renderFormat;
		}

		$date = DBField::create('SS_Datetime', time());
		$this->Text = nl2br($this->Text);
		
		$reportData = array('ReportContent' => $content, 'Format' => $format, 'Now' => $date);
		$additionalData = $this->additionalReportData();
		$reportData = array_merge($reportData, $additionalData);

		$output = $this->customise($reportData)->renderWith($templates);

		if (!$output) {
			// put_contents fails if it's an empty string... 
			$output = " ";
		}

		if (!$convertTo) {
			if ($store) {
				// stick it in a temp file?
				$outputFile = tempnam(TEMP_FOLDER, $format);
				if (file_put_contents($outputFile, $output)) {
					return new AdvancedReportOutput(null, $outputFile);
				} else {
					throw new Exception("Failed creating report in $outputFile"); 
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
	 * Get an array of additional data to add to a report. 
	 * 
	 * @return array
	 */
	protected function additionalReportData() {
		return array();
	}

	/**
	 * Generates an actual report file.
	 *
	 * @param string $format
	 */
	public function generateReport($format='html') {
		$field = strtoupper($format).'FileID';
		$storeIn = $this->getReportFolder();

		// SS hates spaces in here :(
		$name = ereg_replace(' +','-',trim($this->Title));
		$name = ereg_replace('[^A-Za-z0-9.+_\-]','',$name);
		$name = $name . '.' . $format;
		
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
	 * Returns a report formatter instance for an output format.
	 *
	 * @param  string $format
	 * @return ReportFormatter
	 */
	protected function getReportFormatter($format) {
		$class = ucfirst($format) . 'ReportFormatter';

		if(class_exists($class)) {
			return new $class($this);
		}
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
		$folderName = 'advanced-reports/'.$this->ReportID.'/'.$this->ID;
		return Folder::findOrMake($folderName);
	}
	
	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		return Permission::check('ADMIN', 'any', $member) || Permission::check('CMS_ACCESS_AdvancedReportsAdmin', 'any', $member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		return Permission::check('ADMIN', 'any', $member) || Permission::check('CMS_ACCESS_AdvancedReportsAdmin', 'any', $member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canDelete($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		return Permission::check('ADMIN', 'any', $member) || Permission::check('CMS_ACCESS_AdvancedReportsAdmin', 'any', $member);
	}

	/**
	 * @todo Should canCreate be a static method?
	 *
	 * @param Member $member
	 * @return boolean
	 */
	public function canCreate($member = null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		return Permission::check('ADMIN', 'any', $member) || Permission::check('CMS_ACCESS_AdvancedReportsAdmin', 'any', $member);
	}
	
	public function providePermissions() {
		return array(
			'EDIT_ADVANCED_REPORT' => array(
				'name' => _t('AdvancedReport.EDIT', 'Create and edit Advanced Report pages'),
				'category' => _t('AdvancedReport.ADVANCED_REPORTS_CATEGORY', 'Advanced Reports permissions'),
				'help' => _t('AdvancedReport.ADVANCED_REPORTS_EDIT_HELP', 'Users with this permission can create new Report Pages from a Report Holder page'),
				'sort' => 400
			),
			'GENERATE_ADVANCED_REPORT' => array(
				'name' => _t('AdvancedReport.GENERATE', 'Generate an Advanced Report'),
				'category' => _t('AdvancedReport.ADVANCED_REPORTS_CATEGORY', 'Advanced Reports permissions'),
				'help' => _t('AdvancedReport.ADVANCED_REPORTS_GENERATE_HELP', 'Users with this permission can generate reports based on existing report templates via a frontend Report Page'),
				'sort' => 400
			),
		);
	}
}

class ConditionFilters {
	public static $arg_sep = '|';
	
	public function strtotimeDateValue($value) {
		$args = $this->getArgs($value);
		if (!isset($args[1])) {
			$args[1] = 'Y-m-d H:i:s';
		}
		
		return date($args[1], strtotime($args[0]));
	}
	
	public function paramValue($value, $report) {
		$args = $this->getArgs($value);
		$params = $report->ReportParams;
		if ($params) {
			$params = $params->getValues();
		}
		
		if (isset($_GET[$args[0]])) {
			return $_GET[$args[0]];
		}

		if ($params && isset($args[0]) && isset($params[$args[0]])) {
			return $params[$args[0]];
		}
		
		
		return '';
	}

	protected function getArgs($str) {
		return explode(self::$arg_sep, $str);
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
