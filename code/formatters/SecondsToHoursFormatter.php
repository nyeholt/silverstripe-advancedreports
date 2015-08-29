<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class SecondsToHoursFormatter extends DecimalHoursFormatter implements ReportFieldFormatter {
	public function format($value) {
		if ($value) {
			$value = $value / 3600;
		}
		return parent::format($value);
	}

	public function label() {
		return 'Seconds to hours';
	}
}
