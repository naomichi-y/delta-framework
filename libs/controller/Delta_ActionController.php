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

  public function dispatchAction()
  {
    $view = $this->getView();
    $fields = $view->bindForm()->getFields();
    $hasDispatch = FALSE;

    foreach ($fields as $fieldName => $fieldValue) {
      if (strpos($fieldName, 'dispatch') == 0) {
        $actionName = Delta_StringUtils::convertCamelCase(substr($fieldName, 8));
        $hasDispatch = TRUE;

        $this->forward($actionName);
        break;
      }
    }

    if (!$hasDispatch) {
      $this->dispatchUnknownAction();
    }

    $view->setDisableOutput();
  }

  public function dispatchUnknownAction()
  {
    $this->indexAction();
  }

  public function indexAction()
  {}

  public function unknownAction()
  {
    $this->getResponse()->sendError(404);
  }

  public function getRoles()
  {
    return array();
  }
}
