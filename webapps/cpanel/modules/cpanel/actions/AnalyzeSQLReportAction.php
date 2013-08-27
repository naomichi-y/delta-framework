<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSQLReportAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();

    $moduleName = $request->getQuery('target', NULL, TRUE);
    $from = $request->getQuery('from');
    $to = $request->getQuery('to');

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');
    $dailySummary = $sqlRequestsDAO->getDailySummary($moduleName, $from, $to);

    $this->getView()->setAttribute('dailySummary', $dailySummary);

    return Delta_View::SUCCESS;
  }
}
