<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path.router
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * リクエストされた URI から処理すべきアクションを決定するルータクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path.router
 */

class Delta_Router extends Delta_Object
{
  /**
   * リクエストオブジェクト。
   * @var Delta_HttpRequest
   */
  private $_request;

  /**
   * 接続済みルータ名。
   * @var string
   */
  private $_entryRouterName;

  /**
   * エントリモジュール名。
   * @var string
   */
  private $_entryModuleName;

  /**
   * エントリモジュールディレクトリ。
   * @var string
   */
  private $_entryModulePath;

  /**
   * エントリアクション名。
   * @var string
   */
  private $_entryActionName;

  /**
   * PATH_INFO 形式のパラメータリスト。
   * @var array
   */
  private $_holders = array();

  /**
   * application.yml 設定リスト。
   * @var array
   */
  private $_applicationConfig;

  /**
   * アクションのリクエストパスが camelCaps 形式かどうか。
   * @var bool
   */
  private $_isCamelCaps = TRUE;

  /**
   * routes.yml 設定リスト。
   * @var Delta_ParameterHolder
   */
  private $_routerConfig;

  /**
   * リクエストパスを '/' で分割した配列。
   * @var array
   */
  private $_pathList = array();

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
   * クエリ (PATH_INFO) の値セパレータ。
   * @var string
   */
  private $_valueSeparator = '/';

  /**
   * クエリ (PATH_INFO) の追加セパレータ。
   * @var string
   */
  private $_appendSeparator = '/';

