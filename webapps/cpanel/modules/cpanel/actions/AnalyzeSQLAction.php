<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSQLAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();

    $moduleName = $request->getParameter('target');
    $type = $request->getParameter('type');
    $from = $request->getParameter('from');
    $to = $request->getParameter('to');
    $defaultOrderIndex = '3';

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');

    if ($type === 'default') {
      $orderIndex = $request->getParameter('orderBySqlDefault', $defaultOrderIndex);
      $orderType = $this->getOrderType($orderIndex);
      $slowQueries = $sqlRequestsDAO->findSlowStatement($moduleName, $from, $to, $orderType);

    } else {
      $orderIndex = $request->getParameter('orderBySqlPrepared', $defaultOrderIndex);
      $orderType = $this->getOrderType($orderIndex);
      $slowQueries = $sqlRequestsDAO->findSlowPreparedStatement($moduleName, $from, $to, $orderType);
    }

    $view = $this->getView();
    $view->setAttribute('slowQueries', $slowQueries);
    $view->setAttribute('orderName', 'orderBySql' . ucfirst($type));
    $view->setAttribute('orderIndex', $orderIndex);

    return Delta_View::SUCCESS;
  }

  private function getOrderType($orderIndex)
  {
    $result = NULL;

    // 平均時間ソート
    if ($orderIndex === '3') {
      $result = 'averageTime';

    // 実行回数ソート
    } else if ($orderIndex === '2') {
      $result = 'manyQuery';
    }

    return $result;
  }
}
