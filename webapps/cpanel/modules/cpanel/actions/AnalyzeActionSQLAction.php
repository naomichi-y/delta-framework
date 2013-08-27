<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeActionSQLAction extends Delta_Action
{
  public function execute()
  {
    $actionRequestId = $this->getRequest()->getQuery('actionRequestId');

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequests');
    $sqlRequests = $sqlRequestsDAO->findByActionRequestId($actionRequestId);

    $this->getView()->setAttribute('sqlRequests', $sqlRequests);

    return Delta_View::SUCCESS;
  }
}
