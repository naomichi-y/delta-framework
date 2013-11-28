<?php
/**
 * @package controllers
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
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
    if ($this->getUser()->isLogin()) {
      $this->forward('home');

    } else {
      $form = $this->createForm('Login');
      $this->getView()->setForm('form', $form);
    }
  }

  public function testAction()
  {
    $this->getResponse()->write('SUCCESS');
    $this->getView()->setDisableOutput();
  }

  public function loginAction()
  {
    $form = $this->createForm('Login');

    if ($form->validate()) {
      $this->getUser()->login();
      $this->getResponse()->sendRedirectAction('home');

    } else {
      $this->forward('index');
    }
  }

  public function homeAction()
  {}

  public function logoutAction()
  {
    $this->getUser()->logout();
    $this->getResponse()->sendRedirectAction('index');
  }
}
