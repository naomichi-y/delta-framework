<?php
/**
 * @package modules.manager.actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class LoginFormAction extends Delta_Action
{
  public function execute()
  {
    // 既に認証済みであれば Home アクションにフォワード
    if ($this->getUser()->hasRole('manager')) {
      $this->getController()->forward('Home');

      return Delta_View::NONE;
    }

    return Delta_View::SUCCESS;
  }
}
