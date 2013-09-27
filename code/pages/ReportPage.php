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
class ReportPage extends Page {

	private static $db = array(
		'ReportType' => 'Varchar(64)'
	);

	private static $has_one = array(
		'ReportTemplate' => 'AdvancedReport'
	);

	private static $dependencies = array(
		'reportsService' => '%$AdvancedReportsServiceInterface'
	);

	/**
	 * @var AdvancedReportsServiceInterface
	 */
	private $reportsService;

	public function setReportsService(AdvancedReportsServiceInterface $service) {
		$this->reportsService = $service;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab(
			'Root.Main',
			DropdownField::create('ReportType')
				->setTitle(_t('ReportPage.REPORT_TYPE', 'Report type'))
				->setSource($this->reportsService->getReportTypes())
				->setHasEmptyDefault(true),
			'Content'
		);

		return $fields;
	}

	/**
	 * Creates a report template instance if one does not exist.
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		if(!$this->ReportTemplateID && $this->ReportType && ClassInfo::exists($this->ReportType)) {
			$template = Object::create($this->ReportType);
			$template->Title = $this->Title;
			$template->write();

			$this->ReportTemplateID = $template->ID;
		}
	}

}

class ReportPage_Controller extends Page_Controller {

	private static $allowed_actions = array(
		'settings',
		'preview',
		'GenerateForm',
		'SettingsForm',
		'DeleteGeneratedReportForm'
	);

	public function settings() {
		if(!$this->CanEditTemplate()) {
			return Security::permissionFailure($this);
		}

		return $this->renderWith('Page', array(
			'Title' => _t('ReportPage.EDIT_REPORT_SETTINGS', 'Edit Report Settings'),
			'Form' => $this->SettingsForm()
		));
	}

	public function preview() {
		if(!$this->CanGenerateReport()) {
			return Security::permissionFailure();
		}

		return $this->ReportTemplate()->createReport('html')->content;
	}

	public function GenerateForm() {
		if(!$this->CanGenerateReport()) {
			return null;
		}

		return new Form(
			$this,
			'GenerateForm',
			new FieldList(
				new TextField(
					'Title',
					_t('AdvancedReports.GENERATED_REPORT_TITLE', 'Generated report title'),
					$this->ReportTemplate()->Title
				)
			),
			new FieldList(
				new FormAction('doPreview', _t('AdvancedReports.PREVIEW', 'Preview')),
				new FormAction('doGenerate', _t('AdvancedReports.GENERATE', 'Generate'))
			),
			new RequiredFields('Title')
		);
	}

	public function doPreview($data) {
		$report = clone $this->ReportTemplate();
		$report->Title = $data['Title'];

		return $report->createReport('html')->content;
	}

	public function doGenerate($data, $form) {
		$report = clone $this->ReportTemplate();

		if(!empty($data['Title'])) {
			$report->GeneratedReportTitle = $data['Title'];
		}

		$report->prepareAndGenerate();

		return $this->redirect($this->Link());
	}

	public function SettingsForm() {
		if(!$this->CanEditTemplate()) {
			return null;
		}

		$form = new Form(
			$this,
			'SettingsForm',
			$this->ReportTemplate()->getSettingsFields(),
			new FieldList(
				new FormAction('doSaveSettings', _t('ReportPage.SAVE_SETTINGS', 'Save Settings'))
			)
		);
		$form->loadDataFrom($this->ReportTemplate());

		return $form;
	}

	public function doSaveSettings($data, Form $form) {
		$template = $form->getRecord();
		$form->saveInto($template);
		$template->write();

		$form->sessionMessage(
			_t('ReportPage.SETTINGS_SAVED', 'The report settings have been saved'),
			'good'
		);

		return $this->redirectBack();
	}

	public function DeleteGeneratedReportForm() {
		return new Form(
			$this,
			'DeleteGeneratedReportForm',
			new FieldList(new HiddenField('ID')),
			new FieldList(new FormAction('doDeleteGeneratedReport', _t('ReportPage.DELETE', 'Delete'))),
			new RequiredFields('ID')
		);
	}

	public function doDeleteGeneratedReport($data, $form) {
		if(!$this->ReportTemplateID) {
			$this->httpError(404);
		}

		$report = $this->ReportTemplate()->Reports()->byID($data['ID']);

		if(!$report) {
			$this->httpError(403);
		}

		if(!$report->canDelete()) {
			return Security::permissionFailure($this);
		}

		$report->delete();

		return $this->redirectBack();
	}

	/**
	 * Gets a list of viewable reports, with attached delete forms.
	 *
	 * @return ArrayList
	 */
	public function GeneratedReports() {
		$result = new ArrayList();

		if(!$this->ReportTemplateID) {
			return $result;
		}

		foreach($this->ReportTemplate()->Reports() as $report) {
			if(!$report->canView()) {
				continue;
			}

			if($report->canDelete()) {
				$form = $this->DeleteGeneratedReportForm();
				$form->loadDataFrom($report);

				$report = $report->customise(array(
					'DeleteForm' => $form
				));
			}

			$result->push($report);
		}

		return $result;
	}

	public function CanEditTemplate() {
		return $this->ReportTemplateID && $this->ReportTemplate()->canEdit();
	}

	public function CanGenerateReport() {
		return $this->ReportTemplateID && $this->ReportTemplate()->canGenerate();
	}

}
