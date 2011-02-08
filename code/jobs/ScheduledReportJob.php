<?php

/**
 * Description of ScheduledReportJob
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ScheduledReportJob extends AbstractQueuedJob {
	
	public function __construct($report = null, $timesGenerated = 0) {
		if ($report) {
			$this->reportID = $report->ID;
			// captured so we have a unique hash generated for this job 
			$this->timesGenerated = $timesGenerated;

			$this->totalSteps = 1;
		}
	}
	
	public function getReport() {
		return DataObject::get_by_id('AdvancedReport', $this->reportID);
	}
	
	public function getTitle() {
		return 'Regenerate report '.$this->getReport()->ScheduledTitle;
	}
	

	public function setup() {
		
	}
	
	public function process() {
		$report = $this->getReport();
		if ($report) {
			$report->GeneratedReportTitle = $report->ScheduledTitle;
			$report->prepareAndGenerate();
			$report->GeneratedReportTitle = $report->Title;
			
			// figure out what our rescheduled date should be
			$timeStr = $report->RegenerateFree;
			if ($report->RegenerateEvery) {
				$timeStr = '+1 ' . $report->RegenerateEvery;
			}
			
			$this->currentStep++;
			$this->isComplete = true;
			
			$nextGen = date('Y-m-d H:i:s', strtotime($timeStr));
			$nextId = singleton('QueuedJobService')->queueJob(new ScheduledReportJob($report, $this->timesGenerated + 1), $nextGen);
			$report->ScheduledJobID = $nextId;
			$report->write();
		}
	}
}
