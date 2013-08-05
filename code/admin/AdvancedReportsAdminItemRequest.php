<?php
/**
 * Handles requests for managing individual advanced report instances.
 */
class AdvancedReportsAdminItemRequest extends GridFieldDetailForm_ItemRequest {

	private static $allowed_actions = array(
		'ItemEditForm'
	);

	public function ItemEditForm() {
		$form = parent::ItemEditForm();

		if($this->record->isInDB() && $this->record->canGenerate()) {
			$form->Actions()->merge(array(
				FormAction::create('preview', '')
					->setTitle(_t('AdvancedReport.PREVIEW', 'Preview'))
					->setAttribute('target', '_blank')
					->setAttribute('data-icon', 'preview'),
				FormAction::create('generate', '')
					->setTitle(_t('AdvancedReport.GENERATE', 'Generate'))
			));
		}

		return $form;
	}

	public function preview($data, $form) {
		$data = $form->getData();
		$format = $data['PreviewFormat'];

		$result = $this->record->createReport($format);

		if($result->content) {
			return $result->content;
		} else {
			return SS_HTTPRequest::send_file(
				file_get_contents($result->filename), "$data[GeneratedReportTitle].$format"
			);
		}
	}

	public function generate($data, $form) {
		$this->record->prepareAndGenerate();

		return Controller::curr()->redirect($this->Link());
	}

}
