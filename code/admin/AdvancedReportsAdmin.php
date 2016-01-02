<?php
/**
 * Provides an interface for creating, managing, and generating reports.
 */
class AdvancedReportsAdmin extends ModelAdmin
{

    private static $menu_title = 'Advanced Reports';

    private static $url_segment = 'advanced-reports';

    private static $menu_icon = 'advancedreports/images/bar-chart.png';

    private static $model_importers = array();

    private $managedModels;

    public function init()
    {
        parent::init();
        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript('advancedreports/javascript/advanced-report-settings.js');
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $name = $this->sanitiseClassName($this->modelClass);
        $grid = $form->Fields()->dataFieldByName($name);

        $grid->getConfig()->getComponentByType('GridFieldDetailForm')->setItemRequestClass(
            'AdvancedReportsAdminItemRequest'
        );

        return $form;
    }

    public function getList()
    {
        return parent::getList()->filter('ReportID', 0);
    }

    /**
     * If no managed models are explicitly defined, then default to displaying
     * all available reports.
     *
     * @return array
     */
    public function getManagedModels()
    {
        if ($this->managedModels !== null) {
            return $this->managedModels;
        }

        if ($this->stat('managed_models')) {
            $result = parent::getManagedModels();
        } else {
            $classes = ClassInfo::subclassesFor('AdvancedReport');
            $result = array();

            array_shift($classes);

            foreach ($classes as $class) {
                $result[$class] = array('title' => singleton($class)->singular_name());
            }
        }

        return $this->managedModels = $result;
    }
}
