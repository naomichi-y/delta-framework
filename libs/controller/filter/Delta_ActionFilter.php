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

  public function __construct($filterId, Delta_ParameterHolder $holder)
  {
    parent::__construct($filterId, $holder);

    $this->_config = Delta_Config::getBehavior();
  }

  /**
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $route = Delta_FrontController::getInstance()->getRequest()->getRoute();
    $action = $route->getForwardStack()->getLast()->getAction();
    $action->initialize();

    if ($this->isSafety()) {
      // コンバータの実行
      $convertConfig = $this->_config->get('convert');

      if ($convertConfig) {
        $convertManager = new Delta_ConvertManager($convertConfig);
        $convertManager->execute();
      }

      // バリデータの実行
      $hasError = FALSE;

      if ($action->isValidate()) {
        $validateConfig = $this->_config->get('validate');

        if ($validateConfig) {
          $validateManager = new Delta_ValidateManager($validateConfig);

          // ビヘイビアに定義されたバリデータの結果に影響せず Delta_Action::validate() を実行
          if ($validateConfig->getBoolean('invokeMethod')) {
            if (!$validateManager->execute()) {
              $hasError = TRUE;
            }

            if (!$action->validate()) {
              $hasError = TRUE;
            }

          // ビヘイビアに定義されたバリデータをパスした場合のみ Delta_Action::validate() を実行
          } else if (!$validateManager->execute() || !$action->validate()) {
            $hasError = TRUE;
          }

        } else if (!$action->validate()) {
          $hasError = TRUE;
        }
      }

      if ($hasError) {
        $action->setValidateError(TRUE);
        $dispatchView = $action->validateErrorHandler();

      } else {
        $dispatchView = $action->execute();

        if (!$dispatchView) {
          $dispatchView = Delta_View::SUCCESS;
        }
      }

    } else {
      $dispatchView = $action->safetyErrorHandler();
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
          $route = Delta_FrontController::getInstance()->getRequest()->getRoute();
          $actionName = $route->getForwardStack()->getLast()->getAction()->getActionName();
          $template = Delta_StringUtils::convertSnakeCase($actionName);

          $view = $this->getView();
          $view->setTemplatePath($template);
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
