<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アクションコントローラの基底クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @since 2.0
 */
abstract class Delta_ActionController extends Delta_WebApplication
{
  public function initialize()
  {
  }

  public function forward($actionName, $controllerName = NULL)
  {
    Delta_FrontController::getInstance()->forward($actionName, $controllerName, TRUE);
  }

  public function unknownAction()
  {
    $this->getResponse()->sendError(404);
  }

  public function safetyErrorHandler()
  {
    return Delta_View::SAFETY_ERROR;
  }

  public function getRoles()
  {
    return array();
  }
}
