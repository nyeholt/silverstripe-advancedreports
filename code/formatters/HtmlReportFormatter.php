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
	protected function createHeader($tableName) {
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
	protected function createBody($tableName, $tableData) {
		$body = array();
		$body[] = '<tbody>';

		$rowNum = 1;
		foreach ($tableData as $row) {
			$oddEven = $rowNum % 2 == 0 ? 'even' : 'odd';
			$body[] = '<tr class="reportrow '.$oddEven.'">';
			foreach ($row as $field => $value) {
				$extraclass = '';
				if ($value == '') {
					$extraclass = 'noReportData';
				}
				
				$body[] = '<td class="reportcell '.$field.' '.$extraclass.'">'.$value.'</td>';
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
		$bits = '';
		foreach ($reportPieces as $tableName => $table) {
			if ($tableName != ReportFormatter::DEFAULT_TABLE_NAME) {
				$bits .= '<h2 class="reportTableName">'.$tableName."</h2>\n";
			}
			$bits .=  '<table class="reporttable">'.$table['Header'].$table['Body'].'</table>'."\n\n";
		}
		return $bits;
	}
}