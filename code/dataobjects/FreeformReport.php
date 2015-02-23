<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class FreeformReport extends AdvancedReport {
	
	private static $db = array(
		'DataTypes'			=> 'MultiValueField',
	);
	
	private static $allowed_types = array('Page' => 'Page');
	
	
	/**
	 * Which types have been currently selected?
	 * @var type 
	 */
	protected $selectedTypes = array();
	
	/**
	 * Keeps track of which tables are mapped via what fields 
	 * eg Lga.Vips => has_one
	 * @var type 
	 */
	protected $componentTypeMap = array();
	
	
	public function getSettingsFields() {
		
		$dataTypes = $this->getAvailableTypes();
		$reportable = $this->getReportableFields();
		
		$converted = array();

		foreach($reportable as $k => $v) {
			$converted[$this->dottedFieldToUnique($k)] = $v;
		}
		
		$dataTypes = array_merge(array('' => ''), $dataTypes);
		$types = new MultiValueDropdownField('DataTypes', _t('AdvancedReport.DATA_TYPES', 'Data types'), $dataTypes);
		
		$fieldsGroup = new FieldGroup('Fields',
			$reportFieldsSelect = new MultiValueDropdownField('ReportFields', _t('AdvancedReport.REPORT_FIELDS', 'Report Fields'), $reportable)
		);
		
		$fieldsGroup->push(new MultiValueTextField('Headers', _t('AdvancedReport.HEADERS', 'Header labels')));

		$fieldsGroup->addExtraClass('reportMultiField');
		$reportFieldsSelect->addExtraClass('reportFieldsSelection');
		
		$fieldsGroup->setName('FieldsGroup');
		$fieldsGroup->addExtraClass('advanced-report-fields dropdown');

		$conditions = new FieldGroup('Conditions',
			new MultiValueDropdownField('ConditionFields', _t('AdvancedReport.CONDITION_FIELDS', 'Condition Fields'), $reportable),
			new MultiValueDropdownField('ConditionOps', _t('AdvancedReport.CONDITION_OPERATIONS', 'Op'), $this->config()->allowed_conditions),
			new MultiValueTextField('ConditionValues', _t('AdvancedReport.CONDITION_VALUES', 'Value'))
		);
		
		$conditions->setName('ConditionsGroup');
		$conditions->addExtraClass('dropdown');
		

		$sortGroup = new FieldGroup(
			'Sort',
			new MultiValueDropdownField(
				'SortBy',
				_t('AdvancedReport.SORTED_BY', 'Sorted By'),
				$reportable
			),
			new MultiValueDropdownField(
				'SortDir',
				_t('AdvancedReport.SORT_DIRECTION', 'Sort Direction'),
				array(
					'ASC' => _t('AdvancedReport.ASC', 'Ascending'),
					'DESC' => _t('AdvancedReport.DESC', 'Descending')
				)
			)
		);
		$sortGroup->setName('SortGroup');
		$sortGroup->addExtraClass('dropdown');
		
		$formatters = ClassInfo::implementorsOf('ReportFieldFormatter');
		$fmtrs = array();
		foreach ($formatters as $formatterClass) {
			$formatter = new $formatterClass();
			$fmtrs[$formatterClass] = $formatter->label();
		}

		$fields = new FieldList(
			new TextField('Title', _t('AdvancedReport.TITLE', 'Title')),
			new TextareaField(
				'Description',
				_t('AdvancedReport.DESCRIPTION', 'Description')
			),
			$types,
			$fieldsGroup,
			$conditions,
			new KeyValueField(
				'ReportParams',
				_t('AdvancedReport.REPORT_PARAMETERS', 'Default report parameters')
			),
			$sortGroup,
			new MultiValueDropdownField(
				'NumericSort',
				_t('AdvancedReports.SORT_NUMERICALLY', 'Sort these fields numerically'),
				$reportable
			),
			DropdownField::create('PaginateBy')
				->setTitle(_t('AdvancedReport.PAGINATE_BY', 'Paginate By'))
				->setSource($reportable)
				->setHasEmptyDefault(true),
			TextField::create('PageHeader')
				->setTitle(_t('AdvancedReport.HEADER_TEXT', 'Header text'))
				->setDescription(_t('AdvancedReport.USE_NAME_FOR_PAGE_NAME', 'use $name for the page name'))
				->setValue('$name'),
			new MultiValueDropdownField(
				'AddInRows',
				_t('AdvancedReport.ADD_IN_ROWS', 'Add these columns for each row'),
				$converted
			),
			new MultiValueDropdownField(
				'AddCols',
				_t('AdvancedReport.ADD_IN_ROWS', 'Provide totals for these columns'),
				$converted
			),
			$kv = new KeyValueField(
				'FieldFormatting', 
				_t('AdvancedReport.FORMAT_FIELDS', 'Custom field formatting'), 
				$converted, 
				$fmtrs
			),
			new MultiValueDropdownField(
				'ClearColumns',
				_t('AdvancedReport.CLEARED_COLS', '"Cleared" columns'),
				$converted
			)
		);
		
		if($this->hasMethod('updateReportFields')) {
			Deprecation::notice(
				'3.0',
				'The updateReportFields method is deprecated, instead overload getSettingsFields'
			);

			$this->updateReportFields($fields);
		}

		$this->extend('updateSettingsFields', $fields);
		return $fields;
	}
	
	protected function getAvailableTypes() {
		$types = array(); // self::$allowed_types;
		
		$hasRoot = false;
		$dataTypes = $this->DataTypes->getValues();

		if ($dataTypes) {
			if (is_array($dataTypes)) {
				foreach ($dataTypes as $selected) {
					if (!strlen(trim($selected))) {
						continue;
					}

					if (!class_exists($selected)) {
						continue;
					}

					// make sure we're only processing top level types
					if (!isset($this->config()->allowed_types[$selected])) {
						continue;
					}

					if (isset($this->config()->allowed_types[$selected])) {
						if ($hasRoot) {
							continue;
						}
						$hasRoot = true;
						$types[$selected] = $this->config()->allowed_types[$selected];
					}

					// get all has_many, has_one, many_many field options
					$has_ones = Config::inst()->get($selected, 'has_one');
					if ($has_ones && count($has_ones)) {
						foreach ($has_ones as $name => $type) {
							$types["$selected.$name"] = "$selected.$name";
							$this->componentTypeMap["$selected.$name"] = 'has_one';
						}
					}

					$has_manies = Config::inst()->get($selected, 'has_many');
					if ($has_manies && count($has_manies)) {
						foreach ($has_manies as $name => $type) {
							$types["$selected.$name"] = "$selected.$name";
							$this->componentTypeMap["$selected.$name"] = 'has_many';
						}
					}

					$many_many = Config::inst()->get($selected, 'many_many'); 
					if ($many_many && count($many_many)) {
						foreach ($many_many as $name => $type) {
							$types["$selected.$name"] = "$selected.$name";
							$this->componentTypeMap["$selected.$name"] = 'many_many';
						}
					}
				}
			}
		} 
		
		if (count($types) == 0) {
			$types = $this->config()->allowed_types;
		}

		return $types;
	}
	
	
	/**
	 * Gets an array of field names that can be used in this report
	 *
	 * Override to specify your own values. 
	 */
	protected function getReportableFields() {
		$tables = $this->getQueryTables();
		
		$fields = array();
		
		// now figure out which fields we can now select based on the tables being included;
		foreach ($tables as $type => $table) {
//			if (!class_exists($table)) {
//				continue;
//			}
			$alias = $type;
			$fieldPrefix = $type . '.';
			if (strpos($type, '.')) {
				list($type, $rel) = explode('.', $type);
				$alias = 'tbl_' . $type . '_' . $rel;
				$type = $this->getTypeRelationshipClass($type, $rel);
				$fieldPrefix = $rel . '.';
			}

			foreach (Config::inst()->get($type, 'db') as $field => $type) {
				if($type == 'MultiValueField') {
					$fields["$alias.{$field}Value"] = $fieldPrefix . $field;
				} else {
					$fields["$alias.$field"] = $fieldPrefix . $field;
				}
			}
		}
		
		return $fields; 
	}
	
	/**
	 * Based on the user's selection, get all the query tables that will be included 
	 */
	protected function getQueryTables() {
		$allowedTypes = $this->getAvailableTypes();
		
		$tables = array();
		
		$dataTypes = $this->DataTypes->getValues();
		if (is_array($dataTypes)) {
			foreach ($dataTypes as $type) {
				if (!strlen($type)) {
					continue;
				}
				if (!isset($allowedTypes[$type])) {
					continue;
				}
				if (strpos($type, '.')) {
					list($type, $rel) = explode('.', $type);
					$actualType = $this->getTypeRelationshipClass($type, $rel);
					$tables["$type.$rel"] = "$actualType tbl_{$type}_$rel";
				} else {
					$tables[$type] = $type;
				}
			}
		}
		
		return $tables;
	}
	
	/**
	 * Get the type of a relationship for a given type and relationship name
	 * 
	 * @param string $type
	 * @param string $relName 
	 */
	protected function getTypeRelationshipClass($type, $relName) {
		foreach (array('has_one', 'has_many', 'many_many') as $rel) {
			$options = Config::inst()->get($type, $rel);
			if ($options && isset($options[$relName])) {
				return $options[$relName];
			}
		}
	}
}
