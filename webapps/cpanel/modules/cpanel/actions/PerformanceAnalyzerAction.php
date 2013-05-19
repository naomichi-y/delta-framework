<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class PerformanceAnalyzerAction extends Delta_Action
{
  public function execute()
  {
    // パフォーマンスアナライザがインストールされているかチェック
    $config = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);
    $databaseNamespace = Delta_PerformanceListener::getDatabaseNamespace();
    $hasInstall = FALSE;

    if ($databaseNamespace) {
      $command = $this->getDatabase()->getConnection($databaseNamespace)->getCommand();
      $tableName = Delta_DAOFactory::create('Delta_ActionRequests')->getTableName();

      if ($command->isExistTable($tableName)) {
        $hasInstall = TRUE;
      }
    }

    if ($hasInstall) {
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

    } else {
      $this->getController()->forward('PerformanceAnalyzerInstallForm');

      return Delta_View::NONE;
    }

    return Delta_View::SUCCESS;
  }
}
