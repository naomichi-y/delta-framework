<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/kernel/router/Delta_Route.php';

/**
 * リクエストされた URI から処理すべきアクションを決定するルータクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 */

class Delta_RouteResolver extends Delta_Object
{
  /**
   * デフォルトアクション。
   * @var string
   */
  private $_defaultActionName = 'Index';

  /**
   * リクエストオブジェクト。
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * リクエスト URI。
   * @var string
   */
  private $_requestUri;

  /**
   * URI を分割したパート配列。
   * @var array
   */
  private $_uriSegments = array();

  /**
   * application.yml 設定リスト。
   * @var array
   */
  private $_applicationConfig;

  /**
   * routes.yml 設定リスト。
   * @var Delta_ParameterHolder
   */
  private $_routesConfig;

  /**
   * default モジュールが実行されたかどうか。
   * @var bool
   */
  private $_moduleUnknownForward = FALSE;

  /**
   * unknown アクションが実行されたかどうか。
   * @var bool
   */
  private $_actionUnknownForward = FALSE;

  /**
   * プライベートコンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {
    $this->_request = Delta_DIContainerFactory::getContainer()->getComponent('request');
    $this->_requestUri = $this->_request->getURI(FALSE);
    $this->_uriSegments = explode('/', substr($this->_requestUri, 1));

    $this->_applicationConfig = Delta_Config::getApplication();
    $this->_routesConfig = Delta_Config::getRoutes();
  }

  /**
   * Delta_RouteResolver のインスタンスを取得します。
   *
   * @return Delta_RouteResolver Delta_RouteResolver のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_RouteResolver();
    }

    return $instance;
  }

  /**
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAllowPackage($packageName)
  {
    $entryRouteName = $this->_request->getRoute()->getRouteName();

    foreach ($this->_routesConfig as $routeName => $attributes) {
      if (isset($attributes['packages'])) {
        $isValid = Delta_Action::isValidPackage($packageName, $attributes['packages']);

        // エントリルータが見つかった場合、パッケージが正当なものかどうかチェックする
        if ($routeName === $entryRouteName) {
          return $isValid;

        // 不正アクセスを検知
        } else if ($isValid) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * リクエスト URI から経路を検索し、目的のモジュール・アクションへの接続を試みます。
   *
   * @return Delta_Route 適切なルートが見つかった場合にルート情報を格納した Delta_Route オブジェクトを返します。
   *   ルートが見つからない場合は FALSE を返します。
   * @throws RuntimeException モジュールが起動できない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function connect()
  {
    $hasRoute = FALSE;
    $bindings = array();

    $pathManager = $this->getAppPathManager();
    $isSubdomainModule = $this->_applicationConfig->getBoolean('module.subdomain');

    foreach ($this->_routesConfig as $routeName => $routeConfig) {
      if (preg_match($routeConfig->get('regexp'), $this->_requestUri)) {
        $routeSegments = explode('/', substr($routeConfig->get('uri'), 1));
        $j = sizeof($routeSegments);

        for ($i = 0; $i < $j; $i++) {
          $segmentId = $routeSegments[$i];

          switch ($segmentId) {
            case ':module':
              $bindings['module'] = $this->_uriSegments[$i];
              break;

            case ':action':
              $bindings['action'] = $this->requestFileToAction($this->_uriSegments[$i]);
              break;

            default:
              if (substr($segmentId, 0, 1) === ':') {
                $segmentId = substr($segmentId, 1);
                $bindings[$segmentId] = $this->_uriSegments[$i];
              }

              break;
          }
        }

        $forwardConfig = $routeConfig->get('forward');

        // モジュール名がパスに含まれていない場合はモジュールを確定させる
        if (!isset($bindings['module'])) {
          // サブドメインからモジュール名を判別
          if ($isSubdomainModule) {
            $bindings['module'] = $this->parseSubdomainModuleName();

          } else {
            if ($forwardConfig) {
              $bindings['module'] = $forwardConfig->get('module');

            } else {
              $message = sprintf('Forward module is unknown. [%s]', $routeName);
              throw new Delta_ForwardException($message);
            }
          }
        }

        // アクション名がパスに含まれていない場合はアクションを確定させる
        if (!isset($bindings['action'])) {
          if ($forwardConfig) {
            $bindings['action'] = $forwardConfig->get('action', $this->_defaultActionName);

          } else {
            $key = sprintf('module.entries.%s.default', $bindings['module']);
            $defaultAction = $this->_applicationConfig->get($key);

            if ($defaultAction) {
              $bindings['action'] = $defaultAction;

            } else {
              $message = sprintf('Forward action is unknown. [%s]', $routeName);
              throw new Delta_ForwardException($message);
            }
          }
        }

        $hasRoute = TRUE;
        break;

      } // end if
    } // end foreach

    $route = FALSE;

    if ($hasRoute) {
      $accessConfig = $routeConfig->get('access');

      if ($accessConfig && !$this->isAllowNetwork($accessConfig)) {
        // アクセスが許可されていない場合はフォワード先を指定
        $denyForwardConfig = $accessConfig->get('denyForward');

        $bindings['module'] = $denyForwardConfig->get('module');
        $bindings['action'] = $denyForwardConfig->get('action');
      }

      $route = new Delta_Route($routeName, $bindings);
    } // end if

    return $route;
  }

  /**
   * URL に含まれるサブドメインをモジュール名として解析します。
   * 例えば foo.example.com の場合、foo がモジュール名となります。
   * このメソッドは application.yml の 'module.subdomain' 属性が TRUE の場合に connect() メソッドからコールされます。
   *
   * @return string サブドメインに含まれるモジュール名を返します。モジュール名が含まれない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseSubdomainModuleName()
  {
    $result = FALSE;

    // FQDN に含まれるサブドメインパートを取得
    if (($pos = strpos($_SERVER['SERVER_NAME'], '.')) !== FALSE) {
      $moduleNames = Delta_CoreUtils::getModuleNames();
      $moduleName = substr($_SERVER['SERVER_NAME'], 0, $pos);

      // サブドメイン名にマッチするモジュールが定義されているかチェック
      if (in_array($moduleName, $moduleNames)) {
        $result = $moduleName;
      }
    }

    return $result;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parse2(Delta_ParameterHolder &$attributes,
    &$bindings = array(),
    &$pathInfo = array())
  {
    if (!preg_match($attributes['regexp'], $requestUri)) {
      return FALSE;
    }

    $bindings = array();
    $pathInfo = array();

    $routeSegments = explode('/', $attributes['uri']);
    $j = sizeof($routeSegments);

    for ($i = 1; $i < $j; $i++) {
      $part = $routeSegments[$i];

      if (isset($this->_uriSegments[$i])) {
        $value = $this->_uriSegments[$i];

      // When $_SERVER['REQUEST_URI'] does not satisfy regexp pattern
      } else {
        $value = NULL;
      }

      // ホルダパスの置換
      if (substr($part, 0, 1) == ':') {
        $part = substr($part, 1);

        if (isset($attributes['patterns'][$part])) {
          $pattern = '/' . $attributes['patterns'][$part] . '/';

          if (!preg_match($pattern, $value)) {
            return FALSE;
          }

          $name = urldecode($part);
          $pathInfo[$name] = urldecode($value);

        } else if (isset($attributes['parameters'][$part])) {
          $name = urldecode($part);
          $pathInfo[$name] = urldecode($attributes['parameters'][$part]);

        } else if ($part == 'module') {
          if (!strlen($value)) {
            return FALSE;
          }

          $attributes->set('forward.module', $value);

        } else if ($part == 'action') {
          $length = strlen($this->_applicationConfig['action']['extension']);

          if ($length == 0 && strpos($value, '.') === FALSE) {
            $attributes->set('forward.action', $value);
          } else if ($length && substr($value, - $length) === $this->_applicationConfig['action']['extension']) {
            $attributes->set('forward.action', $value);
          }

        // When ':[\w-]+' ($regexp) pattern is undefined
        // (ex) $_SERVER['REQUEST_URI'] is '/0123' and regexp pattern ':/foo' (undefined pattern)
        // '$request->getPathInfo('foo');' return '0123'
        } else {
          $name = urldecode($part);
          $pathInfo[$name] = urldecode($value);
        }

        $bindings[$routeSegments[$i]] = $value;

      } else if ($part == '*') {
        $l = sizeof($this->_uriSegments);

        for ($k = $i; $k < $l; $k = $k + 2) {
          $m = $k + 1;

          if (strlen($this->_uriSegments[$k])) {
            $name = urldecode($this->_uriSegments[$k]);

            if (isset($this->_uriSegments[$m])) {
              if (strpos($name, '[') !== FALSE) {
                $name = str_replace('[', '.', $name);
                $name = str_replace(']', '', $name);
              }

              $result = Delta_ArrayUtils::build($name, urldecode($this->_uriSegments[$m]));

            } else {
              $result = array($name => NULL);
            }

            $pathInfo = Delta_ArrayUtils::mergeRecursive($pathInfo, $result);
          }
        }

      // (ex) Request pattern ('/foo/bar') is different regexp pattern ('/foo/baz')
      } else if ($part != $value) {
        return FALSE;
      }
    }

    if ($i == $j) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * クライアントからリクエストされたパスを元にアクション名を生成します。
   * リクエストパスが '/manager/hello.do' の場合、デフォルトで返される値は 'Hello' になります。
   *
   * @param string $requestUri リクエストされたパス。
   * @return string パスを元に生成したアクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function requestFileToAction($requestUri)
  {
    if (($pos = strpos($requestUri, '.')) !== FALSE) {
      $actionName = ucfirst(substr($requestUri, 0, $pos));
    } else {
      $actionName = ucfirst($requestUri);
    }

    return $actionName;
  }

  /**
   * アクション名を元に URI に追加されるファイル名を生成します。
   * アクション名が 'Hello' の場合、デフォルトで返される値は 'hello.do' になります。
   *
   * @param string $actionName アクション名。
   * @return string URI に追加されるファイル名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function actionToRequestFile($actionName)
  {
    $requestFile = Delta_StringUtils::convertCamelCase($actionName)
      .$this->_applicationConfig->get('action.extension');

    return $requestFile;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function isAllowNetwork(Delta_ParameterHolder $accessConfig)
  {
    $allows = $accessConfig->get('allows');
    $result = FALSE;

    if ($allows) {
      $remoteAddress = $this->_request->getRemoteAddress();

      foreach ($allows as $allow) {
        if (Delta_NetworkUtils::hasContainNetwork($allow, $remoteAddress)) {
          $result = TRUE;
          break;
        }
      }

    } else {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * ルーティング名からパスを生成します。
   *
   * @param string $routeName ルーティング名。
   * @param array $bindings 'uri' 属性にバインドする変数。array(':module' => 'entry', ':action' => 'Start') のように複数指定可能。
   * @param bool $encode TRUE を指定した場合は queries の URL エンコードを行います。
   * @param bool $absolute {buildRequestPath()} メソッドを参照。
   * @param bool $secure {buildRequestPath()} メソッドを参照。
   * @return string 生成したアクションのパスを返します。
   * @throws Delta_ConfigurationException 指定したルーティング名が存在しない、または必要なバインド変数が holders に含まれていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildBaseRoutingPath($routeName,
    array $bindings = array(),
    $encode = TRUE,
    $absolute = FALSE,
    $secure = NULL)
  {
    if (isset($this->_routesConfig[$routeName]['uri'])) {
      $segments = explode('/', $this->_routesConfig[$routeName]['uri']);
      $j = sizeof($segments);

      $path = NULL;
      $moduleName = $this->_request->getRoute()->getModuleName();

      for ($i = 1; $i < $j; $i++) {
        if (preg_match('/^:(\w+)$/', $segments[$i], $matches)) {
          $path .= '/';

          if ($matches[1] == 'module') {
            $moduleName = Delta_ArrayUtils::find($bindings, 'module', $moduleName);
            $path .= $moduleName;

          } else if ($matches[1] == 'action') {
            $actionName = Delta_ArrayUtils::find($bindings, 'action');

            if ($actionName !== NULL) {
              $path .= $this->actionToRequestFile($actionName);
            }

          } else if (isset($bindings[$matches[1]])) {
            $path .= $bindings[$matches[1]];
          }

        } else {
          $path .= '/' . $segments[$i];
        }
      }

      if (substr($path, -2) == '/*') {
        $path = substr($path, 0, -2);
      }

      if ($absolute) {
        if ($secure === NULL) {
          $scheme = $this->_request->getScheme() . '://';
        } else if ($secure === TRUE) {
          $scheme = 'https://';
        } else {
          $scheme = 'http://';
        }

        $path = $scheme . $this->_request->getHost() . $path;
      }

      return $path;
    }

    $message = sprintf('Route does not exist. [%s]', $routeName);
    throw new Delta_ConfigurationException($message);
  }

  /**
   * 指定されたパスを Web からアクセス可能なリクエストパスの形式に変換します。
   *
   * @param mixed $path 遷移先のアクション名。
   *   指定したアクション名を元にリクエストパスを生成する。
   *   path に指定した名前がアクション名と見なされない場合はアクションパスへの変換は行わない。
   *   path は配列形式でパラメータ (PATH_INFO) を追加することも可能。
   *     - router: リンクパスを生成する際に使用するルータ名。未指定時は現在有効なルータが対象となる。
   *     - module: 遷移先のモジュール名。未指定時は現在有効なモジュール名が対象となる。
   *     - action: 遷移先のアクション名。未指定時は現在有効なアクション名が対象となる。
   *
   *   上記以外に追加したパラメータはそのまま PATH_INFO パラメータとして URI に使用される。
   *   <code>
   *   // '/{module}/index.do/greeting/hello' のような URI を生成
   *   array('action' => 'Index', 'greeting' => 'hello');
   *
   *   // アンカーポイントの追加
   *   // '/{module}/index.do#top' のような URI を生成
   *   array('action' => 'Index', '#' => 'top');
   *   </code>
   *   尚、path が未指定の場合は現在実行しているアクション名がそのままパスとして使用される。
   * @param array $queryData path に追加する GET パラメータ。
   * @param bool $absolute 絶対パスを返す場合は TRUE、相対パスを返す場合は FALSE を指定。
   * @secure bool $secure URL スキームの形式。
   *   o TRUE: 'https' 形式に変換
   *   o FALSE: 'http' 形式に変換
   *   o NULL: 変換を行わない
   * @return string URL エンコードされたリクエストパスを返します。
   * @see Delta_RouteResolver::setIgnoreAppendGUID()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestPath($path = NULL, array $queryData = array(), $absolute = FALSE, $secure = NULL)
  {
    $entry = array();
    $anchorPoint = NULL;

    $route = $this->_request->getRoute();
    $bindings = $route->getBindings();

    if (is_array($path)) {
      if (isset($path['route'])) {
        $entry['route'] = $path['route'];
        unset($path['route']);

      } else {
        $entry['route'] = $route->getRouteName();
      }

      if (isset($path['module'])) {
        $entry['module'] = $path['module'];
        unset($path['module']);

      } else {
        $entry['module'] = $route->getModuleName();
      }

      if (isset($path['action'])) {
        $entry['action'] = $path['action'];
        unset($path['action']);

      } else {
        if ($route->getModuleName() === $entry['module']) {
          $route = Delta_DIContainerFactory::getContainer()->getComponent('request')->getRoute();
          $entry['action'] = $route->getForwardStack()->getLast()->getAction()->getActionName();

        } else {
          $search = sprintf('module.entries.%s.default', $entry['module']);
          $entry['action'] = $this->_applicationConfig->getString($search);
        }
      }

      foreach ($path as $key => $value) {
        $bindings[$key] = $value;
      }

      if (isset($path['#'])) {
        $anchorPoint = '#' . $path['#'];
        unset($path['#']);
      }

    } else {
      if ($route) {
        $entry['route'] = $route->getRouteName();
        $entry['module'] = $route->getModuleName();
      }

      if ($path === NULL) {
        $entry['action'] = $route->getForwardStack()->getLast()->getActionName();

      // アクション名が指定された
      } else if (ctype_upper(substr($path, 0, 1))) {
        $entry['action'] = $path;

      // アクション名と見なされない文字列が指定された
      } else {
        return $path;
      }
    }

    $bindings['module'] = $entry['module'];
    $bindings['action'] = $entry['action'];

    $path = $this->buildBaseRoutingPath($entry['route'],
      $bindings,
      TRUE,
      $absolute,
      $secure);

    $path .= $anchorPoint;

    if (sizeof($queryData)) {
      $path = sprintf('%s?%s',
        $path,
        http_build_query($queryData, '', '&amp;'));
    }

    return $path;
  }
}
