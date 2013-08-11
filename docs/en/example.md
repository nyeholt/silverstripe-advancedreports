Example Implementation
======================

This example will go through an example implementation of a custom report
type. A basic page view tracking and reporting system will be implemented.

The first step in implementing a page view report is to set up the data model.
For this example, we will create a `PageView` data object. Each time a page
is viewed, a new `PageView` record will be created with some basic statistics.

The `PageView` object has a relationship to the page that was viewed, as well
as some basic information about the user that viewed the page:

```php
<?php
class PageView extends DataObject {
	private static $db = array(
		'IPAddress' => 'Varchar(45)',
		'UserAgent' => 'Varchar(255)'
	);

	private static $has_one = array(
		'Page' => 'Page'
	);
}
```

We then create an extension, which will be applied to the `Page`
class. Whenever a page is viewed, it will create a new `PageView` record and
save it to the database.

```php
<?php
class PageViewExtension extends DataExtension {
	public function contentcontrollerInit($page) {
		$view = new PageView();
		$view->update(array(
			'IPAddress' => $_SERVER['REMOTE_ADDR'],
			'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
			'PageID' => $page->ID
		));
		$view->write();
	}
}

```

We then apply this extension to the `Page` class using the YAML
config system:

```yaml
Page:
  extensions:
    - PageViewExtension
```

So now we have a system which will create a page view record each time a page
is viewed. Now we will create a report for this data, which will allow an
admin to export a list of page views for a set of pages.

This is done by extending the base `AdvancedReport` class:

```php
<?php
class PageViewsReport extends AdvancedReport {
	private static $has_many = array(
		'Pages' => 'Page'
	);

	public function getSettingsFields() {
		$fields = parent::getSettingsFields();

		$fields->insertAfter(
			new GridField(
				'Pages', '', $this->Pages(), GridFieldConfig_RelationEditor::create()
			),
			'Title'
		);

		return $fields;
	}

	public function getReportName() {
		return 'Page Views Report';
	}

	protected function getReportableFields() {
		return array(
			'Created' => 'Time',
			'Page.Title' => 'Page Title',
			'IPAddress' => 'IP Address',
			'UserAgent' => 'User Agent'
		);
	}

	public function getDataObjects() {
		return PageView::get()
			->filter('PageID', $this->Pages()->column('ID'))
			->where($this->getConditions())
			->sort($this->getSort());
	}
}

```
