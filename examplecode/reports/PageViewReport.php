<?php

/**
 * Example report class 
 * 
 * make it extends AdvancedReport to actually use it...
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class PageViewReport  {

	public static $db = array(
		'Pages' => 'MultiValueField',
	);
		
	public function updateReportFields($fields) {
		parent::updateReportFields($fields);

		$pages = DataObject::get('Page', '', 'Title ASC');
		$vals = $pages->map();

		$fields->push(new MultiValueListField('Pages', 'Pages', $vals));
	}

	protected function getReportableFields() {
		return array(
			'Platform' => 'Platform',
			'Browser' => 'Browser',
			'BrowserVersion' => 'BrowserVersion',
			'ViewDayName' => 'ViewDayName',
			'ViewMonth' => 'ViewMonth',
			'ViewDay' => 'ViewDay',
			'ViewYear' => 'ViewYear',
			'PageName' => 'PageName',
			'Created' => 'Created',
			'ViewNum' => 'ViewNum',
		);
	}

	public function getReportName() {
		return 'Page View Report';
	}

	public function getFieldMapping() {
		return array();
	}

	public function  getDataObjects() {
		$sortBy = $this->getSort();
		
		$items = DataObject::get('PageView', $this->getFilter(), $sortBy);
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

		if ($this->Pages && count($this->Pages->getValues())) {
			$filter = array('PageID IN' => $this->Pages->getValues());
			$filter = singleton('FRUtils')->dbQuote($filter);
			$where .= $sep . $filter;
		}

		return $where;
	}

	/**
	 * Overwrites SiteTree.getCMSFields.
	 *
	 * This method creates a customised CMS form for back-end user.
	 *
	 * @return fieldset
	 */ 
	function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeFieldsFromTab("Root.Main", array(
			"Pages",
		));
		
		return $fields;
	}
}