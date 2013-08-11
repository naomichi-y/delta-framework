<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class LoginAction extends Delta_Action
{
  public function execute()
  {
    $loginPassword = $this->getForm()->get('loginPassword');
    $validPassword = Delta_Config::getApplication()->get('cpanel.password');

    if (strcmp($loginPassword, $validPassword) == 0) {
      $this->getUser()->addRole('cpanel');

      return Delta_View::SUCCESS;
    }

    $this->getMessages()->addError('ログイン認証に失敗しました。');
    $this->getForm()->clear();

    return Delta_View::ERROR;
  }
}
