<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class LoginFormAction extends Delta_Action
{
  public function execute()
  {
    if ($this->getUser()->hasRole('cpanel')) {
      $this->getController()->forward('Home');

      return Delta_View::NONE;
    }

    return Delta_View::SUCCESS;
  }
}
