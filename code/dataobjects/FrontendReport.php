<?php

/**
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class FrontendReport extends DataObject {
    public static $db = array(
		'Title' => 'Varchar(128)',
		'Description' => 'Text',
	);

	public static $has_one = array(
		'HTMLFile' => 'File',
		'CSVFile' => 'File',
		'PDFFile' => 'File',
	);

	public function getReportName() {
		throw new Exception("Abstract method called; please implement getReportName()");
	}

	public function updateReportFields($fields) {
		
	}
}

