<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeActionDeleteAction extends Delta_Action
{
  public function execute()
  {
    $request = $this->getRequest();
    $moduleName = $request->getQuery('module');
    $actionName = $request->getQuery('action');

    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequestsDAO');
    $actionRequestsDAO->deleteByModuleAndAction($moduleName, $actionName);

    return Delta_View::NONE;
  }
}
