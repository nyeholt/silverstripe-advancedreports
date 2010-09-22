<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class HtmlReportFormatter extends ReportFormatter {

	/**
	 * Create a header for the report
	 */
	protected function createHeader() {
		$header = array();
		// just join it all up
		$header[] = '<thead><tr>';
		foreach ($this->headers as $field => $display) {
			$header[] = '<th class="reportHeader '.$field.'">'.$display.'</th>';
		}
		$header[] = '</tr></thead>';

		return implode("\n", $header);
	}

	/**
	 * Create a body for the report
	 */
	protected function createBody() {
		$body = array();
		$body[] = '<tbody>';

		$rowNum = 1;
		foreach ($this->data as $row) {
			$oddEven = $rowNum % 2 == 0 ? 'even' : 'odd';
			$body[] = '<tr class="reportrow '.$oddEven.'">';
			foreach ($row as $field => $value) {
				if ($value == '(no data)') {
					$value = '<span class="noReportData">(no data)</span>';
				}
				$body[] = '<td class="reportcell '.$field.'">'.$value.'</td>';
			}
			$body[] = '</tr>';
			$rowNum++;
		}

		$body[] = '</tbody>';

		return implode("\n", $body);
	}

	/**
	 * Format the header and body into a complete report output.
	 */
	protected function formatReport($reportPieces) {
		return '<table class="reportttable">'.$reportPieces['Header'].$reportPieces['Body'].'</table>';
	}
}