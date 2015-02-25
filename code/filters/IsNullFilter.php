<?php

/**
 * Selects textual content with an 'Is' match between columnname and keyword.
 *
 */

class IsNullFilter extends SearchFilter {
	
	/**
	 * Applies an 'Is' match  on a field value.
	 */
	public function apply(DataQuery $query) {
		
	}
	
	public function isEmpty() {
		return false;
	}

	protected function applyOne(\DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s IS NULL",
			$this->getDbName()
		));
	}

	protected function excludeOne(\DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		return $query->where(sprintf(
			"%s IS NOT NULL",
			$this->getDbName()
		));
	}
}
