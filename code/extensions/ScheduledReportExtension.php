<?php

/**
 * Description of ScheduledReportExtension
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ScheduledReportExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = array(
        'ScheduledTitle'        => 'Varchar(255)',
        'FirstGeneration'        => 'SS_Datetime',
        'RegenerateEvery'        => "Enum(',Hour,Day,Week,Fortnight,Month,Year')",
        'RegenerateFree'        => 'Varchar',
        'SendReportTo'            => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'ScheduledJob'            => 'QueuedJobDescriptor',
    );

    /**
     *
     * @param FieldSet $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (class_exists('AbstractQueuedJob')) {
            $fields->addFieldsToTab('Root.Schedule', array(
                new TextField('ScheduledTitle', _t('AdvancedReport.SCHEDULED_TITLE', 'Title for scheduled report')),
                $dt = new Datetimefield('FirstGeneration', _t('AdvancedReport.FIRST_GENERATION', 'First generation')),
                new DropdownField(
                    'RegenerateEvery',
                    _t('AdvancedReport.REGENERATE_EVERY', 'Regenerate every'),
                    $this->owner->dbObject('RegenerateEvery')->enumValues()
                ),
                new TextField(
                    'RegenerateFree',
                    _t('AdvancedReport.REGENERATE_FREE', 'Scheduled (in strtotime format from first generation)')
                ),
                new TextField('SendReportTo', _t('AdvancedReport.SEND_TO', 'Send to email addresses')),
            ));

            if ($this->owner->ScheduledJobID) {
                $jobTime = $this->owner->ScheduledJob()->StartAfter;
                $fields->addFieldsToTab('Root.Schedule', array(
                    new ReadonlyField('NextRunDate', _t('AdvancedReport.NEXT_RUN_DATE', 'Next run date'), $jobTime)
                ));
            }

            $dt->getDateField()->setConfig('showcalendar', true);
            $dt->getTimeField()->setConfig('showdropdown', true);
        } else {
            $fields->addFieldToTab(
                'Root.Schedule',
                new LiteralField('WARNING', 'You must install the Queued Jobs module to schedule reports')
            );
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->ScheduledTitle) {
            $this->owner->ScheduledTitle = $this->owner->Title;
        }

        if ($this->owner->FirstGeneration) {
            $changed = $this->owner->getChangedFields();
            $changed = false
                || isset($changed['FirstGeneration'])
                || isset($changed['RegenerateEvery'])
                || isset($changed['RegenerateFree']);

            if ($changed && $this->owner->ScheduledJobID) {
                if ($this->owner->ScheduledJob()->ID) {
                    $this->owner->ScheduledJob()->delete();
                }

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
