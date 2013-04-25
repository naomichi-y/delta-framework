<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class LogoutAction extends Delta_Action
{
  public function execute()
  {
    $this->getUser()->clear();

    return Delta_View::SUCCESS;
  }
}
