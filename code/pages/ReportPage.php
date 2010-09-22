<?php

/**
 * The page shown to the user that shows input fields for changing
 * the structure of the report, as well as all previously generated
 * instances of this report
 *
 * The logic 'may' not be correct because I'm so so tired. 
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class ReportPage extends Page {
    public static $db = array(
		'ReportType' => 'Varchar(64)',
	);

	public static $has_one = array(
		'ReportTemplate' => 'FrontendReport',
	);

	public static $has_many = array(
		'Reports' => 'FrontendReport',
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$types = ClassInfo::subclassesFor('FrontendReport');
		array_shift($types);
		array_unshift($types, '');
		$fields->addFieldToTab('Root.Content.Main', new DropdownField('ReportType', _t('FrontendReport.REPORT_TYPE', 'Report Type'), $types), 'Content');

		return $fields;
	}

	/**
	 * The "default" structure used for this report when auto generating etc
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();

		if (!$this->ReportTemplateID && $this->ReportType && ClassInfo::exists($this->ReportType)) {
			$template = Object::create($this->ReportType);

			// create the template first. This is what all actual reports are based on when they're generated, either
			// automatically or by the 'generate' button
			$template->Title = $this->Title . ' Template';
			$template->write();

			$this->ReportTemplateID = $template->ID;
		}
	}
}

class ReportPage_Controller extends Page_Controller {
	public static $allowed_actions = array(
		'ReportForm',
		'htmlpreview',
	);

	public function ReportForm() {
		$template = $this->data()->ReportTemplate();
		if ($template && $template->ID) {
			$fields = new FieldSet();
			$template->updateReportFields($fields);

			$actions = new FieldSet(
				new FormAction('save', _t('FrontendReports.SAVE', 'Save')),
				new FormAction('preview', _t('FrontendReports.PREVIEW', 'Preview')),
				new FormAction('generate', _t('FrontendReports.GENERATE', 'Generate'))
			);

			$form = new Form($this, 'ReportForm', $fields, $actions);
			$form->loadDataFrom($template);
			$form->addExtraClass('ReportForm');
			return $form;
		}
		return null;
	}

	/**
	 * Update the report template definition
	 * 
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request 
	 */
	public function save($data, Form $form, $request) {
		$this->saveReportTemplate($data, $form);
		$this->redirect($this->data()->Link());
	}

	/**
	 * Save the current report template
	 *
	 * @param array $data
	 * @param Form $form
	 */
	protected function saveReportTemplate($data, $form) {
		$template = $this->data()->ReportTemplate();
		if ($template && $template->ID) {
			$form->saveInto($template);
			$template->write();
		}
	}

	/**
	 * Redirects the user to a preview of the current form, if it was generated
	 * right now. 
	 *
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function preview($data, Form $form, $request) {
		$this->saveReportTemplate($data, $form);
		$this->redirect($this->data()->Link('htmlpreview'));
	}

	/**
	 * View a report in HTML format
	 */
	public function htmlpreview() {
		// create the HTML report and spit it out immediately
		echo $this->data()->ReportTemplate()->createReport();
	}

	/**
	 * Generates the given report.
	 *
	 * This creates an HTML, CSV and PDF version of the report. 
	 * 
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function generate(array $data, Form $form, $request) {
		
	}
}
