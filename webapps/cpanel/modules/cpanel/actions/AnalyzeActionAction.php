<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeActionAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();

    $moduleName = $request->getQuery('target', NULL, TRUE);
    $from = $request->getQuery('from');
    $to = $request->getQuery('to');

    $defaultOrderIndex = '4';
    $orderIndex = $request->getQuery('orderByAction', $defaultOrderIndex);
    $orderType = $this->getOrderType($orderIndex);

    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequestsDAO');

    $slowActions = $actionRequestsDAO->findSlowActions($moduleName, $from, $to, $orderType);

    $view = $this->getView();
    $view->setAttribute('slowActions', $slowActions);
    $view->setAttribute('orderIndex', $orderIndex);

    return Delta_View::SUCCESS;
  }

  private function getOrderType($orderIndex)
  {
    $result = NULL;

    // 平均時間ソート
    if ($orderIndex === '4') {
      $result = 'averageTime';

    // 実行回数ソート
    } else if ($orderIndex === '3') {
      $result = 'manyQuery';

    // 最遅時間ソート
    } else if ($orderIndex === '5') {
      $result = 'longTime';
    }

    return $result;
  }
}