  /**
   * プライベートコンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {
    $this->_request = Delta_DIContainerFactory::getContainer()->getComponent('request');
    $this->_routerConfig = Delta_Config::getRoutes();
    $this->_applicationConfig = Delta_Config::getApplication();

    // Delta_DIContainerFactory::create() 実行時に内部エラーが起こると
    // 当コンストラクタが起動するが、この時点ではコンテナが未生成のため、コンポーネントの呼び出しを行ってはいけない
  }

  /**
   * Delta_Router のインスタンスを取得します。
   *
   * @return Delta_Router Delta_Router のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_Router();
    }

    return $instance;
  }

  /**
   * ルータにより決定された経路名を取得します。
   *
   * @return string ルータにより決定された経路名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEntryRouterName()
  {
    return $this->_entryRouterName;
  }

  /**
   * ルータにエントリモジュールを登録します。
   *
   * @param string $entryModuleName エントリモジュール名。
   * @param string $entryModulePath モジュールのディレクトリパス。
   *   未指定時は 'APP_ROOT_DIR/modules/{$entryModuleName}' が設定されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function entryModuleRegister($entryModuleName, $entryModulePath = NULL)
  {
    if ($entryModulePath === NULL) {
      $path = $this->getAppPathManager()->getModulePath($entryModuleName);

    } else {
      $this->getAppPathManager()->addModulePath($entryModuleName, $entryModulePath);
      $path = $entryModulePath;
    }

    $this->_entryModulePath = $path;
    $this->_entryModuleName = $entryModuleName;
  }

  /**
   * ルータに設定されているエントリモジュール名を取得します。
   *
   * @return string エントリモジュール名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEntryModuleName()
  {
    return $this->_entryModuleName;
  }

  /**
   * エントリモジュールのディレクトリパスを取得します。
   *
   * @return string エントリモジュールのディレクトリパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEntryModulePath()
  {
    return $this->_entryModulePath;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAllowRestrict($packageName)
  {
    foreach ($this->_routerConfig as $routerName => $attributes) {
      if (isset($attributes['packages'])) {
        $isValid = Delta_Action::isValidPackage($packageName, $attributes['packages']);

        // エントリルータが見つかった場合、パッケージが正当なものかどうかチェックする
        if ($routerName === $this->_entryRouterName) {
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
   * ルータに設定されているエントリアクション名を取得します。
   *
   * @return string エントリアクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEntryActionName()
  {
    return $this->_entryActionName;
  }

  /**
   * リクエスト URI から経路を検索し、目的のモジュール・アクションへの接続を試みます。
   *
   * @return bool 接続に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws RuntimeException モジュールが起動できない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function connect()
  {
    if ($this->_applicationConfig['action']['path'] !== 'camelCaps') {
      $this->_isCamelCaps = FALSE;
    }

    if ($this->resolver()) {
      // アクション名が URI に含まれていない場合
      if (Delta_StringUtils::nullOrEmpty($this->_entryActionName)) {
        if (is_dir($this->_entryModulePath)) {
          $path = sprintf('module.entries.%s.default', $this->_entryModuleName);
          $this->_entryActionName = $this->_applicationConfig->getString($path);

        } else {
          $this->entryModuleRegister($this->_applicationConfig->getString('module.unknown.module'));
          $this->_entryActionName = $this->_applicationConfig->getString('module.unknown.action');
          $this->_moduleUnknownForward = TRUE;
        }

      } else {
        $search = 'module.entries.' . $this->_entryModuleName . '.enable';

        if (!$this->_applicationConfig->getBoolean($search)) {
          $message = sprintf('Module does not start. [%s]', $this->_entryModuleName);
          throw new RuntimeException($message);
        }
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUnknownForward()
  {
    $config = Delta_Config::getApplication();

    if (!is_dir($this->_entryModulePath)) {
      if ($this->_moduleUnknownForward) {
        $message = sprintf('Unknown module directory not found. [%s]', $this->_entryModuleName);
        throw new RuntimeException($message);

      } else {
        $this->entryModuleRegister($config->getString('module.unknown.module'));
        $this->_entryActionName = $config->getString('module.unknown.action');
        $this->_moduleUnknownForward = TRUE;

        return $this->_entryActionName;
      }

    } else {
      if ($this->_actionUnknownForward) {
        $message = sprintf('Module default action or unknown action can not be found. [%s]', $this->_entryActionName);
        throw new RuntimeException($message);
      }

      $this->_actionUnknownForward = TRUE;
      $path = sprintf('module.entries.%s.unknown', $this->_entryModuleName);

      return $config->getString($path);
    }
  }

  /**
   * URL に含まれるサブドメインをモジュール名として解析します。
   * 例えば foo.example.com の場合、foo がモジュール名となります。
   * このメソッドは application.yml の 'module.subdomain' 属性が TRUE の場合に resolver() メソッドからコールされます。
   *
   * @return string サブドメインに含まれるモジュール名を返します。モジュール名が含まれない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseSubdomainModule()
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
  private function parse($requestPath,
    array $pathList,
    Delta_ParameterHolder &$attributes,
    &$holders = array(),
    &$pathInfo = array())
  {
    if (!preg_match($attributes['regexp'], $requestPath)) {
      return FALSE;
    }

    $holders = array();
    $pathInfo = $attributes->getArray('parameters');

    $queryData = explode('/', $attributes['uri']);
    $j = sizeof($queryData);

    for ($i = 1; $i < $j; $i++) {
      $query = $queryData[$i];

      if (isset($pathList[$i])) {
        $value = $pathList[$i];

      // When $_SERVER['REQUEST_URI'] does not satisfy regexp pattern
      } else {
        $value = NULL;
      }

      // ホルダパスの置換
      if (substr($query, 0, 1) == ':') {
        $query = substr($query, 1);

        if (isset($attributes['patterns'][$query])) {
          $pattern = '/' . $attributes['patterns'][$query] . '/';

          if (!preg_match($pattern, $value)) {
            return FALSE;
          }

          $name = urldecode($query);
          $pathInfo[$name] = urldecode($value);

        } else if (isset($attributes['parameters'][$query])) {
          $name = urldecode($query);
          $pathInfo[$name] = urldecode($attributes['parameters'][$query]);

        } else if ($query == 'module') {
          if (!strlen($value)) {
            return FALSE;
          }

          $attributes->set('forward.module', $value);

        } else if ($query == 'action') {
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
          $name = urldecode($query);
          $pathInfo[$name] = urldecode($value);
        }

        $holders[$queryData[$i]] = $value;

      } else if ($query == '*') {
        $l = sizeof($pathList);

        for ($k = $i; $k < $l; $k = $k + 2) {
          $m = $k + 1;

          if (strlen($pathList[$k])) {
            $name = urldecode($pathList[$k]);

            if (isset($pathList[$m])) {
              if (strpos($name, '[') !== FALSE) {
                $name = str_replace('[', '.', $name);
                $name = str_replace(']', '', $name);
              }

              $result = Delta_ArrayUtils::build($name, urldecode($pathList[$m]));

            } else {
              $result = array($name => NULL);
            }

            $pathInfo = Delta_ArrayUtils::mergeRecursive($pathInfo, $result);
          }
        }

      // (ex) Request pattern ('/foo/bar') is different regexp pattern ('/foo/baz')
      } else if ($query != $value) {
        return FALSE;
      }
    }

    if ($i == $j) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * リクエスト URI からルーティング先を解決します。
   *
   * @return 経路が見つかった場合は TRUE、見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function resolver()
  {
    $requestPath = $this->_request->getURI(FALSE);
    $pathList = explode('/', $requestPath);

    $routerConfig = $this->_routerConfig;
    $isResolved = FALSE;

    $subdomain = Delta_Config::getApplication()->getBoolean('module.subdomain');
    $subdomainModule = FALSE;

    if ($subdomain) {
      $subdomainModule = $this->parseSubdomainModule();
    }

    foreach ($routerConfig as $routerName => $attributes) {
      $isResolved = $this->parse($requestPath, $pathList, $attributes, $holders, $pathInfo);

      if (!$isResolved) {
        continue;
      }

      $this->_entryRouterName = $routerName;
      $this->_holders = $holders;
      $this->_request->setPathInfo($pathInfo);

      // index.php で entryModuleRegister() が実行されている場合
      if ($this->_entryModuleName === NULL) {
        if (isset($attributes['forward']['module'])) {
          $this->entryModuleRegister($attributes['forward']['module']);

        } else {
          if ($subdomainModule === FALSE) {
            $this->entryModuleRegister($this->_applicationConfig['module']['default']);
          } else {
            $this->entryModuleRegister($subdomainModule);
          }
        }
      }

      if ($this->isAllowNetwork($attributes)) {
        $entryActionName = NULL;

        if (isset($attributes['forward']['action'])) {
          $entryActionName = $this->requestFileToAction($attributes['forward']['action']);
        }

        $this->_entryActionName = $entryActionName;
      }

      if (isset($attributes['attributes'])) {
        foreach ($attributes['attributes'] as $name => $value) {
          $this->_request->setAttribute($name, $value);
        }
      }

      break;
    }

    return $isResolved;
  }

  /**
   * クライアントからリクエストされたパスを元にアクション名を生成します。
   * リクエストパスが '/manager/hello.do' の場合、デフォルトで返される値は 'Hello' になります。
   *
   * @param string $requestPath リクエストされたパス。
   * @return string パスを元に生成したアクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function requestFileToAction($requestPath)
  {
    if ($this->_isCamelCaps) {
      if (($pos = strpos($requestPath, '.')) !== FALSE) {
        $actionName = ucfirst(substr($requestPath, 0, $pos));
      } else {
        $actionName = ucfirst($requestPath);
      }

    } else {
      $actionName = Delta_StringUtils::convertPascalCase($requestPath);

      if (($pos = strpos($actionName, '.')) !== FALSE) {
        $actionName = substr($actionName, 0, strpos($actionName, '.'));
      }
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
    if ($this->_isCamelCaps) {
      $requestFile = Delta_StringUtils::convertCamelCase($actionName);
    } else {
      $requestFile = Delta_StringUtils::convertSnakeCase($actionName);
    }

    $requestFile .= $this->_applicationConfig['action']['extension'];

    return $requestFile;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function isAllowNetwork(Delta_ParameterHolder $attributes)
  {
    $allows = $attributes->getArray('access.allows');

    if (sizeof($allows)) {
      $valid = FALSE;
      $remoteAddress = $this->_request->getRemoteAddress();

      foreach ($allows as $allow) {
        if (Delta_NetworkUtils::hasContainNetwork($allow, $remoteAddress)) {
          $valid = TRUE;
          break;
        }
      }

      if (!$valid) {
        $denyForward = $attributes->get('access.denyForward');

        if ($denyForward) {
          $this->entryModuleRegister($denyForward->getString('module'));
          $this->_entryActionName = $denyForward->getString('action');
        }
      }

      return $valid;
    }

    return TRUE;
  }

  /**
   * ルーティング名からパスを生成します。
   *
   * @param string $routerName ルーティング名。
   * @param array $holders 'uri' 属性にバインドする変数。array(':module' => 'entry', ':action' => 'Start') のように複数指定可能。
   * @param array $pathInfo {@link buildPathInfo()} メソッドを参照。
   * @param bool $encode TRUE を指定した場合は queries の URL エンコードを行います。
   * @param bool $absolute 絶対パスを返す場合は TRUE、相対パスを返す場合は FALSE を指定。
   * @return string 生成したアクションのパスを返します。
   * @throws Delta_ConfigurationException 指定したルーティング名が存在しない、または必要なバインド変数が holders に含まれていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildBaseRoutingPath($routerName,
    array $holders = array(),
    array $pathInfo = array(),
    $encode = TRUE,
    $absolute = FALSE)
  {
    if (isset($this->_routerConfig[$routerName]['uri'])) {
      $parts = explode('/', $this->_routerConfig[$routerName]['uri']);
      $j = sizeof($parts);
      $path = NULL;

      for ($i = 1; $i < $j; $i++) {
        if (preg_match('/^:\w+$/', $parts[$i], $matches)) {
          $path .= '/';

          if ($matches[0] == ':module') {
            $moduleName = Delta_ArrayUtils::find($holders, ':module', $this->_entryModuleName);
            $path .= $moduleName;

          } else if ($matches[0] == ':action') {
            $actionName = Delta_ArrayUtils::find($holders, ':action');

            if ($actionName !== NULL) {
              $path .= $this->actionToRequestFile($actionName);
            }

          } else if (isset($holders[$parts[$i]])) {
            $path .= $holders[$parts[$i]];

          } else if (isset($pathInfo[$parts[$i]])) {
            $path .= $pathInfo[$parts[$i]];
            unset($pathInfo[$parts[$i]]);

          } else {
            $message = sprintf('"%s" router includes "%s", please set the holder attribute.',
              $routerName,
              $parts[$i]);
            throw new Delta_ConfigurationException($message);
          }

        } else {
          $path .= '/' . $parts[$i];
        }
      }

      if (substr($path, -2) == '/*') {
        $path = substr($path, 0, -2);

        if (sizeof($pathInfo)) {
          $pathInfoString = $this->buildPathInfo($pathInfo, $encode);

          if ($pathInfoString !== NULL && substr($path, -1, 1) !== $this->_appendSeparator) {
            $path .= $this->_appendSeparator;
          }

          $path = $path . $pathInfoString;
        }
      }

      if ($absolute) {
        $path = $this->_request->getScheme() . '://' . $this->_request->getHost() . $path;
      }

      return $path;
    }

    $message = sprintf('Route does not exist. [%s]', $routerName);
    throw new Delta_ConfigurationException($message);
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function implodeQueryValues($queryName, array $queryValues, $encode)
  {
    $query = NULL;

    foreach ($queryValues as $valueName => $value) {
      if (is_array($value)) {
        $name = sprintf('%s[%s]', $queryName, $valueName);
        $query .= $this->implodeQueryValues($name, $value, $encode);

      } else {
        if (Delta_StringUtils::nullOrEmpty($value)) {
          continue;
        }

        $valueName = sprintf('%s[%s]', $queryName, $valueName);

        if ($encode) {
          $valueName = urlencode($valueName);
          $value = urlencode($value);
        }

        $query .= $valueName . $this->_valueSeparator . $value . $this->_appendSeparator;
      }
    }

    return rtrim($query, $this->_appendSeparator);
  }

  /**
   * PATH_INFO 形式のパラメータを URI で使用されるパスの形式に変換します。
   *
   * @param array $pathInfo URI に追加するパラメータを連想配列形式で指定。
   *   値が NULL または空文字のものに関しては除外されます。
   * @param bool $encode 構築したパラメータ文字列を URL エンコードする場合は TRUE を指定。
   * @return string 構築したパラメータ文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildPathInfo(array $pathInfo, $encode = TRUE)
  {
    $query = NULL;

    foreach ($pathInfo as $queryName => $queryValue) {
      if (is_array($queryValue)) {
        $query .= $this->implodeQueryValues($queryName, $queryValue, $encode) . $this->_appendSeparator;

      // 値が NULL または空文字の場合はパラメータから除外
      // PATH_INFO 形式の場合、array('foo' => NULL, 'bar' => 'hello') のようなクエリは '/foo//bar/hello' となるため)
      } else if (!Delta_StringUtils::nullOrEmpty($queryValue)) {
        if ($encode) {
          $queryName = urlencode($queryName);
          $queryValue = urlencode($queryValue);
        }

        $query .= $queryName
                 .$this->_valueSeparator
                 .$queryValue
                 .$this->_appendSeparator;
      }
    }

    if (strlen($query)) {
      return rtrim($query, $this->_appendSeparator);
    }

    return NULL;
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
   * @param bool $absolute 絶対パスに変換する場合は TRUE、相対パスに変換する場合は FALSE を指定。
   * @return string URL エンコードされたリクエストパスを返します。
   * @see Delta_Router::setIgnoreAppendGUID()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestPath($path = NULL, array $queryData = array(), $absolute = FALSE)
  {
    $entry = array();
    $pathInfo = array();
    $anchorPoint = NULL;

    if (is_array($path)) {
      if (isset($path['router'])) {
        $entry['router'] = $path['router'];
        unset($path['router']);

      } else {
        $entry['router'] = $this->_entryRouterName;
      }

      if (isset($path['module'])) {
        $entry['module'] = $path['module'];
        unset($path['module']);

      } else {
        $entry['module'] = $this->_entryModuleName;
      }

      if (isset($path['action'])) {
        $entry['action'] = $path['action'];
        unset($path['action']);

      } else {
        if ($this->_entryModuleName === $entry['module']) {
          $entry['action'] = Delta_ActionStack::getInstance()->getLastEntry()->getActionName();

        } else {
          $search = sprintf('module.entries.%s.default', $entry['module']);
          $entry['action'] = $this->_applicationConfig->getString($search);
        }
      }

      if (isset($path['#'])) {
        $anchorPoint = '#' . $path['#'];
        unset($path['#']);
      }

      $pathInfo = $path;

    } else {
      $entry = array();
      $entry['router'] = $this->_entryRouterName;
      $entry['module'] = $this->_entryModuleName;

      if ($path === NULL) {
        $entry['action'] = Delta_ActionStack::getInstance()->getLastEntry()->getActionName();

      // アクション名が指定された
      } else if (ctype_upper(substr($path, 0, 1))) {
        $entry['action'] = $path;

      // アクション名と見なされない文字列が指定された
      } else {
        return $path;
      }
    }

    $this->_holders[':module'] = $entry['module'];
    $this->_holders[':action'] = $entry['action'];

    $path = $this->buildBaseRoutingPath($entry['router'],
      $this->_holders,
      $pathInfo,
      TRUE,
      $absolute);

    $path .= $anchorPoint;

    if (sizeof($queryData)) {
      $path = $path . '?' . http_build_query($queryData, '', '&');
    }

    return $path;
  }
}
