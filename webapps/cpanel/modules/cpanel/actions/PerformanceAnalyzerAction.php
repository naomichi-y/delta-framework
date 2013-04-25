<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class PerformanceAnalyzerAction extends Delta_Action
{
  public function execute()
  {
    $command = $this->getDatabase()->getConnection()->getCommand();
    $tableName = Delta_DAOFactory::create('Delta_ActionRequests')->getTableName();

    if (!$command->isExistTable($tableName)) {
      $this->getController()->forward('PerformanceAnalyzerInstallForm');

      return Delta_View::NONE;
    }

    $modules = array();
    $modules[''] = '全てのモジュール';

    foreach (Delta_CoreUtils::getModuleNames() as $module) {
      $modules[$module] = $module;
    }

    $this->getView()->setAttribute('modules', $modules);
    $form = $this->getForm();

    if (!$form->hasName('search')) {
      $from = date('Y-m-d', strtotime('-6 day'));
      $form->set('from', $from);

      $to = date('Y-m-d');
      $form->set('to', $to);
    }

    return Delta_View::SUCCESS;
  }
}
