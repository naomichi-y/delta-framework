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
      $this->getView()->setDisableOutput();
    }
  }

  public function authAction()
  {
    $loginPassword = $this->getForm()->get('loginPassword');
    $validPassword = Delta_Config::getApplication()->get('cpanel.password');

    if (strcmp($loginPassword, $validPassword) != 0) {
      $this->getMessages()->addError('ログイン認証に失敗しました。');
      $this->getForm()->clear();

      $this->getView()->setViewPath('login/form');

    } else {
      $this->getUser()->addRole('cpanel');
    }
  }
}
