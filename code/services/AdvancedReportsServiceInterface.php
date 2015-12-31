<?php
/**
 * A service which exposes advanced reports functionality.
 */
interface AdvancedReportsServiceInterface
{

    /**
     * Gets a mapping of report classes to their user-friendly title.
     *
     * @return array
     */
    public function getReportTypes();
}
