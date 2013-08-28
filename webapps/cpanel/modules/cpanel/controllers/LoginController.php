<?php
/**
 * @package controllers
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class LoginController extends Delta_ActionController
{
  public function formAction()
  {
    if ($this->getUser()->hasRole('cpanel')) {
      $this->getController()->forward('Home');

      return Delta_View::NONE;
    }

    return Delta_View::SUCCESS;
  }

  public function authAction()
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
