<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class ReportHolder extends Page {
	public static $allowed_children = array(
		'ReportPage',
	);

    public function getReports() {
		return $this->Children();
	}
}

class ReportHolder_Controller extends Page_Controller {
	
}