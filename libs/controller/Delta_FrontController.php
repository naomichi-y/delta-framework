<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/controller/filter/Delta_Filter.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_FilterManager.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_FilterChain.php';

require DELTA_LIBS_DIR . '/controller/action/Delta_Action.php';

require DELTA_LIBS_DIR . '/kernel/path/Delta_Forward.php';
require DELTA_LIBS_DIR . '/kernel/path/Delta_ForwardStack.php';
require DELTA_LIBS_DIR . '/kernel/router/Delta_RouteResolver.php';

/**
 * Web アプリケーションのためのフロントエンドコントローラ機能を提供します。
 *
 * このクラスは 'controller' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_DIController::getController()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 */

class Delta_FrontController extends Delta_Object
{
  /**
   * リクエストオブジェクト。
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * アプリケーション設定。
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   *{@link Delta_AppPathManager} オブジェクト。
   * @var Delta_AppPathManager
   */
  private $_pathManager;

  /**
   * {@link Delta_RouteResolver} オブジェクト。
   * @var Delta_RouteResolver
   */
  private $_resolver;

  /**
   * ルートオブジェクト。
   * @var Delta_Route
   */
  private $_route;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_config = Delta_Config::getApplication();
    $this->_pathManager = Delta_AppPathManager::getInstance();
  }

  /**
   * アクションを実行するための準備を行います。
   *
   * @throws Delta_RequestException リクエストパスが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatch()
  {
    $container = Delta_DIContainerFactory::getContainer();

    // リクエストコンポーネントの初期化
    $this->_request = $container->getComponent('request');
    $this->_request->initialize();

    // レスポンスコンポーネントの初期化
    $response = $container->getComponent('response');
    $response->initialize();

    // セッションコンポーネントの初期化
    // (Delta_DatabaseSessionHandler で DB エラーを検知した場合はレスポンスにエラーが出力されるため、先に request、response コンポーネントを初期化しておく)
    $session = $container->getComponent('session');
    $session->initialize();

    $this->_resolver = Delta_RouteResolver::getInstance();

    if ($route = $this->_resolver->connect()) {
      $this->_request->setRoute($route);
      $this->_route = $route;

      $container->getComponent('user')->initialize();

      $observer = $this->getObserver();
      $observer->dispatchEvent('postRouteConnect');

      ob_start();
      $this->forward($route->getActionName());
      $buffer = ob_get_contents();
      ob_end_clean();

      if (!$response->isCommitted()) {
        $arguments = array(&$buffer);
        $observer->dispatchEvent('preOutput', $arguments);

        $response->write($buffer);
        $response->flush();
      }

      $observer->dispatchEvent('postProcess');

    } else {
      $message = sprintf('Route can\'t be found. [%s]', $this->_request->getURI());
      throw new Delta_RequestException($message);
    }
  }

  /**
   * アクションのフォワードを行います。
   *
   * @param string $actionName フォワードするアクション名。
   * @throws Delta_ForwardException フォワードが失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function forward($actionName, $validate = TRUE)
  {
    $moduleName = $this->_route->getModuleName();

    // 実行モジュールを決定する
    $modulePath = $this->getModulePath($moduleName);

    if ($modulePath) {
      $actionClass = $this->loadAction($actionName, $moduleName, $modulePath, $validate);

      if (!$actionClass) {
        $key = sprintf('module.entries.%s.unknown', $moduleName);
        $actionName = $this->_config->get($key);
        $actionClass = $this->loadAction($actionName, $moduleName, $modulePath, $validate);

        if (!$actionClass) {
          $message = sprintf('\'%s\' action can\'t be found. [application.yml#%s]', $actionName, $key);
          throw new Delta_ForwardException($message);
        }
      }

      if ($actionClass) {
        Delta_FilterManager::getInstance()->doFilters();
      }

    // モジュールが存在しない場合
    } else {
      $unknownForward = FALSE;

      $unknownConfig = $this->_config->get('module.unknown');
      $moduleName = $unknownConfig->get('module');
      $actionName = $unknownConfig->get('action');

      if ($moduleName) {
        $modulePath = $this->_pathManager->getModulePath($moduleName);

        if (is_dir($modulePath)) {
          $unknownForward = new Delta_Forward($moduleName, $actionName);
        }
      }

      $modulePath = $this->getModulePath($moduleName);

      if ($modulePath) {
        $actionName = $unknownForward->getActionName();
        $actionClass = $this->loadAction($actionName, $moduleName, $modulePath, $validate);

        $this->_route->setModuleName($moduleName);
        $this->_route->setActionName($actionName);

        if ($actionClass) {
          Delta_FilterManager::getInstance()->doFilters();

        } else {
          $message = sprintf('Action can\'t be found. [%s]', $actionName);
          throw new Delta_ForwardException($message);
        }

      } else {
        $message = sprintf('Module directory is not found. [%s]', $moduleName);
        throw new Delta_ForwardException($message);
      }
    }
  }

  /**
   * モジュールのパスを取得します。
   *
   * @param string $moduleName モジュール名。
   * @return string モジュール名に対応するパスを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getModulePath($moduleName)
  {
    if ($moduleName === 'cpanel') {
      $modulePath = DELTA_ROOT_DIR . '/webapps/cpanel/modules/cpanel';
      $this->_pathManager->addModulePath('cpanel', $modulePath);

    } else {
      $modulePath = $this->_pathManager->getModulePath($moduleName);
    }

    if (!is_dir($modulePath)) {
      $modulePath = FALSE;
    }

    return $modulePath;
  }

  /**
   * アクションオブジェクトを取得します。
   *
   * @return Delta_Action アクションオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function loadAction($actionName, $moduleName, $modulePath, $validate)
  {
    static $loads = array();

    $action = FALSE;

    if (!isset($loads[$actionName])) {
      $actionClassName = $actionName . 'Action';
      $searchBasePath = $modulePath . '/actions';
      $actionPath = Delta_ClassLoader::findPath($actionClassName, $searchBasePath);

      if ($actionPath !== FALSE) {
        $actionRelativePath = substr($actionPath, strpos($actionPath, 'actions') + 7);
        $packagePath = dirname(substr($actionRelativePath, 1));

        if (DIRECTORY_SEPARATOR === "\\") {
          $packageName = str_replace("\\", '/', $padckagePath);
        } else {
          $packageName = $packagePath;
        }

        if ($packageName === '.') {
          $packageName = $moduleName . ':/';
          $behaviorRelativePath = $actionName . '.yml';

        } else {
          $packageName = $moduleName . ':' . $packageName;
          $behaviorRelativePath = sprintf('%s%s%s.yml', $packagePath, DIRECTORY_SEPARATOR, $actionName);
        }

        if ($this->_resolver->isAllowPackage($packageName)) {
          require $actionPath;

          // アクションクラスの生成
          $actionClass = $actionName . 'Action';
          $behaviorPath = $this->_pathManager->getModuleBehaviorsPath($moduleName, $behaviorRelativePath);

          $action = new $actionClass($actionPath, $behaviorPath);
          $action->setPackageName($packageName);
          $action->setValidate($validate);

          $forward = new Delta_Forward($moduleName, $actionName);
          $forward->setAction($action);
          $this->_route->getForwardStack()->add($forward);

          $config = Delta_Config::getBehavior($actionName);
          $action->setRoles($config->getArray('roles'));
        }

        $loads[$actionName] = $action;
      }

    } else {
      $action = $loads[$actionName];
    }

    return $action;
  }
}
