<?php

/**
 *
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class DecimalHoursFormatter implements ReportFieldFormatter {
	public function format($value) {
		$hours = floor($value);
		$mins  = round(($value - $hours) * 60);
		return $hours . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT);
	}

	public function label() {
		return 'Decimal to Time';
	}
}
