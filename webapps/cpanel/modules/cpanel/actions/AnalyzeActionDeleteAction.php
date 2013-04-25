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
    $moduleName = $request->getParameter('module');
    $actionName = $request->getParameter('action');

    $actionRequestsDAO = Delta_DAOFactory::create('Delta_ActionRequestsDAO');
    $actionRequestsDAO->delete($moduleName, $actionName);

    return Delta_View::NONE;
  }
}
