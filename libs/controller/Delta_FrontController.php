<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/controller/Delta_ActionController.php';
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

      $this->_observer->dispatchEvent('postRouteConnect');

      ob_start();

      $this->forward($route->getActionName(), $route->getControllerName());
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
   * @param string $actionName フォワード先のアクション名。
   * @param string $controllerName フォワード先のコントローラ名。
   * @param bool $throw コントローラが見つからない場合に例外をスローする場合は TRUE、404 ページを出力する場合は FALSE を指定。
   * @throws Delta_ForwardException コントローラが見つからない場合に発生。(throw が TRUE の場合のみ)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function forward($actionName, $controllerName = NULL, $throw = FALSE)
  {
    $moduleName = $this->_route->getModuleName();

    if ($controllerName === NULL) {
      $controllerName = $this->_route->getControllerName();
    }

    if ($this->loadController($moduleName, $controllerName)) {
      $forward = new Delta_Forward($moduleName, $controllerName, $actionName);
      $this->_route->getForwardStack()->add($forward);

      $filter = new Delta_FilterManager();
      $filter->addFilter('actionFilter', array('class' => 'Delta_ActionFilter'));
      $filter->doFilters();

    } else {
      if ($throw) {
        $message = sprintf('Controller class can\'t be found. [%s]', $controllerName);
        throw new Delta_ForwardException($message);

      } else {
        $this->_response->sendError(404);
      }
    }
  }

  /**
   * @since 2.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function loadController($moduleName, $controllerName)
  {
    static $loadedControllers = array();

    $result = FALSE;

    if (!in_array($controllerName, $loadedControllers)) {
      // モジュールパスの取得
      if ($moduleName === 'cpanel') {
        $modulePath = DELTA_ROOT_DIR . '/webapps/cpanel/modules/cpanel';
        $this->_pathManager->addModulePath('cpanel', $modulePath);

      } else {
        $modulePath = $this->_pathManager->getModulePath($moduleName);
      }

      $formsPath = sprintf('%s%sforms', $modulePath, DIRECTORY_SEPARATOR);
      Delta_ClassLoader::addSearchPath($formsPath);

      $controllerClassName = $controllerName . 'Controller';
      $controllerClassPath = sprintf('%s%scontrollers%s%s.php',
        $modulePath,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        $controllerClassName);

      if (is_file($controllerClassPath)) {
        require $controllerClassPath;

        /** @todo 2.0
        $config = Delta_Config::getBehavior($controllerName);
        $action->setRoles($config->getArray('roles'));
        */

        $loadedControllers[] = $controllerName;
        $result = TRUE;
      }

    } else {
      $result = TRUE;
    }

    return $result;
  }
}
