<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class PageViewExtension extends DataObjectDecorator {
	public function ViewPage() {
		if ($this->owner->ID) {
			$view = new PageView();
			$view->PageID = $this->owner->ID;
			$view->PageName = $this->owner->Title;
			$view->write();
		}
	}
}