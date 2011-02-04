<?php

/**
 * Description of ScheduledReportExtension
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ScheduledReportExtension extends DataObjectDecorator {
	public function extraStatics() {
		if (class_exists('AbstractQueuedJob')) {
			return array(
				'db' => array(
					'ScheduledTitle'		=> 'Varchar(255)',
					'FirstGeneration'		=> 'SS_Datetime',
					'RegenerateEvery'		=> "Enum(',Hour,Day,Week,Fortnight,Month,Year')",
					'RegenerateFree'		=> 'Varchar',
				),
				'has_one' => array(
					'ScheduledJob'			=> 'QueuedJobDescriptor',
				)
			);
		}
		return array();
	}
	
	/**
	 *
	 * @param FieldSet $fields 
	 */
	public function updateCMSFields($fields) {
		if (class_exists('AbstractQueuedJob')) {
			$fields->addFieldsToTab('Root.Schedule', array(
				new TextField('ScheduledTitle', _t('AdvancedReports.SCHEDULED_TITLE', 'Title for scheduled report')),
				$dt = new Datetimefield('FirstGeneration', _t('AdvancedReports.FIRST_GENERATION', 'First generation')),
				new DropdownField('RegenerateEvery', _t('AdvancedReports.REGENERATE_EVERY', 'Regenerate every'), $this->owner->dbObject('RegenerateEvery')->enumValues()),
				new TextField('RegenerateFree', _t('AdvancedReports.REGENERATE_FREE','Scheduled (in strtotime format from first generation)'))
			));

			if ($this->owner->ScheduledJobID) {
				$jobTime = $this->owner->ScheduledJob()->StartAfter;
				$fields->addFieldsToTab('Root.Schedule', array(
					new ReadonlyField('NextRunDate', _t('AdvancedReports.NEXT_RUN_DATE', 'Next run date'), $jobTime)
				));
			}

		} else {
			$fields->addFieldToTab('Root.Schedule', new LiteralField('WARNING', 'You must install the Queued Jobs module to schedule reports'));
		}
		
		
		$dt->getDateField()->setConfig('showcalendar', true);
		$dt->getTimeField()->setConfig('showdropdown', true);
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->owner->ScheduledTitle) {
			$this->owner->ScheduledTitle = $this->owner->Title;
		}
		
		if ($this->owner->FirstGeneration) {
			$changed = $this->owner->getChangedFields();
			$changed = isset($changed['FirstGeneration']) || isset($changed['RegenerateEvery']) || isset($changed['RegenerateFree']);

			if ($changed && $this->owner->ScheduledJobID) {
				$this->owner->ScheduledJob()->delete();
				$this->owner->ScheduledJobID = 0;
			}

			if (!$this->owner->ScheduledJobID) {
				$job = new ScheduledReportJob($this->owner);
				$time = date('Y-m-d H:i:s');
				if ($this->owner->FirstGeneration) {
					$time = date('Y-m-d H:i:s', strtotime($this->owner->FirstGeneration));
				}

				$this->owner->ScheduledJobID = singleton('QueuedJobService')->queueJob($job, $time);
			}
		}

		
	}
}
