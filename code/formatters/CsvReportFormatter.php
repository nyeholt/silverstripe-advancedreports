<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class CsvReportFormatter extends ReportFormatter {

	/**
	 * Create a header for the report
	 */
	protected function createHeader() {
		$header = array();
		// just join it all up
		foreach ($this->headers as $field => $display) {
			$header[] = $display;
		}

		return '"'.implode('","', $header).'"';
	}

	/**
	 * Create a body for the report
	 */
	protected function createBody() {
		$body = array();
		foreach ($this->data as $row) {
			foreach ($row as $field => $value) {
				$body[] = $value;
			}
		}

		return '"'.implode('","', $body).'"';
	}

	/**
	 * Format the header and body into a complete report output.
	 */
	protected function formatReport($reportPieces) {
		return $reportPieces['Header']."\n".$reportPieces['Body'];
	}
}