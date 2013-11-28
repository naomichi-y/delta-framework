<?php
/**
 * @package modules.demo_admin.controllers
 */
class IndexController extends Delta_ActionController
{
  public function isLoginRequired($actionName)
  {
    if ($actionName === 'home' || $actionName === 'logout') {
      return TRUE;
    }

    return FALSE;
  }

  public function indexAction()
  {
    $this->forward('loginForm');
  }

  public function loginFormAction()
  {
    if ($this->getUser()->isLogin()) {
      $this->forward('home');

    } else {
      $form = $this->createForm('Login');
      $form->set('login_password', '', TRUE);

      $this->getView()->setForm('form', $form);
    }
  }

  public function loginAction()
  {
    $form = $this->createForm('Login');

    if ($form->validate()) {
      $this->getView()->setDisableOutput();
      $this->getUser()->login();
      $this->getResponse()->sendRedirectAction('home');

    } else {
      $this->forward('loginForm');
    }
  }

  public function homeAction()
  {}

  public function logoutAction()
  {
    $this->getUser()->logout();
    $this->getResponse()->sendRedirectAction('loginForm');
    $this->getView()->setDisableOutput();
  }
}
