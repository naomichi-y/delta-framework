<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.response
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * クライアントに応答する HTTP レスポンスを制御します。
 *
 * このクラスは 'response' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_DIController::getResponse()} からインスタンスを取得することができます。
 *
 * Delta_HttpResponse が提供するいくつかのメソッドは、{@link flush()} メソッドによってクライアントにレスポンスが返されるタイミングで実行されます。
 *   - {@link setStatus()}
 *   - {@link setHeader()}
 *   - {@link setCacheExpire()}
 *   - {@link sendRedirectAction()}
 *   - {@link sendRedirect()}
 *   - {@link addCookie()}
 *   - {@link setContentType()}
 *   - {@link write()}
 *   - {@link writeBinary()}
 *   - {@link writeJSON()}
 *   - {@link removeHeader()}
 *   - {@link setDownloadData()}
 *
 * 例えば {@link setHeader()} メソッドはクライアントに HTTP ヘッダを送信しますが、setHeader() の直後で exit() をコールした場合、ヘッダはクライアントに送信されないことになります。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.response
 */

class Delta_HttpResponse extends Delta_Object
{
  /**
   * 出力エンコーディング。
   * @var string
   */
  private $_outputEncoding;

  /**
   * 応答コンテンツタイプ。
   * @var string
   */
  private $_contentType;

  /**
   * 応答レスポンスコード。
   * @var string
   */
  private $_status;

  /**
   * 応答ヘッダ配列。
   * @var array
   */
  private $_headers = array();

  /**
   * 応答 Cookie 配列。
   * @var array
   */
  private $_cookies = array();

  /**
   * レスポンスがコミット済みであるかどうか。
   * @var bool
   */
  private $_isCommitted = FALSE;

  /**
   * 出力バッファ。
   * @var string
   */
  private $_writeBuffer = NULL;

  /**
   * バッファに書き込み可能な状態にあるかどうか。
   * @var bool
   */
  private $_isWrite = TRUE;

  /**
   * バイナリデータが含まれているかどうか。
   * @var bool
   */
  private $_hasBinary = FALSE;

  /**
   * HTTP ステータスコードリスト。
   * @var array
   */
  private $_statusCode = array(
    // 1xx informational
    100 => 'Continue',
    101 => 'Switching Protocols',

    // 2xx success
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',

    // 3xx redirection
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    307 => 'Moved Temporarily', // HTTP 1.1 (HTTP 1.0 302)

    // 4xx client error
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Time-out',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Large',
    415 => 'Unsupported Media Type',

    // server error
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Time-out',
    505 => 'HTTP Version not supported'
  );

