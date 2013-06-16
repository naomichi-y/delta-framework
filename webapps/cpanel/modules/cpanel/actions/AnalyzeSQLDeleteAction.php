<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class AnalyzeSQLDeleteAction extends Delta_Action
{
  public function execute()
  {
    $hash = $this->getRequest()->getParameter('hash');

    $sqlRequestsDAO = Delta_DAOFactory::create('Delta_SQLRequestsDAO');
    $sqlRequestsDAO->deleteByStatementHash($hash);

    return Delta_View::NONE;
  }
}
