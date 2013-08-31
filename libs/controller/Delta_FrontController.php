<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/controller/action/Delta_Action.php';
require DELTA_LIBS_DIR . '/controller/router/Delta_RouteResolver.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_Filter.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_FilterManager.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_FilterChain.php';
require DELTA_LIBS_DIR . '/controller/filter/Delta_ActionFilter.php';
require DELTA_LIBS_DIR . '/controller/forward/Delta_Forward.php';
require DELTA_LIBS_DIR . '/controller/forward/Delta_ForwardStack.php';

/**
 * Web アプリケーションのためのフロントエンドコントローラ機能を提供します。
 *
<<<<<<< HEAD
 * このクラスは 'controller' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_WebApplication::getController()} からインスタンスを取得することができます。
 *
=======
>>>>>>> 1.2-stable
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 */

class Delta_FrontController extends Delta_Object
{
  /**
   * オブザーバオブジェクト。
   * @var Delta_KernelEventObserver
   */
  private $_observer;

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
   * {@link Delta_HttpRequest} オブジェクト。
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * {@link Delta_HttpResponse} オブジェクト。
   * @var Delta_HttpResponse
   */
  private $_response;

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
  private function __construct()
  {
    $this->_observer = new Delta_KernelEventObserver(Delta_BootLoader::BOOT_MODE_WEB);
    $this->_observer->initialize();

    $container = Delta_DIContainerFactory::getContainer();

    $this->_request = $container->getComponent('request');
    $this->_response = $container->getComponent('response');

    $this->_config = Delta_Config::getApplication();
    $this->_pathManager = Delta_AppPathManager::getInstance();
    $this->_resolver = new Delta_RouteResolver($this->_request);
  }

  /**
   * フロントコントローラのインスタンスオブジェクトを取得します。
   *
   * @return Delta_FrontController フロントコントローラのインスタンスオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;
<<<<<<< HEAD

    if ($instance === NULL) {
      $instance = new Delta_FrontController();
    }

    return $instance;
  }

  /**
   * オブザーバイブジェクトを取得します。
   *
   * @return Delta_KernelEventObserver オブザーバオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getObserver()
  {
    return $this->_observer;
  }

  /**
   * HTTP リクエストオブジェクトを取得します。
   *
   * @return Delta_HttpRequest HTTP リクエストオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRequest()
  {
    return $this->_request;
  }

  /**
   * HTTP レスポンスオブジェクトを取得します。
   *
   * @return Delta_HttpResponse HTTP レスポンスオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getResponse()
  {
    return $this->_response;
  }

  /**
   * ルータオブジェクトを取得します。
   *
   * @return Delta_RouteResolver ルータオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRouter()
  {
    return $this->_resolver;
  }

  /**
   * アクションを実行するための準備を行います。
   *
   * @throws Delta_RequestException リクエストパスが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatch()
  {

    // ルートの探索
    if ($route = $this->_resolver->connect()) {
      $this->_request->setRoute($route);
      $this->_route = $route;

=======

    if ($instance === NULL) {
      $instance = new Delta_FrontController();
    }

    return $instance;
  }

  /**
   * オブザーバイブジェクトを取得します。
   *
   * @return Delta_KernelEventObserver オブザーバオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getObserver()
  {
    return $this->_observer;
  }

  /**
   * HTTP リクエストオブジェクトを取得します。
   *
   * @return Delta_HttpRequest HTTP リクエストオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRequest()
  {
    return $this->_request;
  }

  /**
   * HTTP レスポンスオブジェクトを取得します。
   *
   * @return Delta_HttpResponse HTTP レスポンスオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getResponse()
  {
    return $this->_response;
  }

  /**
   * ルータオブジェクトを取得します。
   *
   * @return Delta_RouteResolver ルータオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRouter()
  {
    return $this->_resolver;
  }

  /**
   * アクションを実行するための準備を行います。
   *
   * @throws Delta_RequestException リクエストパスが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatch()
  {

    // ルートの探索
    if ($route = $this->_resolver->connect()) {
      $this->_request->setRoute($route);
      $this->_route = $route;

>>>>>>> 1.2-stable
      $this->_observer->dispatchEvent('postRouteConnect');

      ob_start();

      $this->forward($route->getActionName());
      $buffer = ob_get_contents();

      ob_end_clean();

      if (!$this->_response->isCommitted()) {
        $arguments = array(&$buffer);
        $this->_observer->dispatchEvent('preOutput', $arguments);

        $this->_response->write($buffer);
        $this->_response->flush();
      }

      $this->_observer->dispatchEvent('postProcess');

    // ルートが見つからない場合は 404 ページを出力
    } else {
      $this->_response->sendError(404);
    }
  }

  /**
   * アクションのフォワードを実行します。
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

      if ($actionClass) {
        $filter = new Delta_FilterManager();
        $filter->addFilter('actionFilter', array('class' => 'Delta_ActionFilter'));
        $filter->doFilters();

      } else {
        $this->_response->sendError(404);
      }

    // モジュールが存在しない場合
    } else {
      $this->_response->sendError(404);
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
