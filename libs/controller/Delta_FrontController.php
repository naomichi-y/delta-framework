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
require DELTA_LIBS_DIR . '/controller/action/Delta_ActionStack.php';

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
   * アプリケーション設定。
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * {@link Delta_Router} オブジェクト。
   * @var Delta_Router
   */
  private $_router;

  /**
   * 経路が確定しているかどうか。
   * @var bool
   */
  private $_isRouteResoleved = FALSE;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_config = Delta_Config::getApplication();
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
    $container->getComponent('session')->initialize();

    $request = $container->getComponent('request');
    $request->initialize();

    $response = $container->getComponent('response');
    $response->initialize();

    $this->_router = $router = Delta_Router::getInstance();

    if ($router->connect()) {
      $container->getComponent('user')->initialize();
      $this->_isRouteResoleved = TRUE;

      $observer = $this->getObserver();
      $observer->dispatchEvent('postRouteConnect');

      ob_start();
      $this->forward($this->_router->getEntryActionName());
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
      $message = sprintf('Request path was not found. [%s]', $request->getURI());
      throw new Delta_RequestException($message);
    }
  }

  /**
   * 実行対象のアクションをコントローラに読み込みます。
   *
   * @param string $actionName 実行対象のアクション名。
   * @param string &$packageName アクションのパッケージ名が格納される。
   * @param string &$actionPath アクションのファイルパスが格納される。
   * @param string &$behaviorPath ビヘイビアのファイルパスが格納される。
   * @return bool アクションの読み込みに成功したかどうかを TRUE/FALSE で返します。
   * @throws RuntimeException リクエスト経路 (モジュール) が未確定の状態でメソッドがコールされた場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function loadAction($actionName, &$packageName, &$actionPath, &$behaviorPath)
  {
    static $loaded = array();

    if (!$this->_isRouteResoleved) {
      throw new RuntimeException('Routing is not established for HTTP request.');
    }

    if (in_array($actionName, $loaded)) {
      return TRUE;
    }

    $actionClassName = $actionName . 'Action';

    $modulePath = $this->_router->getEntryModulePath();
    $moduleName = $this->_router->getEntryModuleName();

    $searchBasePath = $modulePath . '/actions';
    $actionPath = $searchBasePath . DIRECTORY_SEPARATOR . $actionClassName . '.php';

    // アクションクラスの静的読み込み
    if (is_file($actionPath)) {
      $packageName = $moduleName . ':/';
      $behaviorPath = $this->getAppPathManager()->getModuleBehaviorsPath($moduleName, $actionName . '.yml');

    // アクションクラスの動的読み込み
    } else {
      $actionPath = Delta_ClassLoader::findPath($actionClassName, $searchBasePath);

      if ($actionPath !== FALSE) {
        $relativePath = substr($actionPath, strpos($actionPath, 'actions') + 7);
        $deepPath = str_replace("\\", '/', dirname(substr($relativePath, 1)));
        $packageName = $moduleName . ':' . $deepPath;

        $behaviorPath = sprintf('%s%s%s.yml', $deepPath, DIRECTORY_SEPARATOR, $actionName);
        $behaviorPath = $this->getAppPathManager()->getModuleBehaviorsPath($moduleName, $behaviorPath);

      } else {
        $packageName = FALSE;

        return FALSE;
      }
    }

    if (!$this->_router->isAllowRestrict($packageName)) {
      $packageName = FALSE;
      return FALSE;
    }

    require $actionPath;
    $loaded[] = $actionName;

    return TRUE;
  }

  /**
   * 指定したアクションにフォワードします。
   *
   * @param string $actionName 実行対象のアクション名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function forward($actionName, $validate = TRUE)
  {
    $packageName = NULL;
    $actionPath = NULL;
    $behaviorPath = NULL;

    if ($this->loadAction($actionName, $packageName, $actionPath, $behaviorPath)) {
      // リクエストされたアクション名が小文字から始まる場合、$actionName は小文字から始まる
      $actionClass = $actionName . 'Action';

      $action = new $actionClass($actionPath, $behaviorPath);
      $action->setPackageName($packageName);
      $action->setValidate($validate);

      Delta_ActionStack::getInstance()->addEntry($action);

      // ビヘイビアに定義されているコンポーネントをコンテナに設定
      // $actionName->getActionName() で参照する YAML の文字構成をアクション名と合わせる
      $config = Delta_Config::getBehavior($action->getActionName());

      // アクションロールの設定
      $action->setRoles($config->getArray('roles'));

      Delta_FilterManager::getInstance()->doFilters();

    } else {
      $this->forward($this->_router->getUnknownForward());
    }
  }
}
