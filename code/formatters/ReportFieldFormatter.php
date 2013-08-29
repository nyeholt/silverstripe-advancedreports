<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
interface ReportFieldFormatter {
	
	/**
	 * Return a 'nice' label for this formatter
	 */
	public function label();
	
	/**
	 * Format a report field in a particular way
	 * 
	 * @param mixed $value
	 */
	public function format($value);
}
