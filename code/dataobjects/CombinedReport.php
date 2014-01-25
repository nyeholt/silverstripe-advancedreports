<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class CombinedReport extends AdvancedReport {
	private static $db = array(
		'Title'						=> 'Varchar(255)',
		'Description'				=> 'Text',
	);
	
	private static $has_many = array(
		'ChildReports'		=> 'RelatedReport',
	);
	
	public function getCMSFields($params = null) {
		$fields = parent::getCMSFields($params); //  new FieldSet();
		
		// tabbed or untabbed
//		$fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
//		$mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));
		
		$fields->removeByName('Settings');
		
		$fields->addFieldToTab('Root.Main', new TextField('Title'));
		
		$fields->addFieldToTab('Root.Main', new TextareaField('Description'));

		if ($this->ID) {
			// $fields->addFieldToTab('Root.Main', new OrderableComplexTableField($this, 'ChildReports', 'RelatedReport', null, null, '"CombinedReportID" = ' . $this->ID)); 
			$data = RelatedReport::get();
			// ->filter('"CombinedReportID" = ' . $this->ID);
			$fields->addFieldToTab('Root.Main', new GridField('ChildReports', 'Child Reports', $data)); 
		} else {
			$fields->addFieldToTab('Root.Main', new LiteralField('Notice', 'Please save before adding related reports'));
		}
		
		if ($this->ID) {
			
			$link = Controller::curr()->Link('preview');
			$link = '<a href="' . $link . '" target="_blank">' . _t('AdvancedReports.PREVIEW', 'Preview').'</a>';
			$fields->addFieldToTab('Root.Main', new LiteralField('Preview', $link));
		}
		
		return $fields;
		
	}
	
	/**
	 * Prepares this combined report 
	 * 
	 * Functions the same as the parent class, but we need to 
	 * clone the Related reports too
	 * 
	 * @return CombinedReport
	 */
	public function prepareAndGenerate() {
		$report = $this->duplicate(false);
		$report->ReportID = $this->ID;
		$report->Title = $this->GeneratedReportTitle;
		$report->write();
		
		$toClone = $this->ChildReports();
		if ($toClone) {
			foreach ($toClone as $child) {
				$clonedChild = $child->duplicate(false);
				$clonedChild->CombinedReportID = $report->ID;
				$clonedChild->write();
			}
		}

		$report->generateReport('html');
		$report->generateReport('csv');
		if (self::$generate_pdf) {
			$report->generateReport('pdf');
		}
		
		return $report;
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
		if (isset(AdvancedReport::$conversion_formats[$format])) {
			$convertTo = 'pdf';
			$renderFormat = AdvancedReport::$conversion_formats[$format];
		}
		
		$reports = $this->ChildReports();
		if (!$reports->count()) {
			return _t('AdvancedReports.NO_REPORTS_SELECTED', "No reports selected");
		}

		$contents = array();
		foreach ($reports as $report) {
			if (!$report->ReportID) {
				continue;
			}
			
			$params = $report->Parameters;
			
			$report = $report->Report();
			
			if ($params) {
				$params = $params->getValues();
				$baseParams = $report->ReportParams->getValues();
				$params = array_merge($baseParams, $params);
				$report->ReportParams = $params;
			}
			
			$formatter = $report->getReportFormatter($renderFormat);

			if($formatter) {
				$contents[] = $report->customise(array('ReportContent' => $formatter->format()));
			} else {
				$contents[] = new ArrayData(array('ReportContent' => "Formatter for '$renderFormat' not found."));
			}
		}
		
		$classes = array_reverse(ClassInfo::ancestry(get_class($this)));
		$templates = array(get_class($this) . '_' . $renderFormat);
		
		$date = DBField::create('SS_Datetime', time());
		$this->Description = nl2br($this->Description);
		
		$reportData = array('Reports' => new DataObjectSet($contents), 'Format' => $format, 'Now' => $date);

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
	
	
}
