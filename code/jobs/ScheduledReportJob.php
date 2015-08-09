<?php
/**
 * Schedules an {@link AdvancedReport} for future generation.
 */
class ScheduledReportJob extends AbstractQueuedJob {
	/**
	 * @param AdvancedReport $report
	 * @param integer $timesGenerated
	 */
	public function __construct($report = null, $timesGenerated = 0) {
		if($report) {
			$this->reportID = $report->ID;
			$this->timesGenerated = $timesGenerated;
			$this->totalSteps = 1;
		}
	}

	/**
	 * @return AdvancedReport
	 */
	public function getReport() {
		return AdvancedReport::get()->byID($this->reportID);
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Generate Report ' . $this->getReport()->ScheduledTitle;
	}

	public function process() {
		$service = singleton('QueuedJobService');
		$report = $this->getReport();

		if(!$report) {
			$this->currentStep++;
			$this->isComplete = true;

			return;
		}

		$clone = clone $report;
		$clone->GeneratedReportTitle = $report->ScheduledTitle;

		$result = $clone->prepareAndGenerate();

		if($report->ScheduleEvery) {
			if($report->ScheduleEvery == 'Custom') {
				$interval = $report->ScheduleEveryCustom;
			} else {
				$interval = $report->ScheduleEvery;
			}

			$next = $service->queueJob(
				new ScheduledReportJob($report, $this->timesGenerated + 1),
				date('Y-m-d H:i:s', strtotime("+1 $interval"))
			);

			$report->QueuedJobID = $next;
		} else {
			$report->Scheduled = false;
			$report->QueuedJobID = 0;
		}

		$report->write();

		if($report->EmailScheduledTo) {
			$email = new Email();
			$email->setTo($report->EmailScheduledTo);
			$email->setSubject($result->Title);
			$email->setBody(_t(
				'ScheduledReportJob.SEE_ATTACHED_REPORT', 'Please see the attached report file'
			));
			$email->attachFile($result->PDFFile()->getFullPath(), $result->PDFFile()->Filename, 'application/pdf');
			$email->send();
		}

		$this->currentStep++;
		$this->isComplete = true;
	}

}
