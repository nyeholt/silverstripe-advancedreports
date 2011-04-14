<?php

/**
 * A Report Page is a frontend interface to creating and generating reports. 
 * 
 * The page shown to the user that shows input fields for changing
 * the structure of the report, as well as all previously generated
 * instances of this report
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class ReportPage extends Page implements PermissionProvider {
    public static $db = array(
		'ReportType' => 'Varchar(64)',
	);

	public static $has_one = array(
		'ReportTemplate' => 'AdvancedReport',
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$types = ClassInfo::subclassesFor('AdvancedReport');
		array_shift($types);
		array_unshift($types, '');
		$fields->addFieldToTab('Root.Content.Main', new DropdownField('ReportType', _t('AdvancedReport.REPORT_TYPE', 'Report Type'), $types), 'Content');
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
			$template->Title = $this->Title . ' Preview';
			$template->write();

			$this->ReportTemplateID = $template->ID;
		}
	}

	/**
	 * This is needed because a has_many to AdvancedReport seems to think it needs
	 * a parent ID. I haven't got time to figure out why SilverStripe thinks there's a parent ID involved,
	 * so be gone with it.
	 *
	 * @return DataObjectSet
	 */
	public function getReports() {
		return DataObject::get('AdvancedReport', '"ReportID" = '.((int) $this->ID), 'Created DESC');
	}
	
	public function providePermissions() {
		return array(
			'EDIT_ADVANCED_REPORT' => array(
				'name' => _t('AdvancedReport.EDIT', 'Create and edit Advanced Report pages'),
				'category' => _t('AdvancedReport.ADVANCED_REPORTS_CATEGORY', 'Advanced Reports permissions'),
				'help' => _t('AdvancedReport.ADVANCED_REPORTS_EDIT_HELP', 'Users with this permission can create new Report Pages from a Report Holder page'),
				'sort' => 400
			),
			'GENERATE_ADVANCED_REPORT' => array(
				'name' => _t('AdvancedReport.GENERATE', 'Generate an Advanced Report'),
				'category' => _t('AdvancedReport.ADVANCED_REPORTS_CATEGORY', 'Advanced Reports permissions'),
				'help' => _t('AdvancedReport.ADVANCED_REPORTS_GENERATE_HELP', 'Users with this permission can generate reports based on existing report templates via a frontend Report Page'),
				'sort' => 400
			),
		);
	}
}

class ReportPage_Controller extends Page_Controller {
	public static $allowed_actions = array(
		'ReportForm',
		'DeleteSavedReportForm',
		'htmlpreview',
		'delete',
	);

	public function init() {
		parent::init();
		Requirements::themedCSS('ReportPage');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');
		Requirements::javascript('advancedreports/javascript/advancedreports.js');

	}

	public function ReportForm() {
		$template = $this->data()->ReportTemplate();
		if ($template && $template->ID && $this->data()->canEdit()) {
			$fields = new FieldSet();
			$template->updateReportFields($fields);

			$fields->push(new TextField('Title', _t('ReportPage.NEW_TITLE', 'Title for generated reports')));

			$actions = new FieldSet(
				new FormAction('save', _t('AdvancedReports.SAVE', 'Save')),
				new FormAction('preview', _t('AdvancedReports.PREVIEW', 'Preview')),
				new FormAction('generate', _t('AdvancedReports.GENERATE', 'Generate')),
				new FormAction('delete', _t('AdvancedReports.DELETE', 'Delete'))
			);

			$form = new Form($this, 'ReportForm', $fields, $actions);
			$form->loadDataFrom($template);
			$form->addExtraClass('ReportForm');
			return $form;
		} else if (Permission::check('GENERATE_ADVANCED_REPORT', 'any')) {
			$fields = new FieldSet();
			$fields->push(new TextField('Title', _t('ReportPage.NEW_TITLE', 'Title for generated reports')));
			
			$actions = new FieldSet(
				new FormAction('preview', _t('AdvancedReports.PREVIEW', 'Preview')),
				new FormAction('generate', _t('AdvancedReports.GENERATE', 'Generate'))
			);

			$form = new Form($this, 'ReportForm', $fields, $actions);
			$form->loadDataFrom($template);
			$form->addExtraClass('ReportForm');
			return $form;
			
		}

		return null;
	}

	public function DeleteSavedReportForm() {
		$fields = new FieldSet(
			new HiddenField('ReportID')
		);

		return new Form($this, 'DeleteSavedReportForm', $fields, new FieldSet(new FormAction('deletereport', '')));
	}

	/**
	 * OHHH sorry about this hack. 
	 *
	 * @return String
	 */
	public function SecurityID() {
		return Session::get('SecurityID');
	}

	/**
	 * Update the report template definition
	 * 
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request 
	 */
	public function save($data, Form $form, $request) {
		if ($this->data()->canEdit()) {
			$this->saveReportTemplate($data, $form);
		}
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
		if ($this->data()->canEdit() || Permission::check('GENERATE_ADVANCED_REPORT', 'any')) {
			$this->saveReportTemplate($data, $form);
		}
		
		$this->redirect($this->data()->Link('htmlpreview'));
	}

	/**
	 * View a report in HTML format
	 */
	public function htmlpreview() {
		// create the HTML report and spit it out immediately
		$format = isset($_REQUEST['f']) ? $_REQUEST['f'] : 'html';
		$output = $this->data()->ReportTemplate()->createReport($format);
		if ($output->filename) {
			// do nothing
		}

		if ($output->content) {
			echo $output->content;
		}
	}

	/**
	 * Generates the given report.
	 *
	 * This creates an HTML, CSV and PDF version of the report, stores the
	 * report in its list of 'saved' reports, and clears up the 'template'
	 * to be reused.
	 * 
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function generate(array $data, Form $form, $request) {
		// okay if we're generating we need to do a few things
		// 1. clone the current template as a saved version
		// 2. create the actual files
		if ($this->data()->canEdit() || Permission::check('GENERATE_ADVANCED_REPORT', 'any')) {
			$this->saveReportTemplate($data, $form);
			
			$currentTemplate = $this->data()->ReportTemplate();

			$report = $currentTemplate->duplicate(false);
//			$report->Title = isset($data['ReportTitle']) ? $data['ReportTitle'] : $this->data()->Title . ' ' . date('Y-m-d');
			$report->ReportID = $this->data()->ID;
			$report->write();

			$report->generateReport('html');
			$report->generateReport('csv');
			$report->generateReport('pdf');
		}

		$this->redirect($this->data()->Link());
	}

	/**
	 * Delete an actual report page.
	 *
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function delete($data, $form, $request) {
		if ($this->data()->canDelete()) {
			$parent = $this->data()->Parent();
			$this->data()->doUnpublish();
			$this->data()->delete();
			$this->redirect($parent->Link());
		} else {
			$form->sessionMessage(_t('ReportPage.DELETE_WARNING', 'You do not have permission to delete the report'), 'warning');
			$this->redirect($this->data()->Link());
		}
	}

	/**
	 * Delete a previously saved generated report
	 *
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function deletereport($data, $form, $request) {
		if ($this->data()->canDelete()) {
			$reportId = isset($data['ReportID']) ? $data['ReportID'] : null;
			if ($reportId) {
				$report = DataObject::get_by_id('AdvancedReport', $reportId);
				if ($report) {
					$report->delete();
				}
			}
		}
		

		$this->redirect($this->data()->Link());
	}
}