  /**
   * レスポンスオブジェクトを初期化します。
   * レスポンス出力時のデフォルトコンテンツタイプ、及びエンコーディング形式はクライアントのユーザエージェントに依存します。
   *
   * @see Delta_UserAgentAdapter::getContentType()
   * @see Delta_HttpResponse::setOutputEncoding()
   * @throws RuntimeException 出力コールバック関数が未定義の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {
    $config = Delta_Config::getApplication();

    if ($config->getBoolean('controller.detectUserAgent')) {
      $container = Delta_DIContainerFactory::getContainer();
      $userAgent = $container->getComponent('request')->getUserAgent();

      $this->setOutputEncoding($userAgent->getEncoding());
      $this->setContentType($userAgent->getContentType());

    } else {
      $this->setOutputEncoding($config->getString('charset.default'));
    }

    // 出力バッファにコールバック関数を適用
    $callback = $config->getString('response.callback');

    if ($callback !== 'none') {
      if (function_exists($callback)) {
        ob_start($callback);

      } else {
        $message = sprintf('Can\'t find %s() function of the output buffer. '
          .'Please set to \'none\' of application.yml \'response.callback\'.', $callback);
        throw new RuntimeException($message);
      }
    }
  }

  /**
   * 出力エンコーディング形式を設定します。
   *
   * @param string $outputEncoding 出力エンコーディング形式。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setOutputEncoding($outputEncoding)
  {
    $this->_outputEncoding = $outputEncoding;
  }

  /**
   * 出力エンコーディングを取得します。
   *
   * @return string 出力エンコーディングを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOutputEncoding()
  {
    return $this->_outputEncoding;
  }

  /**
   * クライアントに送信する HTTP ステータスコードを設定します。
   * {@link setStatus()} メソッドは、全ての出力が行われる前にコールする必要があります。
   *
   * @param int $status クライアントに送信する HTTP レスポンスコード。
   * @param string $version HTTP プロトコルのバージョン。
   * @throws InvalidArgumentException 未定義のレスポンスコードが指定された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setStatus($status, $version = '1.1')
  {
    $this->sendStatus($status, NULL, $version);
  }

  /**
   * クライアントにエラーステータスを送信すると共に、エラーメッセージを出力します。
   * このメソッドは実行した時点でレスポンスが返されます。
   * 後に続く処理は一切実行されない点に注意して下さい。
   * エラーの出力に使用されるテンプレートは {APP_ROOT_DIR}/templates/html/http_error.php にあります。
   *
   * @param int $status クライアントに送信する HTTP レスポンスコード。
   * @param string $message 応答メッセージ。未指定時はデフォルトのエラーメッセージが返されます。
   * @param string $version HTTP プロトコルのバージョン。
   * @see Delta_HttpResponse::sendStatus()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendError($status, $message = NULL, $version = '1.1')
  {
    ob_get_clean();
    ob_start();

    $this->sendStatus($status, $message, $version);
    $path = sprintf('%s%shtml%shttp_error.php',
      $this->getAppPathManager()->getTemplatesPath(),
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);

    $view = new Delta_View(new Delta_BaseRenderer());
    $view->setAttribute('message', $this->_status);
    $view->setTemplatePath($path);
    $view->execute();

    $buffer = ob_get_contents();
    ob_end_clean();

    $arguments = array(&$buffer);
    $this->getObserver()->dispatchEvent('preOutput', $arguments);

    $this->write($buffer);
    $this->flush();

    die();
  }

  /**
   * クライアントに返すレスポンスコードを設定します。
   *
   * @param int $status クライアントに送信する HTTP レスポンスコード。
   * @param string $message 応答メッセージ。
   * @param string $version HTTP プロトコルのバージョン。
   * @throws Delta_UnsupportedException サポートされていないステータスコードが指定された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function sendStatus($status, $message, $version)
  {
    if ($status == 302) {
      if ($version == '1.1') {
        $check = 307;
      } else {
        $check = 302;
      }

    } else {
      $check = $status;
    }

    if (isset($this->_statusCode[$check])) {
      if ($message === NULL) {
        $message = $this->_statusCode[$check];
      }

      $this->_status = sprintf('HTTP/%s %s %s', $version, $status, $message);

    } else {
      $message = sprintf('Undefined status cord. [%s]', $status);
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * レスポンスデータがクライアントに送信済みであるかどうかチェックします。
   *
   * @return bool レスポンスが送信済みの場合は TRUE、未送信の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isCommitted()
  {
    return $this->_isCommitted;
  }

  /**
   * クライアントに送信するヘッダを設定します。
   *
   * @param string $name ヘッダ名。
   * @param string $value ヘッダの値。
   * @param bool $replace ヘッダの上書きを許可するかどうかの指定。TRUE を指定した場合は値を上書きする。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setHeader($name, $value, $replace = FALSE)
  {
    if ($replace) {
      $this->_headers[$name] = $value;

    } else {
      if (!isset($this->_headers[$name])) {
        $this->_headers[$name] = $value;
      }
    }
  }

  /**
   * 指定されたヘッダがレスポンスオブジェクトに設定されているかチェックします。
   *
   * @param string $name チェック対象のヘッダ名。
   * @return bool 対象ヘッダがレスポンスオブジェクトに設定済みに場合は TRUE、未設定の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasHeader($name)
  {
    return isset($this->_headers[$name]);
  }

  /**
   * レスポンスオブジェクトに設定されたクライアントに送信するヘッダを取得します。
   *
   * @param string $name 対象のヘッダ名。
   * @return string name に対応するヘッダを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeader($name)
  {
    if (isset($this->_headers[$name])) {
      return $this->_headers[$name];
    }

    return NULL;
  }

  /**
   * レスポンスオブジェクトに設定されたクライアントに送信する全てのヘッダを取得します。
   *
   * @return array 全てのヘッダを連想配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaders()
  {
    return $this->_headers;
  }

  /**
   * レスポンスオブジェクトに設定されている全てのヘッダを削除します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearHeader()
  {
    $this->_headers = array();
  }

  /**
   * レスポンスオブジェクトに設定されている特定のヘッダを削除します。
   *
   * @param string $name 削除対象のヘッダ名。
   * @return string $name ヘッダの削除が成功した場合は TRUE、失敗した (ヘッダが存在しない) 場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeHeader($name)
  {
    if (isset($this->_headers[$name])) {
      unset($this->_headers[$name]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * クライアントに応答コンテンツを返します。
   * このメソッドはコントローラによってレスポンス出力時に呼び出されます。
   *
   * @throws RuntimeException レスポンスが既に送信済みの場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function flush()
  {
    if (!$this->_isCommitted) {
      // Content-Type の出力 (指定がないと出力エンコーディングが送信されない)
      if ($this->_contentType === NULL) {
        $contentType = 'text/html; charset=' . $this->_outputEncoding;
        $this->setContentType($contentType);
      }

      // HTTP ステータスの送信
      if ($this->_status !== NULL) {
        header($this->_status);
      }

      // HTTP ヘッダの送信
      $headers = $this->_headers;

      foreach ($headers as $name => $value) {
        header(sprintf('%s: %s', $name, $value));
      }

      // クッキーの送信
      $cookies = $this->_cookies;

      foreach ($cookies as $cookie) {
        setcookie($cookie['name'],
                  $cookie['value'],
                  $cookie['expire'],
                  $cookie['path'],
                  $cookie['domain'],
                  $cookie['secure'],
                  $cookie['httpOnly']);
      }

      if ($this->hasWriteBuffer()) {
        echo $this->getWriteBuffer();
      }

      $this->_isCommitted = TRUE;

    } else {
      throw new RuntimeException('Response has already been committed.');
    }
  }

  /**
   * レスポンスを送信する際にキャッシュを無効化します。
   * {@link setCacheExpire()} メソッドは、全ての出力が行われる前にコールする必要があります。
   *
   * 出力されるヘッダ:
   *   - Expires: Thu, 01 Dec 1994 16:00:00 GMT
   *   - Last-Modified: {現在の GMT 時刻}
   *   - Cache-Control: no-store, no-cache, must-revalidate
   *   - Cache-Control: post-check=0, pre-check=0
   *   - Pragma: no-cache
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCacheExpire()
  {
    $this->setHeader('Expires', 'Thu, 01 Dec 1994 16:00:00 GMT');
    $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
    $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    $this->setHeader('Cache-Control', 'post-check=0, pre-check=0');
    $this->setHeader('Pragma', 'no-cache');
  }

  /**
   * 指定したアクションへリダイレクトします。
   * {@link sendRedirect()} の項も合わせて参照して下さい。
   *
   * @param string $path リダイレクト先のアクション名。
   *   指定可能なパスの書式は {@link Delta_RouteResolver::buildRequestPath()} メソッドを参照。
   * @param array $queryData リダイレクト URI に追加する GET パラメータを連想配列形式で指定。
   * @param bool $appendSessionId クエリにセッション ID を追加している場合、リダイレクト先のアクションに ID を引き継ぐかどうかを指定。
   * @throws Delta_RequestException ルーティング経路が確定していない状態でメソッドをコールした場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendRedirectAction($path, array $queryData = array(), $appendSessionId = TRUE)
  {
    $router = Delta_RouteResolver::getInstance();
    $request = Delta_DIContainerFactory::getContainer()->getComponent('request');

    if ($router->getEntryRouterName() === NULL) {
      $message = sprintf('Routing path is not established. [%s]', $request->getURI(FALSE));
      throw new Delta_RequestException($message);
    }

    if ($appendSessionId) {
      $sessionName = Delta_Config::getApplication()->getString('session.name');
      $isSendSessionId = FALSE;

      if ($request->getRequestMethod() == Delta_HttpRequest::HTTP_GET) {
        if ($request->getQuery($sessionName)) {
          $isSendSessionId = TRUE;
        }

      } else {
        if ($request->getPost($sessionName)) {
          $isSendSessionId = TRUE;
        }
      }

      if ($isSendSessionId) {
        // セッション ID が生成し直されてる可能性があるため再取得する
        $queryData[$sessionName] = session_id();
      }
    }

    $path = $router->buildRequestPath($path, $queryData, TRUE);

    $this->sendRedirect($path);
  }

  /**
   * 指定した URI にリダイレクトします。
   * 実際にリダイレクトされるのは、{@link flush()} メソッドが実行されたタイミングであることに注意して下さい。
   *
   * @param string $uri リダイレクト先の URI。
   *   '/' (スラッシュ) から始まるパスは相対パスとみなし、アプリケーションの URI が補完されます。
   *   ただし将来的に URI の設計が変更されることを想定した場合、相対パスによる指定を行うべきではありません。
   *   代わりに {@link sendRedirectAction()} メソッドを使用するべきです。
   * @param int $status 送信するレスポンスコード。301〜307 を指定可能。
   * @throws Delta_SecurityException URI に CrLf インジェクションとなりうる文字列が含まれる場合に発生。
   * @throws Delta_UnsupportedException サポートされていないステータスコードが指定された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendRedirect($uri, $status = 302)
  {
    // CrLf インジェクション対策
    if (strpos($uri, "\r") !== FALSE || strpos($uri, "\n") !== FALSE) {
      $message = sprintf('Contains an invalid character in redirect URI. [%s]', $uri);
      throw new Delta_SecurityException($message);
    }

    if (strcasecmp(substr($uri, 0, 4), 'http') !== 0) {
      $request = Delta_DIContainerFactory::getContainer()->getComponent('request');
      $uri = $request->getScheme() . '://' . $request->getHost() . $uri;
    }

    if ($status >= 301 && $status <= 307) {
      $this->setStatus($status);

    } else {
      $message = sprintf('Unknown status code. [%s]', $status);
      throw new Delta_UnsupportedException($message);
    }

    $container = Delta_DIContainerFactory::getContainer();

    if ($container->hasComponent('session')) {
      $container->getComponent('session')->finalize();
    }

    $this->setHeader('Location', $uri);
  }

  /**
   * クライアントに送信する Cookie を設定します。
   * 実際に Cookie が送信されるのは、{@link flush()} メソッドが実行されたタイミングであることに注意して下さい。
   *
   * @param string $name クライアントに送信する Cookie の名前。
   * @param string $value クライアントに送信する Cookie の値。
   * @param int $expire Cookie の有効期限を 1970 年 1 月 1 日からの経過秒数で指定。
   *   未指定時はクライアントを閉じるまでが有効期間となる。
   * @param string $path Cookie を有効とするサーバ上のパス。
   * @param string $domain Cookie を有効とするドメイン名。
   * @param bool $secure セキュア Cookie の設定。TRUE を指定した場合、セキュアな通信 (HTTPS) が行われている場合のみ Cookie を送信する。
   * @param bool $httpOnly TRUE を指定した場合、JavaScript (document.cookie) から Cookie を参照できないようにする。
   *   ただし、httpOnly は Internet Explorer の独自拡張のため、全ての XSS から盗聴を防げる訳ではない。
   * @throws InvalidArgumentException Cookie の有効期限の指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addCookie($name,
    $value,
    $expire = NULL,
    $path = NULL,
    $domain = NULL,
    $secure = FALSE,
    $httpOnly = TRUE)
  {
    if (is_numeric($expire) || $expire === NULL) {
      $cookie = array();
      $cookie['name'] = $name;
      $cookie['value'] = $value;
      $cookie['expire'] = $expire;
      $cookie['path'] = $path;
      $cookie['domain'] = $domain;
      $cookie['secure'] = $secure;
      $cookie['httpOnly'] = $httpOnly;

      $this->_cookies[] = $cookie;

    } else {
      throw new InvalidArgumentException('Expire is illegal.');
    }
  }

  /**
   * クライアントが保持している全ての Cookie を削除します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearCookie()
  {
    foreach ($_COOKIE as $name => $value) {
      unset($_COOKIE[$name]);
      $this->addCookie($name, NULL, -1);
    }
  }

  /**
   * クライアントが保持している特定の Cookie を削除します。
   *
   * @param string $name 削除対象の Cookie 名。
   * @return string $name Cookie の削除が成功した場合は TRUE、失敗した (Cookie が存在しない) 場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeCookie($name)
  {
    if (isset($_COOKIE[$name])) {
      unset($_COOKIE[$name]);
      $this->addCookie($name, NULL, -1);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * クライアントに送信するコンテンツタイプ (Content-Type ヘッダ) を設定します。
   *
   * @param string $contentType クライアントに送信するコンテンツタイプ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setContentType($contentType)
  {
    $this->_contentType = $contentType;
    $this->setHeader('Content-Type', $contentType, TRUE);
  }

  /**
   * レスポンスオブジェクトに設定されたコンテンツタイプ (Content-Type ヘッダ) を取得します。
   *
   * @return string レスポンスオブジェクトに設定されたコンテンツタイプを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentType()
  {
    return $this->_contentType;
  }

  /**
   * 出力バッファにデータを書き込みます。
   * 書き込まれたデータは {@link flush()} メソッドがコールされた時点で出力されます。
   *
   * @param mixed $data 出力するデータ。データは追記型で書き込まれます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($data)
  {
    $this->_writeBuffer .= $data;
  }

  /**
   * 出力バッファが書き込み可能な状態にあるかどうかチェックします。
   *
   * @return bool 出力バッファが書き込み可能な状態にある場合は TRUE を返します。
   *   {@link writeBinary()}、{@link writeJSON()} 等のメソッドがコールされた場合、isWrite() は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isWrite()
  {
    return $this->_isWrite;
  }

  /**
   * 出力バッファをクリアします。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_writeBuffer = NULL;
    $this->_hasBinary = FALSE;
    $this->_isWrite = TRUE;
  }

  /**
   * 指定されたイメージデータをバイナリ形式で出力します。
   * {@link writeImage()} で出力バッファにデータが書き込まれた場合、後に続く出力制御は全て無効となります。
   *
   * @param string $data 出力するイメージデータ。
   * @param string $contentType Content-Type の形式。未指定の場合は data の形式から自動判別されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function writeImage($data, $contentType = NULL)
  {
    $this->_writeBuffer = $data;
    $this->_hasBinary = TRUE;
    $this->_isWrite = FALSE;

    if ($contentType === NULL) {
      $info = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_buffer($info, $data);
      finfo_close($info);

      $this->setContentType($mimeType);

    } else {
      $this->setContentType($contentType);
    }
  }

  /**
   * 指定されたデータをバイナリ形式で出力します。
   * {@link writeBinary()} で出力バッファにデータが書き込まれた場合、後に続く出力制御は全て無効となります。
   *
   * @param string $data 出力するデータ。
   * @param string $contentType Content-Type の形式。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function writeBinary($data, $contentType = 'application/octet-stream')
  {
    $this->_writeBuffer = $data;
    $this->_hasBinary = TRUE;
    $this->_isWrite = FALSE;

    $this->setContentType($contentType);
  }

  /**
   * 指定されたデータを JSON エンコードした形式で出力します。
   * {@link writeJSON()} で出力バッファにデータが書き込まれた場合、後に続く出力制御は全て無効となります。
   *
   * @param string $data 出力対象のデータ。Content-Type は自動的に設定されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function writeJSON($data)
  {
    $this->_writeBuffer = json_encode($data);
    $this->_isWrite = FALSE;

    $this->setContentType('application/json');
  }

  /**
   * 指定したデータをダウンロード形式で提供します。
   *
   * @param string $data ダウンロード対象のデータ。
   * @param string $name ダウンロード時のファイル名。未指定の場合は、現在実行しているアクション名を camelCaps 形式に変換した名前が適用されます。
   * @param bool $isBinary 対象データがバイナリデータの場合に TRUE を指定します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDownloadData($data, $name = NULL, $isBinary = FALSE)
  {
    if ($name === NULL) {
      $route = Delta_DIContainerFactory::getContainer()->getComponent('request')->getRoute();
      $actionName = $route->getForwardStack()->getLast()->getAction()->getActionName();
      $name = Delta_StringUtils::convertCamelCase($actionName);
    }

    $this->setContentType('application/octet-stream');
    $this->setHeader('Content-Disposition', 'attachment; filename=' . $name);

    $this->_writeBuffer = $data;
    $this->_isWrite = FALSE;

    // Delta_KernelEventObserver::outputBuffer() の動作に影響
    $this->_hasBinary = $isBinary;
  }

  /**
   * 出力バッファにバイナリデータが含まれているかチェックします。
   *
   * @return bool 出力バッファにバイナリデータが含まれている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasBinary()
  {
    return $this->_hasBinary;
  }

  /**
   * 出力バッファにデータが含まれているかチェックします。
   *
   * @return bool 出力バッファにデータが含まれている場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasWriteBuffer()
  {
    if ($this->_writeBuffer === NULL) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * レスポンスオブジェクトに含まれる出力バッファを取得します。
   *
   * @return string レスポンスオブジェクトに含まれる出力バッファを返します。
   * @see Delta_HttpResponse::hasWriteBuffer()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getWriteBuffer()
  {
    return $this->_writeBuffer;
  }
}
