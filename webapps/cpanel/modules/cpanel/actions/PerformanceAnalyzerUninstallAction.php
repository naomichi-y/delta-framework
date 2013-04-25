<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class PerformanceAnalyzerUninstallAction extends Delta_Action
{
  public function execute()
  {
    $this->getController()->getDelegate()->removeEventListener('Delta_PerformanceListener');

    $conn = $this->getDatabase()->getConnection();
    $command = $conn->getCommand();
    $daos = array('Delta_ActionRequestsDAO', 'Delta_SQLRequestsDAO');

    foreach ($daos as $dao) {
      $tableName = Delta_DAOFactory::create($dao)->getTableName();

      if ($command->isExistTable($tableName)) {
        $command->dropTable($tableName);
      }
    }

    return Delta_View::SUCCESS;
  }
}
