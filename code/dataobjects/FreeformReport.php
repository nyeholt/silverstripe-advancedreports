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
	
	private static $allowed_types = array('Page' => 'Page', 'Member' => 'Member');
	
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
		
		$fieldsGroup->push(new MultiValueTextField('ReportHeaders', _t('AdvancedReport.HEADERS', 'Header labels')));

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
					
					$many_many = Config::inst()->get($selected, 'belongs_many_many'); 
					if ($many_many && count($many_many)) {
						foreach ($many_many as $name => $type) {
							$types["$selected.$name"] = "$selected.$name";
							$this->componentTypeMap["$selected.$name"] = 'belongs_many_many';
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
			// do we look up ALL fields from parent types too? Only works for 
			// the _base_ class, NOT joined class inherited fields
			$includeInheritedDbFields = Config::INHERITED;
			$alias = '';
			$fieldPrefix = $type . '.';
			if (strpos($type, '.')) {
				// @TODO - this should be changed so that the fields added further below
				// use the full inherited list of fields available on this remote type
				$includeInheritedDbFields = Config::UNINHERITED;
				list($type, $rel) = explode('.', $type);
				$alias = 'tbl_' . $type . '_' . $rel .'.';
				$type = $this->getTypeRelationshipClass($type, $rel);
				$fieldPrefix = $rel . '.';
			}
			
			$fields["{$alias}ID"] = $fieldPrefix . 'ID';
			$fields["{$alias}Created"] = $fieldPrefix . 'Created';
			$fields["{$alias}LastEdited"] = $fieldPrefix . 'LastEdited';

			foreach (Config::inst()->get($type, 'db', $includeInheritedDbFields) as $field => $type) {
				if($type == 'MultiValueField') {
					$fields["$alias{$field}Value"] = $fieldPrefix . $field;
				} else {
					$fields["$alias$field"] = $fieldPrefix . $field;
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
		foreach (array('has_one', 'has_many', 'many_many', 'belongs_many_many') as $rel) {
			$options = Config::inst()->get($type, $rel);
			if ($options && isset($options[$relName])) {
				return $options[$relName];
			}
		}
	}
	
	public function getDataObjects() {
		Versioned::reading_stage('Stage');
		$rows = array();
		$tables = $this->getQueryTables();
		
		$allFields = $this->getReportableFields();
		
		$selectedFields = $this->ReportFields->getValues(); 
		
		$fields = array();
		
		if ($selectedFields) {
			foreach ($selectedFields as $field) {
				if (!isset($allFields[$field])) {
					continue;
				}
				$as = $this->dottedFieldToUnique($allFields[$field]);
				$fields[$as] = $field;
			}
		}
		
		$baseTable = null;
		$relatedTables = array();

		foreach ($tables as $typeName => $alias) {
			if (strpos($typeName, '.')) {
				$relatedTables[$typeName] = $alias;
			} else {
				$baseTable = $typeName;
			}
		}
		
		if (!$baseTable) {
			throw new Exception("All freeform reports must have a base data type selected");
		}

		$multiValue = array();

		// go through and capture all the multivalue fields
		// at the same time, remap Type.Field structures to 
		// TableName.Field
		$remappedFields = array();
		$simpleFields = array();
		foreach ($fields as $alias => $name) {
			$class = '';
			if (strpos($name, '.')) {
				list($class, $field) = explode('.', $name);
			} else {
				$class = $baseTable;
				$field = $name;
			}
			
			// if the name is prefixed, we need to figure out what the actual $class is from the
			// remote join
			if (strpos($name, 'tbl_') === 0) {
				$fieldAlias = $this->dottedFieldToUnique($name);
				$remappedFields[$fieldAlias] = '"' . Convert::raw2sql($class) . '"."' . Convert::raw2sql($field) . '"';
			} else {
//				$remappedFields[$alias] = $field;
				// just store it as is
				$simpleFields[$alias] = $field;
			}

			$field  = preg_replace('/Value$/', '', $field);
			$db = Config::inst()->get($class, 'db');

			if (isset($db[$field]) && $db[$field] == 'MultiValueField') {
				$multiValue[] = $alias;
			}
		}
		
		$dataQuery = new DataQuery($baseTable);
		$dataQuery->setQueriedColumns($simpleFields);
		// converts all the fields being queried into the appropriate 
		// tables for querying. 
		

		
		$query = $dataQuery->getFinalisedQuery();
		
		// explicit fields that we want to query against, that come from joins. 
		// we need to do it this way to ensure that a) the field names in the results match up to 
		// the header labels specified and b) because dataQuery by default doesn't return fields from
		// joined tables, it merely allows for fields from the base dataClass
		foreach ($remappedFields as $alias => $name) {
			$query->selectField($name, $alias);	
		}
		
		// and here's where those joins are added. This is somewhat copied from dataQuery,
		// but modified so that our table aliases are used properly to avoid the bug described at
		// https://github.com/silverstripe/silverstripe-framework/issues/3518
		// it also ensures the alias names are in a format that our header assignment and field retrieval works as
		// expected
		foreach (array_keys($relatedTables) as $relation) {
			$this->applyRelation($baseTable, $query, $relation);
		}
		
		$sort = $this->getSort();
		$query->setOrderBy($sort);
		
		$filter = $this->getConditions();
		$where = $this->getWhereClause($filter, $baseTable);
		$query->setWhere($where);
		
		$sql = $query->sql();
		
		$out = $query->execute();

		$rows = array();

		$headers = $this->getHeaders();
		foreach ($out as $row) {
			foreach ($multiValue as $field) {
				$row[$field] = implode("\n", (array) unserialize($row[$field]));
			}

			$rows[] = $row;
		}
		
		return ArrayList::create($rows);
	}
	
	/**
	 * Traverse the relationship fields, and add the table
	 * mappings to the query object state. This has to be called
	 * in any overloaded {@link SearchFilter->apply()} methods manually.
	 * 
	 * @param String|array $relation The array/dot-syntax relation to follow
	 * @return The model class of the related item
	 */
	protected function applyRelation($modelClass, SQLQuery $query, $relation) {
		// NO-OP
		if(!$relation) return;
		
		$alias = 'tbl_' . $this->dottedFieldToUnique($relation);
		
		if(is_string($relation)) {
			$relation = explode(".", $relation);
		}
		
		foreach($relation as $rel) {
			$model = singleton($modelClass);
			if ($component = $model->has_one($rel)) {
				if(!$query->isJoinedTo($alias)) {
					$has_one = array_flip($model->has_one());
					$foreignKey = $has_one[$component];
					$realModelClass = ClassInfo::table_for_object_field($modelClass, "{$foreignKey}ID");
					$query->addLeftJoin($component,
						"\"$alias\".\"ID\" = \"{$realModelClass}\".\"{$foreignKey}ID\"", $alias);
				
					/**
					 * add join clause to the component's ancestry classes so that the search filter could search on
					 * its ancestor fields.
					 */
					$ancestry = ClassInfo::ancestry($component, true);
					if(!empty($ancestry)){
						$ancestry = array_reverse($ancestry);
						foreach($ancestry as $ancestor){
							if($ancestor != $component){
								$query->addInnerJoin($ancestor, "\"$alias\".\"ID\" = \"$ancestor\".\"ID\"");
							}
						}
					}
				}
				$modelClass = $component;

			} elseif ($component = $model->has_many($rel)) {
				if(!$query->isJoinedTo($alias)) {
					$ancestry = $model->getClassAncestry();
					$foreignKey = $model->getRemoteJoinField($rel);
					$query->addLeftJoin($component,
						"\"$alias\".\"{$foreignKey}\" = \"{$ancestry[0]}\".\"ID\"");
					/**
					 * add join clause to the component's ancestry classes so that the search filter could search on
					 * its ancestor fields.
					 */
					$ancestry = ClassInfo::ancestry($component, true);
					if(!empty($ancestry)){
						$ancestry = array_reverse($ancestry);
						foreach($ancestry as $ancestor){
							if($ancestor != $component){
								$query->addInnerJoin($ancestor, "\"$alias\".\"ID\" = \"$ancestor\".\"ID\"");
							}
						}
					}
				}
				$modelClass = $component;

			} elseif ($component = $model->many_many($rel)) {
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$parentBaseClass = ClassInfo::baseDataClass($parentClass);
				$componentBaseClass = ClassInfo::baseDataClass($componentClass);
				$query->addInnerJoin($relationTable,
					"\"$relationTable\".\"$parentField\" = \"$parentBaseClass\".\"ID\"");
				$query->addLeftJoin($componentBaseClass,
					"\"$relationTable\".\"$componentField\" = \"$alias\".\"ID\"", $alias);
				if(ClassInfo::hasTable($componentClass)) {
					$query->addLeftJoin($componentClass,
						"\"$relationTable\".\"$componentField\" = \"$alias\".\"ID\"", $alias);
				}
				$modelClass = $componentClass;

			}
		}
		
		return $modelClass;
	}
	
	protected function fieldAliasToDataType($alias) {
		$tables = $this->getQueryTables();
		
		foreach ($tables as $table => $aliasing) {
			$bits = explode(' ', $aliasing);
			if (count($bits) == 2) {
				if ($bits[1] == $alias) {
					return $bits[0];
				}
			}
		}
	}
}
