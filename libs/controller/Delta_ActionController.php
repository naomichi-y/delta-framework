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

  public function isLoginRequired($actionName)
  {
    return FALSE;
  }

  public function createForm($formName = NULL)
  {
    static $instances = array();

    if ($formName === NULL) {
      $formName = 'Delta_';
    }

    if (!isset($instances[$formName])) {
      if ($formName === NULL) {
        $instance = new Delta_Form();

      } else {
        $formClassName = $formName . 'Form';
        $instance = new $formClassName;
      }

      $instances[$formName] = $instance;
    }

    return $instances[$formName];
  }

  public function forward($forward)
  {
    $actionName = NULL;
    $controllerName = NULL;

    // 文字列に '/' が含まれる場合は '{コントローラ名}/{アクション名}' 形式と識別
    if (($pos = strpos($forward, '/')) !== FALSE) {
      $controllerName = substr($forward, 0, $pos);
      $actionName = substr($forward, $pos + 1);

    } else {
      $actionName = $forward;
    }

    Delta_FrontController::getInstance()->forward($actionName, $controllerName, TRUE);
  }

  public function dispatchAction()
  {
    $request = $this->getRequest();

    if ($request->isPost()) {
      $data = $request->getPost();
    } else {
      $data = $request->getQuery();
    }

    $hasDispatch = FALSE;

    foreach ($data as $fieldName => $fieldValue) {
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

    $this->getView()->setDisableOutput();
  }

  public function dispatchUnknownAction()
  {
    $message = sprintf('Forward destination is unknown. Please implement %s::dispatchUnknownAction() method.',
      get_class($this));
    throw new Delta_ForwardException($message);
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
