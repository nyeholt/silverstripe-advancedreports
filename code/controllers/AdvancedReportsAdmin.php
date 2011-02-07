<?php

/**
* @author Rodney Way <rodney@silverstripe.com.au>
*/
class AdvancedReportsAdmin extends ModelAdmin {
	
	
	//Set it to a default value - contructor will override
	static $managed_models = array('AdvancedReport',
    );
    
	static $url_segment = 'advanced-reports';
	
	static $menu_title = "Advanced Reports";
	
	public static $collection_controller_class = "AdvancedReportsAdmin_CollectionController";
	
	public static $record_controller_class = 'AdvancedReportsAdmin_RecordController';

	function __construct() {
		//Add Advanced Reports prior to parent contruction
		$classes = ClassInfo::subclassesFor('AdvancedReport');
		array_shift($classes);
		self::$managed_models = array_keys($classes);
		parent::__construct();
		
		$this->showImportForm = false;
		
	}

	public function init() {
		parent::init();
				
		Requirements::themedCSS('AdvancedReportsAdmin');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('advancedreports/javascript/advancedreports.js');
	}
}

class AdvancedReportsAdmin_RecordController extends ModelAdmin_RecordController {
	public function EditForm() {
		$form = parent::EditForm();
		
		if ($this->currentRecord->ID) {
			$fields = $form->Fields();
			$link = $this->Link('preview');
			$link = '<a href="' . $link . '" target="_blank">' . _t('AdvancedReports.PREVIEW', 'Preview').'</a>';
			$fields->addFieldToTab('Root.Reports', new LiteralField('Preview', $link));
		}
		
		return $form;
	}
	
	public function preview() {
		$output = $this->currentRecord->createReport('html');
		if ($output->filename) {
			
		}

		if ($output->content) {
			echo $output->content;
		}
	}
}

class AdvancedReportsAdmin_CollectionController extends ModelAdmin_CollectionController {
		
	/**
	 * Override model admin to only return those items that AREN'T generated reports. 
	 * @return SQLQuery
	 */
	function getSearchQuery($searchCriteria) {
		$query = parent::getSearchQuery($searchCriteria);
		$query->where('"ReportID" = 0');
		return $query;
	}
}