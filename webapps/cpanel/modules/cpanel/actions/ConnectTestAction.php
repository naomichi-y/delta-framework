<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class ConnectTestAction extends Delta_Action
{
  public function execute()
  {
    $this->getResponse()->write('SUCCESS');

    return Delta_View::NONE;
  }
}
