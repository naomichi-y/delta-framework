<?php
/**
 * @package libs.filter
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class ControlPanelFilter extends Delta_Filter
{
  public function doFilter(Delta_FilterChain $chain)
  {
    if ($this->getRequest()->getParameter('check') !== NULL) {
      $this->getResponse()->write('SUCCESS');

    } else {
      $route = Delta_FrontController::getInstance()->getRequest()->getRoute();
      $currentAction = $route->getForwardStack()->getLast()->getAction()->getActionName();
      $unAuthorizedActions = array('LoginForm', 'Login', 'ConnectTest');

      if (in_array($currentAction, $unAuthorizedActions)) {
        $chain->filterChain();

      } else {
        if ($this->getUser()->isCurrentActionAuthenticated()) {
          $chain->filterChain();

        } else {
          $this->getMessages()->addError('ログインを行って下さい。');
          $this->getController()->forward('LoginForm');
        }
      }
    }
  }
}
