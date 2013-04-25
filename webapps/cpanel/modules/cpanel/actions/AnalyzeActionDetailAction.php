<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeActionDetailAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();
    $view = $this->getView();

    $moduleName = $request->getParameter('module');
    $actionName = $request->getParameter('action');
    $from = $request->getParameter('from');
    $to = $request->getParameter('to');

    $hash = hash('md5', $moduleName . $actionName);
    $view->setAttribute('hash', $hash);

    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequests');

    $slowRequests = $actionRequestsDAO->findSlowRequests($moduleName, $actionName, $from, $to);
    $view->setAttribute('slowRequests', $slowRequests);

    // 遅いステートメントの抽出
    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');
    $slowStatements = $sqlRequestsDAO->findSlowStatementByAction($moduleName, $actionName, $from, $to);
    $view->setAttribute('slowStatements', $slowStatements);

    return Delta_View::SUCCESS;
  }
}
