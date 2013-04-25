<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSettingFormAction extends Delta_Action
{
  public function execute()
  {
    $view = $this->getView();
    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequests');

    // データ期間の取得
    $beginDate = $actionRequestsDAO->getBeginSummary();
    $view->setAttribute('beginDate', $beginDate);

    $endDate = $actionRequestsDAO->getEndSummary();
    $view->setAttribute('endDate', $endDate);

    // データサイズの取得
    $tableNames = array('delta_action_requests', 'delta_sql_requests');
    $dataList = array();
    $command = $this->getDatabase()->getConnection()->getCommand();

    foreach ($tableNames as $tableName) {
      $dataList[$tableName] = array(
        'count' => $command->getRecordCount($tableName),
        'size' => $command->getTableSize($tableName)
      );
    }

    $view->setAttribute('dataList', $dataList);

    return Delta_View::SUCCESS;
  }
}
