<?php

/**
 * An AdvancedReport type that allows a user to select the type they want 
 * to report on. 
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataObjectReport extends AdvancedReport {
	
	public static $db = array(
		'ReportOn'			=> 'Varchar(64)',
	);

	public function getReportName() {
		return "Generic Report";
	}
	
	protected function getReportableFields() {
		$fields = array(
			'ID' => 'ID',
			'Created' => 'Created',
			'LastModified' => 'LastModified',
		);

		if ($this->ReportOn) {
			$dbfields = Object::combined_static($this->ReportOn, 'db');
			$fields = array_merge($fields, $dbfields);
			
			$ones = Object::combined_static($this->ReportOn, 'has_one');
			
			foreach ($ones as $name => $type) {
				$fields[$name . 'ID'] = $name . 'ID';
			}

			$fields = array_combine(array_keys($fields), array_keys($fields));
		}
		
		ksort($fields);
		
		return $fields;
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$types = ClassInfo::subclassesFor('DataObject');
		unset($types[0]);
		ksort($types);
		
		$fields->addFieldToTab('Root.Main', new DropdownField('ReportOn', _t('AdvancedReports.REPORT_ON', 'Report On'), $types));
		
		return $fields;
	}

	public function  getDataObjects() {
		$sortBy = $this->getSort();
		
		$items = DataObject::get($this->ReportOn, $this->getFilter(), $sortBy);
		return $items;
	}
	
	/**
	 * Gets the filter we need for the report
	 *
	 * @param  $agreementFilter
	 * @return string 
	 */
	protected function getFilter() {
		$conditions = $this->getConditions();
		$where = '';
		$sep = '';
		if ($conditions) {
			$where .= $conditions;
			$sep = ' AND ';
		}

		return $where;
	}
}
