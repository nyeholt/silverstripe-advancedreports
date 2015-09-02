<?php

/**
 *
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class RelatedReport extends DataObject  {
	private static $db = array(
		'Title'			=> 'Varchar',
		'Parameters'	=> 'MultiValueField',
		'Sort'			=> 'Int',
	);

	private static $has_one = array(
		'CombinedReport'		=> 'CombinedReport',
		'Report'				=> 'AdvancedReport',
	);

	private static $summary_fields = array(
		'Report.Title',
		'Title',
	);

	public function getCMSFields($params = null) {
		$fields = new FieldList();

		// tabbed or untabbed
		$fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
		$mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));

		$reports = array();
		$reportObjs = AdvancedReport::get()->filter(array('ReportID' => 0));
		if ($reportObjs && $reportObjs->count()) {
			foreach ($reportObjs as $obj) {
				if ($obj instanceof CombinedReport) {
					continue;
				}
				$reports[$obj->ID] = $obj->Title . '(' . $obj->ClassName .')';
			}
		}

		$fields->addFieldsToTab('Root.Main', array(
			new DropdownField('ReportID', 'Related report', $reports),
			new TextField('Title'),
			new KeyValueField('Parameters', 'Parameters to pass to the report'),
			new NumericField('Sort'),
		));

		return $fields;
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canCreate($member = null) {
		if (!$member) $member = Member::currentUser();

		return false
			 || Permission::check('ADMIN', 'any', $member)
			 || Permission::check('CMS_ACCESS_AdvancedReportsAdmin', 'any', $member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
		return $this->CombinedReport()->canView($member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return $this->CombinedReport()->canEdit($member);
	}

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return $this->CombinedReport()->canDelete($member);
	}
}
