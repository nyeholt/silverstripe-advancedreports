<?php
/**
 * Allows scheduling advanced reports to be generated at regular intervals.
 */
class ScheduledAdvancedReportExtension extends DataExtension {
	/**
	 * @var array
	 */
	private static $db = array(
		'Scheduled' => 'Boolean',
		'ScheduledTitle' => 'Varchar(255)',
		'FirstScheduled' => 'SS_Datetime',
		'ScheduleEvery' => 'Enum(array("", "Hour", "Day", "Week", "Fortnight", "Month", "Year", "Custom"))',
		'ScheduleEveryCustom' => 'Varchar(50)',
		'EmailScheduledTo' => 'Varchar(255)'
	);

	/**
	 * @var array
	 */
	private static $has_one = array(
		'QueuedJob' => 'QueuedJobDescriptor'
	);

	/**
	 * @param FieldList $fields
	 */
	public function updateCMSFields(FieldList $fields) {
		Requirements::javascript('advancedreports/javascript/scheduled-report-settings.js');

		$first = new DatetimeField(
			'FirstScheduled',
			_t('AdvancedReport.FIRST_SCHEDULED_GENERATION', 'First scheduled generation')
		);
		$first->getDateField()->setConfig('showcalendar', true);

		if($this->owner->QueuedJobID) {
			$next = $this->owner->QueuedJob()->obj('StartAfter')->Nice();
		} else {
			$next = _t('AdvancedReport.NOT_CURRENTLY_SCHEDULED', 'not currently scheduled');
		}

		$fields->addFieldsToTab('Root.Scheduling', array(
			new CheckboxField(
				'Scheduled',
				_t('AdvancedReport.SCHEDULE_REPORT_GENERATION', 'Schedule report generation?')
			),
			new TextField(
				'ScheduledTitle',
				_t('AdvancedReport.SCHEDULED_REPORT_TITLE', 'Scheduled report title')
			),
			$first,
			new DropdownField(
				'ScheduleEvery',
				_t('AdvancedReport.SCHEDULE_EVERY', 'Schedule every'),
				$this->owner->dbObject('ScheduleEvery')->enumValues()
			),
			TextField::create('ScheduleEveryCustom')
				->setTitle(_t('AdvancedReport.CUSTOM_INTERVAL', 'Custom interval'))
				->setDescription(_t(
					'AdvancedReport.USING_STRTOTIME_FORMAT',
					'Using relative <a href="{link}" target="_blank">strtotime</a> format',
					null,
					array('link' => 'http://php.net/strtotime')
				)
			),
			new TextField(
				'EmailScheduledTo',
				_t('AdvancedReport.EMAIL_SCHEDULED_TO', 'Email scheduled reports to')
			),
			new ReadonlyField(
				'NextScheduledGeneration',
				_t('AdvancedReport.NEXT_SCHEDULED_GENERATION', 'Next scheduled generation'),
				$next
			)
		));
	}

	public function onBeforeWrite() {
		if(!$this->owner->ScheduledTitle) {
			$this->owner->ScheduledTitle = $this->owner->Title;
		}

		$jobExists = $this->owner->QueuedJob()->exists();
		if($this->owner->Scheduled) {
			$changed = $this->owner->getChangedFields();
			$changed = isset($changed['FirstScheduled'])
				|| isset($changed['ScheduleEvery'])
				|| isset($changed['ScheduleEveryCustom']);

			if($jobExists && $changed) {
				$this->owner->QueuedJob()->delete();
				$this->owner->QueuedJobID = 0;
			}

			if(!$jobExists) {
				if($this->owner->FirstScheduled) {
					$time = date('Y-m-d H:i:s', strtotime($this->owner->FirstScheduled));
				} else {
					$time = date('Y-m-d H:i:s');
				}

				$this->owner->QueuedJobID = singleton('QueuedJobService')->queueJob(
					new ScheduledReportJob($this->owner), $time
				);
			}
		} else {
			if($jobExists) {
				$this->owner->QueuedJob()->delete();
				$this->owner->QueuedJobID = 0;
			}
		}
	}
}
