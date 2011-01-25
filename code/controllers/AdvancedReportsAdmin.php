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

