<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.router
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/controller/router/Delta_Route.php';

/**
 * リクエスト URI からルーティングを解決するためのクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.router
 */

class Delta_RouteResolver extends Delta_Object
{
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
   * コンストラクタ。
   *
   * @param Delta_HttpRequest HTTP リクエストオブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_HttpRequest $request)
  {
    $this->_applicationConfig = Delta_Config::getApplication();
    $this->_routesConfig = Delta_Config::getRoutes();
    $this->_request = $request;
    $this->_requestUri = $request->getURI(FALSE);

    // URI に拡張子が付いてる場合は除去する
    $extension = $this->_applicationConfig->getString('action.extension');

    if ($extension) {
      $length = strlen($extension);

      if (substr($this->_requestUri, - $length)=== $extension) {
        $this->_requestUri = substr($this->_requestUri, 0, - $length);
      }
    }

    $this->_uriSegments = explode('/', substr($this->_requestUri, 1));
  }

  /**
   * @deprecated
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAllowPackage($packageName)
  {
    $requestRouteName = $this->_request->getRoute()->getRouteName();

    foreach ($this->_routesConfig as $routeName => $attributes) {
      if (isset($attributes['packages'])) {
        $isValid = Delta_Action::isValidPackage($packageName, $attributes['packages']);

        // エントリルータが見つかった場合、パッケージが正当なものかどうかチェックする
        if ($routeName === $requestRouteName) {
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
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function connect()
  {
    $detectedRoute = FALSE;
    $isSubdomainModule = $this->_applicationConfig->getBoolean('module.subdomain');

    foreach ($this->_routesConfig as $routeName => $routeConfig) {
      if (preg_match($routeConfig->get('regexp'), $this->_requestUri)) {
        $pathHolder = array();
        $patterns = $routeConfig->get('patterns');
        $routeSegments = explode('/', substr($routeConfig->get('uri'), 1));
        $j = sizeof($routeSegments);

        for ($i = 0; $i < $j; $i++) {
          $segmentId = $routeSegments[$i];
          $segmentValue = $this->_uriSegments[$i];

          // プレースホルダの解析
          switch ($segmentId) {
            case ':module':
              $pathHolder['module'] = $segmentValue;
              break;

            case ':action':
              $pathHolder['action'] = Delta_StringUtils::convertPascalCase($segmentValue);
              break;

            default:
              if (substr($segmentId, 0, 1) === ':') {
                $segmentId = substr($segmentId, 1);
                $segmentValue = urldecode($segmentValue);

                // パターンのチェック
                if ($patterns) {
                  $pattern = $patterns->get($segmentId);

                  if ($pattern && !preg_match($pattern, $segmentValue)) {
                    continue 3;
                  }
                }

                $pathHolder[$segmentId] = $segmentValue;
              }

              break;
          }
        } // end for

        $forwardConfig = $routeConfig->get('forward');

        // モジュール名がパスに含まれていない場合はモジュールを確定させる
        if (!isset($pathHolder['module'])) {
          // サブドメインからモジュール名を判別
          if ($isSubdomainModule) {
            $pathHolder['module'] = $this->parseSubdomainModule();

          } else {
            if ($forwardConfig) {
              $pathHolder['module'] = $forwardConfig->get('module');

            } else {
              $message = sprintf('Forward module is unknown. [%s]', $routeName);
              throw new Delta_ForwardException($message);
            }
          }
        }

        // アクション名がパスに含まれていない場合はアクションを確定させる
        if (!isset($pathHolder['action'])) {
          if ($forwardConfig) {
            $pathHolder['action'] = $forwardConfig->get('action');

          } else {
            $message = sprintf('Forward action is unknown. [%s]', $routeName);
            throw new Delta_ForwardException($message);
          }
        }

        $detectedRoute = TRUE;
        break;

      } // end if
    } // end foreach

    $route = FALSE;

    if ($detectedRoute) {
      $accessConfig = $routeConfig->get('access');
      $pathHolder['route'] = $routeName;

      if ($accessConfig && !$this->isAllowNetwork($accessConfig)) {
        // アクセスが許可されていない場合はフォワード先を指定
        $denyForwardConfig = $accessConfig->get('denyForward');

        $pathHolder['module'] = $denyForwardConfig->get('module');
        $pathHolder['action'] = $denyForwardConfig->get('action');
      }

      $route = new Delta_Route($pathHolder);
    }

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
   * 指定されたパスを Web からアクセス可能なリクエストパスの形式に変換します。
   *
   * @param mixed $path 遷移先のリクエストパスを文字列、または配列形式で指定することができる。
   *   文字列形式の場合、は PascalCase 形式でアクション名を指定。
   *   (ただし、'http://'、'https://' から始まるパスは外部リンク、'/' から始まるパスは絶対と見なされ、パスの変換は行われない)
   *   配列形式では以下のキーから構成されるリクエストパスを生成する。
   *     - route: パスの生成に用いるルート名。未指定時は現在有効なルートが適用される
   *     - module: 遷移先のモジュール名。未指定時は現在有効なモジュールが適用される
   *     - action: 遷移先のアクション名。未指定時は現在有効なアクションが適用される
   *
   *   上記以外に指定されたキーはリクエストパスのホルダ ID に適用される
   *   <code>
   *   // routes.yml に 'uri: /:action/:greeting' が定義されている場合、'/index/helloWorld.do' といったパスを生成
   *   array('action' => 'Index', 'greeting' => 'helloWorld');
   *
   *   // フラグメント識別子の指定
   *   // '/index.do#top' といったパスを生成
   *   array('action' => 'Index', 'fragment' => 'top');
   *   </code>
   * @param array $queryData path に追加する GET パラメータ。
   * @param bool $absolute 絶対パスを返す場合は TRUE、相対パスを返す場合は FALSE を指定。
   * @secure bool $secure URL スキームの形式。
   *   o TRUE: 'https' 形式
   *   o FALSE: 'http' 形式
   *   o NULL: 現在のリクエスト形式に合わせる
   * @return string URL エンコードされたリクエストパスを返します。
   * @throws Delta_ForwardException ルートパスの生成に必要なホルダが不足している場合に発生。
   * @throws Delta_ConfigurationException 指定されたルートがルート設定ファイル定義されていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestPath($path = NULL, array $queryData = array(), $absolute = FALSE, $secure = NULL)
  {
    $route = $this->_request->getRoute();
    $buildPath = NULL;

    if ($route) {
      $fragment = NULL;
      $externalLink = FALSE;

      if (is_array($path)) {
        if (isset($path['fragment'])) {
          $fragment = '#' . $path['fragment'];
        }
      } else {
        if (strpos($path, 'http://') !== FALSE || strpos($path, 'https://') !== FALSE) {
          $externalLink = TRUE;
        }
      }

      // 内部リンクの生成
      if (!$externalLink) {
        if (is_array($path) || substr($path, 0, 1) !== '/') {
          $pathHolder = $this->buildPathHolder($route, $path);
          $actionPathFormat = $this->_applicationConfig->get('action.path');

          if (isset($this->_routesConfig[$pathHolder['route']])) {
            $moduleName = $this->_request->getRoute()->getModuleName();

            $uriSegments = explode('/', substr($this->_routesConfig[$pathHolder['route']]['uri'], 1));
            $j = sizeof($uriSegments);

            for ($i = 0; $i < $j; $i++) {
              if (preg_match('/^:(\w+)$/', $uriSegments[$i], $matches)) {
                $buildPath .= '/';

                if ($matches[1] == 'module') {
                  $buildPath .= $pathHolder['module'];

                } else if ($matches[1] == 'action') {
                  if ($actionPathFormat === 'underscore') {
                    $buildPath .= Delta_StringUtils::convertSnakeCase($pathHolder['action']);
                  } else {
                    $buildPath .= Delta_StringUtils::convertCamelCase($pathHolder['action']);
                  }

                } else if (isset($pathHolder[$matches[1]])) {
                  $buildPath .= urlencode($pathHolder[$matches[1]]);

                } else {
                  $message = sprintf('Path parameter is missing. [%s]', $matches[1]);
                  throw new Delta_ForwardException($message);
                }

              } else {
                $buildPath .= '/' . $uriSegments[$i];
              }
            }  // end if

            if ($absolute) {
              if ($secure === NULL) {
                $scheme = $this->_request->getScheme() . '://';
              } else if ($secure === TRUE) {
                $scheme = 'https://';
              } else {
                $scheme = 'http://';
              }

              $buildPath = $scheme . $this->_request->getHost() . $buildPath;
            }

            if (substr($buildPath, -1) !== '/') {
              $buildPath .= $this->_applicationConfig->getString('action.extension');
            }

            if ($fragment !== NULL) {
              $buildPath .= $fragment;
            }

            if (sizeof($queryData)) {
              $buildPath = sprintf('%s?%s', $buildPath, http_build_query($queryData, '', '&amp;'));
            }
          } else {
            $message = sprintf('Route does not exist. [%s]', $pathHolder['route']);
            throw new Delta_ConfigurationException($message);
          }

        } else {
          $buildPath = $path;
        }

      } // end if
    } else {
      $buildPath = $path;
    }

    return $buildPath;
  }

  /**
   * @param Delta_Route $route
   * @param string $path
   * @return array
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildPathHolder($route, $path)
  {
    $pathHolder = $route->getPathHolder();

    // パスが配列で構成されている
    if (is_array($path)) {
      foreach ($path as $key => $value) {
        $pathHolder[$key] = $value;
      }

      if (!isset($path['route'])) {
        $pathHolder['route'] = $route->getRouteName();
      }

      if (!isset($path['module'])) {
        $pathHolder['module'] = $route->getModuleName();
      }

      if (!isset($path['action'])) {
        $pathHolder['action'] = $route->getActionName();
      }

    // パスが文字列で構成されている場合
    } else {
      $pathHolder['route'] = $route->getRouteName();
      $pathHolder['module'] = $route->getModuleName();

      if ($path === NULL) {
        $pathHolder['action'] = $route->getForwardStack()->getLast()->getActionName();
      } else {
        $pathHolder['action'] = $path;
      }
    }

    return $pathHolder;
  }
}
