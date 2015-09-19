<?php

/**
 *
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class FullDateFromTimestampFormatter implements ReportFieldFormatter {
	public function format($value) {
		$value = (double) $value;
		if ($value) {
			return date('Y-m-d H:i:s', $value);
		}
	}

	public function label() {
		return "Timestamp to Y-m-d H:i:s";
	}
}

class DateFromTimestampFormatter implements ReportFieldFormatter {
	public function format($value) {
		$value = (double) $value;
		if ($value) {
			return date('Y-m-d', $value);
		}
	}

	public function label() {
		return "Timestamp to Y-m-d";
	}
}

