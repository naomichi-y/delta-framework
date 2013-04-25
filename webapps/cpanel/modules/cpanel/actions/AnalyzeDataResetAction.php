<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeDataResetAction extends Delta_Action
{
  public function execute()
  {
    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');
    $sqlRequestsDAO->truncate();

    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequestsDAO');
    $actionRequestsDAO->truncate();

    echo '1';

    return Delta_View::NONE;
  }
}
