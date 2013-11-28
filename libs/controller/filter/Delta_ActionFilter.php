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
   * @var bool
   */
  private static $_viewExecuted = FALSE;

  /**
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * @since 2.0
   */
  public function __construct($filterId, Delta_ParameterHolder $holder)
  {
    parent::__construct($filterId, $holder);

    // @todo 2.0 廃止
    $this->_config = Delta_Config::getBehavior();
  }

  /**
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $request = Delta_FrontController::getInstance()->getRequest();
    $route = $request->getRoute();
    $forward = $route->getForwardStack()->getLast();

    $viewPath = sprintf('%s%s%s',
      Delta_StringUtils::convertSnakeCase($forward->getControllerName()),
      DIRECTORY_SEPARATOR,
      Delta_StringUtils::convertSnakeCase($forward->getActionName()));

    $viewBasePath = $this->getAppPathManager()->getModuleViewsPath($route->getModuleName());

    $view = $this->getView();
    $view->setViewBasePath($viewBasePath);
    $view->setViewPath($viewPath);

    $actionName = $forward->getActionName();

    $controller = $forward->getController();
    $controller->initialize();

    // ログインが必要なアクションで認証チェックを行う
    $validLoginAccess = FALSE;

    if ($controller->isLoginRequired($actionName)) {
      // ログインが必要なアクションに未ログインでアクセスしていないか
      if ($request->getSession()->getUser()->isLogin()) {
        $validLoginAccess =  TRUE;
      }

    } else {
      $validLoginAccess = TRUE;
    }

    if ($validLoginAccess) {
    } else {
      $controller->forward($route->getModule()->getLoginFormPath());
    }

    if ($this->isSafety()) {
      $actionMethodName = $actionName . 'Action';

      if (method_exists($controller, $actionMethodName)) {
        call_user_func(array($controller, $actionMethodName));

      } else {
        $controller->unknownAction();
      }

    } else {
      // @todo 2.0
    }

    $response = $this->getResponse();

    if ($response->isWrite() && !$response->isCommitted() && !$view->isDisableOutput() && !self::$_viewExecuted) {
      $view->importHelpers();
      $view->execute();

      self::$_viewExecuted = TRUE;
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
