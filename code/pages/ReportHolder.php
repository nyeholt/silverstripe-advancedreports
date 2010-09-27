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
	public function Form() {
		if ($this->data()->canEdit()) {
			$classes = ClassInfo::subclassesFor('FrontendReport');
			array_shift($classes);
			//array_unshift($classes, '');

			$classTitles = array('' => '');
			foreach ($classes as $class) {
				$classTitles[$class] = Object::create($class)->singular_name();
			}

			$fields = new FieldSet(
				new TextField('ReportName', _t('ReportHolder.REPORT_TITLE', 'Title')),
				new TextareaField('ReportDescription', _t('ReportHolder.REPORT_DESCRIPTION', 'Description')),
				new DropdownField('ReportType', _t('ReportHolder.REPORT_TYPE', 'Type'), $classTitles)
			);

			$actions = new FieldSet(new FormAction('createreport', _t('ReportHolder.CREATE_REPORT', 'Create')));
			$form = new Form($this, 'Form', $fields, $actions, new RequiredFields('ReportName', 'ReportType'));
			$form->addExtraClass('newReportForm');
			return $form;
		}
	}

	/**
	 * Create a new report
	 *
	 * @param array  $data
	 * @param Form $form
	 */
	public function createreport($data, $form) {
		// assume a user's okay if they can edit the reportholder
		// @TODO have a new create permission here?
		if ($this->data()->canEdit()) {
			$type = $data['ReportType'];
			$classes = ClassInfo::subclassesFor('FrontendReport');
			if (!in_array($type, $classes)) {
				throw new Exception("Invalid report type");
			}

			$report = new ReportPage();

			$report->Title = $data['ReportName'];
			$report->MetaDescription = isset($data['ReportDescription']) ? $data['ReportDescription'] : '';
			$report->ReportType = $type;
			$report->ParentID = $this->data()->ID;
			$report->write();
			$report->doPublish();
			$this->redirect($report->Link());
		} else {
			$form->sessionMessage(_t('ReporHolder.NO_PERMISSION', 'You do not have permission to do that'), 'warning');
			$this->redirect($this->data()->Link());
		}
	}
}