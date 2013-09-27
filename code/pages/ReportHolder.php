<?php
/**
 * Lists and allows creating reports in the front end.
 */
class ReportHolder extends Page {

	private static $allowed_children = array(
		'ReportPage',
	);

}

class ReportHolder_Controller extends Page_Controller {

	private static $allowed_actions = array(
		'Form'
	);

	public function Form() {
		if(!singleton('AdvancedReport')->canCreate()) {
			return null;
		}

		$classes = ClassInfo::subclassesFor('AdvancedReport');
		$titles = array();

		array_shift($classes);

		foreach($classes as $class) {
			$titles[$class] = singleton($class)->singular_name();
		}

		return new Form(
			$this,
			'Form',
			new FieldList(
				new TextField('Title', _t('ReportHolder.TITLE', 'Title')),
				new TextareaField('Description', _t('ReportHolder.DESCRIPTION', 'Description')),
				DropdownField::create('ClassName')
					->setTitle(_t('ReportHolder.TYPE', 'Type'))
					->setSource($titles)
					->setHasEmptyDefault(true)
			),
			new FieldList(
				new FormAction('doCreate', _t('ReportHolder.CREATE', 'Create'))
			),
			new RequiredFields('Title', 'ClassName')
		);
	}

	public function doCreate($data, $form) {
		if(!singleton('AdvancedReport')->canCreate()) {
			return Security::permissionFailure($this);
		}

		$data = $form->getData();

		$description = $data['Description'];
		$class = $data['ClassName'];

		if(!is_subclass_of($class, 'AdvancedReport')) {
			$form->addErrorMessage(
				'ClassName',
				_t('ReportHolder.INVALID_TYPE', 'An invalid report type was selected'),
				'required'
			);

			return $this->redirectBack();
		}

		$page = new ReportPage();

		$page->update(array(
			'Title' => $data['Title'],
			'Content' => $description ? "<p>$description</p>" : '',
			'ReportType' => $class,
			'ParentID' => $this->data()->ID
		));

		$page->writeToStage('Stage');

		if(Versioned::current_stage() == Versioned::get_live_stage()) {
			$page->doPublish();
		}

		return $this->redirect($page->Link());
	}

}