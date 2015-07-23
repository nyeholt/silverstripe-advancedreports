<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class CsvReportFormatter extends ReportFormatter {

	protected function getOutputFormat() {
		return 'csv';
	}

	/**
	 * Create a header for the report
	 */
	protected function createHeader($tableName) {
		$header = array();
		// just join it all up
		foreach ($this->headers as $field => $display) {
			$header[] = $display;
		}

		return '"'.implode('", "', $header).'"';
	}

	/**
	 * Create a body for the report
	 */
	protected function createBody($tableName, $tableData) {
		$body = array();

		$formatting = $this->getFieldFormatters();

		foreach ($tableData as $row) {
			$csvRow = array();
			foreach ($row as $field => $value) {
				if (isset($formatting[$field])) {
					$value = $formatting[$field]->format($value);
				}
				$csvRow[] = $value;
			}
			$body[] = '"'.implode('", "', $csvRow).'"';
		}

		return implode("\n", $body);
	}

	/**
	 * Format the header and body into a complete report output.
	 */
	protected function formatReport($reportPieces) {
		$bits = '';

		foreach ($reportPieces as $tableName => $table) {
			if ($tableName != ReportFormatter::DEFAULT_TABLE_NAME) {
				$bits .= '"'.$tableName.'",';
			}
			$bits .=  $table['Header']."\n".$table['Body']."\n,\n,\n";
		}
		return $bits;
	}
}
