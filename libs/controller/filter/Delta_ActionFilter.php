<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アクションの事前処理、実行、後処理を行います。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */

class Delta_ActionFilter extends Delta_Filter
{
  /**
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * @var Delta_Forward
   */
  private $_forward;

  /**
   * @since 2.0
   */
  public function __construct($filterId, Delta_ParameterHolder $holder)
  {
    parent::__construct($filterId, $holder);

    $this->_config = Delta_Config::getBehavior();

    $route = Delta_FrontController::getInstance()->getRequest()->getRoute();
    $this->_forward = $route->getForwardStack()->getLast();
  }

  /**
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $templatePath = sprintf('%s%s%s',
      Delta_StringUtils::convertSnakeCase($this->_forward->getControllerName()),
      DIRECTORY_SEPARATOR,
      Delta_StringUtils::convertSnakeCase($this->_forward->getActionName()));

    $view = $this->getView();
    $view->setTemplatePath($templatePath);

    $controller = $this->_forward->getController();
    $controller->initialize();

    if ($this->isSafety()) {
      // コンバータの実行
      $convertConfig = $this->_config->get('convert');

      if ($convertConfig) {
        $convertManager = new Delta_ConvertManager($convertConfig);
        $convertManager->execute();
      }

      $actionMethodName = $this->_forward->getActionName() . 'Action';

      if (method_exists($controller, $actionMethodName)) {
        call_user_func(array($controller, $actionMethodName));
      } else {
        $controller->unknownAction();
      }

    } else {
      // @todo 2.0
    }

    $response = $this->getResponse();

    if ($response->isWrite() && !$response->isCommitted() && !$view->isDisableOutput()) {
      $view->importHelpers();
      $view->execute();
    }

    $chain->filterChain();
  }

  /**
   * 現在のアクションがセーフティであるかどうかチェックします。
   * セーフティかであるかどうかの判定基準は下記の通りです。
   *
   * - ビヘイビアの 'safety.access' 属性値が 'secure'、かつ {@link Delta_HttpRequest::isSecure()} の戻り値が TRUE。
   * - ビヘイビアの 'safety.access' 属性値が 'unsecure'、かつ {@link Delta_HttpRequest::isSecure()} の戻り値が FALSE。
   * - ビヘイビアの 'safety.access' 属性値が 'none'。
   *
   * @return bool 現在のアクションがセーフティであれば TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function isSafety()
  {
    $safety = $this->_config->getString('safety.access', 'none');

    if ($safety === 'none') {
      return TRUE;
    }

    $secure = $this->getRequest()->isSecure();

    if ((($safety == 'secure') && !$secure) || ($safety == 'unsecure') && $secure) {
      return FALSE;
    }

    return TRUE;
  }
}
