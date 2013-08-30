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
    $controller = $this->_forward->getController();
    $controller->initialize();

    if ($this->isSafety()) {
      // コンバータの実行
      $convertConfig = $this->_config->get('convert');

      if ($convertConfig) {
        $convertManager = new Delta_ConvertManager($convertConfig);
        $convertManager->execute();
      }

      // バリデータの実行
      $hasError = FALSE;

      $validateConfig = $this->_config->get('validate');

      if ($validateConfig) {
        $validateManager = new Delta_ValidateManager3($validateConfig);

        // ビヘイビアに定義されたバリデータの結果に影響せず Delta_Action::validate() を実行
        if ($validateConfig->getBoolean('invokeMethod')) {
          if (!$validateManager->execute()) {
            $hasError = TRUE;
          }

        // ビヘイビアに定義されたバリデータをパスした場合のみ Delta_Action::validate() を実行
        } else if (!$validateManager->execute()) {
          $hasError = TRUE;
        }
      }

      $dispatchView = NULL;

      if ($hasError) {
        // @todo 2.0
        //$action->setValidateError(TRUE);
        //$dispatchView = $action->validateErrorHandler();
        $dispatchView = Delta_View::SUCCESS;

      } else {
        $actionMethodName = $this->_forward->getActionName() . 'Action';

        if (method_exists($controller, $actionMethodName)) {
          $dispatchView = call_user_func(array($controller, $actionMethodName));
        } else {
          $dispatchView = $controller->unknownAction();
        }

        if (!$dispatchView) {
          $dispatchView = Delta_View::SUCCESS;
        }
      }

    } else {
      $dispatchView = $controller->safetyErrorHandler();
    }

    $this->dispatchView($dispatchView);
    $chain->filterChain();
  }

  /**
   * @param string $dispatchView
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function dispatchView($dispatchView)
  {
    $response = $this->getResponse();

    if ($dispatchView !== Delta_View::NONE && $response->isWrite() && !$response->isCommitted()) {
      // @todo 2.0 {action.view} を参照するよう変更
      $viewConfig = $this->_config->get('view');
      $hasDispatch = FALSE;

      // ビヘイビアに 'view' 属性が定義されているか
      if ($viewConfig) {
        $dispatchConfig = $viewConfig->get($dispatchView);

        // ビヘイビアにマッピングするビューが定義されている
        if (is_string($dispatchConfig)) {
          $hasDispatch = TRUE;

          $view = $this->getView();
          $view->setTemplatePath($dispatchConfig);
          $view->importHelpers();
          $view->execute();

        // ビヘイビアにマッピングするフォワードアクション、またはリダイレクト URI が指定されている
        } else if ($dispatchConfig) {
          $forwardConfig = $dispatchConfig->getString('forward');

          // フォワード指定がある場合
          if ($forwardConfig) {
            $hasDispatch = TRUE;

            $validate = $dispatchConfig->getBoolean('validate', TRUE);
            $this->getController()->forward($forwardConfig, $validate);

          // リダイレクト指定がある場合
          } else {
            $redirectConfig = $dispatchConfig->getString('redirect');

            if ($redirectConfig) {
              $hasDispatch = TRUE;
              $response->sendRedirectAction($redirectConfig);
            }
          }
        }
      }

      if (!$hasDispatch) {
        if ($dispatchView === Delta_View::SUCCESS) {
          $controllerName = Delta_StringUtils::convertSnakeCase($this->_forward->getControllerName());
          $actionName = Delta_StringUtils::convertSnakeCase($this->_forward->getActionName());

          $templatePath = $controllerName . DIRECTORY_SEPARATOR . $actionName;

          $view = $this->getView();
          $view->setTemplatePath($templatePath);
          $view->importHelpers();
          $view->execute();

        } else {
          $message = sprintf('Specified view does not exist. [%s]', $dispatchView);
          throw new Delta_ForwardException($message);
        }
      }
    }
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
