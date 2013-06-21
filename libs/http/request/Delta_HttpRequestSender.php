<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.request
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * GET/POST ベースによる HTTP リクエストを発行します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.request
 */

class Delta_HttpRequestSender extends Delta_Object
{
  /**
   * POST データの送信形式。(JSON)
   */
  const FORMAT_JSON = 'application/json';

  /**
   * MIME 定数。(application/x-www-form-urlencoded)
   */
  const CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';

  /**
   * MIME 定数。(multipart/form-data)
   */
  const CONTENT_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';

  /**
   * リクエスト基底 URI。
   */
  private $_baseURI;

  /**
   * リクエストパス。
   * @var string
   */
  private $_requestPath;

  /**
   * リクエストメソッド。(Delta_HttpRequest::HTTP_*)
   * @var string
   */
  private $_requestMethod = Delta_HttpRequest::HTTP_GET;

  /**
   * ユーザエージェント。
   * @var string
   */
  private $_userAgent;

  /**
   * stream_context_create() のオプションリスト。
   * @var array
   */
  private $_options = array();

  /**
   * 最大リダイレクト回数。
   * @var int
   */
  private $_maxRedirect = 2;

  /**
   * HTTP プロトコルバージョン。
   * @var string
   */
  private $_protocolVersion = "1.0";

  /**
   * 読み込みタイムアウト秒。
   * @var int
   */
  private $_readTimeout = 10;

  /**
   * 送信ヘッダリスト。
   * @var array
   */
  private $_headers = array();

  /**
   * 送信クエリパラメータ。
   * @var array
   */
  private $_parameters;

  /**
   * 送信ファイル。
   * @var array
   */
  private $_files;

  /**
   * POST データの送信フォーマット。
   * @var string
   */
  private $_postFormat;

  /**
   * POST データのエンコーディング形式。
   * @var string
   */
  private $_postEncoding = self::CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENCODED;

  /**
   * 応答ヘッダリスト。
   * @var array
   */
  private $_responseHeaders = array();

  /**
   * コンストラクタ。
   *
   * @param string $baseURI リクエスト対象の基底 URI。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($baseURI = NULL)
  {
    if ($baseURI !== NULL) {
      $this->setBaseURI($baseURI);
    }

    $this->clearHeader();
    $this->clearParameter();
    $this->clearUploadFile();
  }

  /**
   * リクエスト対象の基底 URI を設定します。
   *
   * @param string $baseURI リクエスト対象の基底 URI。
   * @param bool $entityDecode baseURI に含まれる HTML エンティティを適切な文字に変換する場合は TRUE を指定。
   *   TRUE を指定した場合、例えば baseURI 内の "&" は "&amp;" に変換されます。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBaseURI($baseURI, $entityDecode = FALSE)
  {
    if ($entityDecode) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
      $baseURI = html_entity_decode($baseURI, ENT_QUOTES, $encoding);
    }

    $this->_baseURI = $baseURI;

    return $this;
  }

  /**
   * {@link setBaseURI()} メソッドで指定した基底 URI に対するリクエストパスを指定します。
   * 例えば setBaseURI('http://localhost/') が指定された状態で setRequestPath('manager/login.do') を指定した場合、実際に発行されるリクエスト URI は 'http://localhost/manager/login.do' となります。
   * <i>このメソッドで指定したパスは、{@link send()} メソッドがコールされた時点で自動的に破棄されます。</i>
   *
   * @param string $request リクエストパス。
   * @param bool $entityDecode {@link setBaseURI()} メソッドを参照。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRequestPath($requestPath, $entityDecode = FALSE)
  {
    if ($entityDecode) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
      $requestPath = html_entity_decode($requestPath, ENT_QUOTES, $encoding);
    }

    $this->_requestPath = $requestPath;

    return $this;
  }

  /**
   * リクエストメソッドを設定します。
   *
   * @param int $requestMethod {@link Delta_HttpRequest::HTTP_GET}、または {@link Delta_HttpRequest::HTTP_POST} を指定。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRequestMethod($requestMethod)
  {
    $this->_requestMethod = $requestMethod;

    return $this;
  }

  /**
   * BASIC 認証を通過するための認証情報をセットします。
   *
   * @param string $user ユーザ名。
   * @param string $password パスワード。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBasicAuthorization($user, $password)
  {
    $authorization = 'Basic ' . base64_encode($user . ':' . $password);
    $this->addHeader('Authorization', $authorization);

    return $this;
  }

  /**
   * ユーザエージェントを設定します。
   *
   * @param string $userAgent ユーザエージェント。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setUserAgent($userAgent)
  {
    $this->_userAgent = $userAgent;
    $this->addHeader('User-Agent', $userAgent);

    return $this;
  }

  /**
   * 受け入れ可能なメディアタイプを設定します。
   *
   * @param string $value 受け入れ可能なメディアタイプ。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAccept($value)
  {
    $this->addHeader('Accept', $value);

    return $this;
  }

  /**
   * 受け入れ可能な文字セットを設定します。
   *
   * @param string $value 受け入れ可能な文字セット。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAcceptCharset($value)
  {
    $this->addHeader('Accept-Charset', $value);

    return $this;
  }

  /**
   * 受け入れ可能なコンテンツのエンコーディングを設定します。
   *
   * @param string $value 受け入れ可能なコンテンツのエンコーディング。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAcceptEncoding($value)
  {
    $this->addHeader('Accept-Encoding', $value);

    return $this;
  }

  /**
   * 受け入れ可能な言語を設定します。
   *
   * @param string $value 受け入れ可能な言語。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAcceptLanguage($value)
  {
    $this->addHeader('Accept-Language', $value);

    return $this;
  }

  /**
   * 経由するプロキシサーバを設定します。
   *
   * @param string $host プロキシホスト名。
   * @param int $port プロキシポート番号。
   * @param bool $requestFullURI TRUE を設定した場合、リクエスト発行時に完全な URI が利用されます。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setProxy($host, $port, $requestFullURI = TRUE)
  {
    $this->_options['http']['proxy'] = 'tcp://' . $host . ':' . $port;
    $this->_options['http']['request_fulluri'] = $requestFullURI;

    return $this;
  }

  /**
   * 接続先のページがリダイレクトレスポンスを返した際、再リダイレクト要求を行う最大回数を設定します。
   *
   * @param int $maxRedirect 最大リダイレクト回数。既定値は 2 回。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setMaxRedirect($maxRedirect)
  {
    $this->_maxRedirect = $maxRedirect;

    return $this;
  }

  /**
   * レスポンスヘッダに Location が含まれる場合、ヘッダに記された URI にリダイレクトするかどうかを設定します。
   * 既定の動作では URI をたどります。
   *
   * @param bool $followLocation Location をたどる場合は TRUE、たどらない場合は FALSE を指定。
   *   FALSE 指定時の動作は {@link setMaxRedirect()} メソッドに 0 を指定した場合と同じ挙動になります。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFollowLocation($followLocation)
  {
    $this->_options['http']['follow_location'] = $followLocation;

    return $this;
  }

  /**
   * リクエスト要求時における HTTP プロトコルのバージョンを設定します。
   *
   * @param float $protocolVersion プロトコルバージョンの指定。既定値は 1.1。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setProtocolVersion($protocolVersion)
  {
    $this->_protocolVersion = $protocolVersion;

    return $this;
  }

  /**
   * 読み込みタイムアウト秒を設定します。
   *
   * @param float $readTimeout タイムアウト秒数。既定値は 10 秒。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setReadTimeout($readTimeout)
  {
    $this->_readTimeout = $readTimeout;

    return $this;
  }

  /**
   * サーバに送信する Cookie 情報を追加します。
   *
   * @param string $name Cookie 名。
   * @param string $value Cookie の値。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addCookie($name, $value)
  {
    $value = $name . '=' . $value;

    if (isset($this->_headers['Cookie'])) {
      $this->_headers['Cookie'] .= '; ' . $value;
    } else {
      $this->_headers['Cookie'] = $value;
    }

    return $this;
  }

  /**
   * キー、値の組ではない生の Cookie を設定します。
   *
   * @param string $value Cookie データ。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRawCookie($value)
  {
    $this->_headers['Cookie'] = $value;

    return $this;
  }

  /**
   * 設定されている全ての Cookie を削除します。
   *
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearCookie()
  {
    $this->removeHeader('Cookie');

    return $this;
  }

  /**
   * 設定されている特定の Cookie を削除します。
   *
   * @param string $name 削除対象の Cookie 名。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeCookie($name)
  {
    $headers = explode(';', $this->_headers['Cookie']);
    $buffer = NULL;

    foreach ($headers as $header) {
      $header = trim($header);

      if (strpos($header, $name . '=') !== 0) {
        $buffer .= $header . '; ';
      }
    }

    $this->_headers['Cookie'] = $buffer;

    return $this;
  }

  /**
   * サーバに送信する HTTP ヘッダを追加します。
   *
   * @param string $name HTTP ヘッダ名。
   * @param mixed $value HTTP ヘッダ値。既に同一のヘッダが設定されている場合は値が上書きされます。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addHeader($name, $value)
  {
    $this->_headers[$name] = $value;

    return $this;
  }

  /**
   * 設定されている全てのヘッダを削除します。
   *
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearHeader($name = NULL)
  {
    $this->_headers = array();
    $userAgent = ini_get('user_agent');

    if (strlen($userAgent) == 0) {
      $userAgent = 'PHP';
    }

    $this->setUserAgent($userAgent);
  }

  /**
   * 設定されている特定のヘッダを削除します。
   *
   * @param string $name 削除対象のヘッダ名。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeHeader($name)
  {
    if (isset($this->_headers[$name])) {
      unset($this->_headers[$name]);
    }

    return $this;
  }

  /**
   * サーバに送信するリクエストパラメータを追加します。
   *
   * @param string $name リクエストパラメータ名。
   * @param string $value リクエストパラメータ値。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addParameter($name, $value)
  {
    $this->_parameters[$name] = $value;

    return $this;
  }

  /**
   * サーバに送信するリクエストパラメータを連想配列形式で追加します。
   *
   * @param array $parameters 連想配列形式のリクエストパラメータ。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addParameters(array $parameters)
  {
    foreach ($parameters as $name => $value) {
      $this->_parameters[$name] = $value;
    }

    return $this;
  }

  /**
   * 設定されているリクエストパラメータを削除します。
   *
   * @param string $name 削除対象のパラメータ名。未指定の場合は設定済みの全てのパラメータを削除します。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearParameter($name = NULL)
  {
    if ($name === NULL) {
      $this->_parameters = array();
    } else {
      unset($this->_parameters[$name]);
    }

    return $this;
  }

  /**
   * サーバに送信するファイルを追加します。
   *
   * @param string $name リクエストパラメータ名。
   * @param string $filePath 送信するファイルのパス。APP_ROOD_DIR からの相対パス、あるいは絶対パスが有効。
   *   パスが未指定の場合はパラメータのみ送信され、ファイルのアップロードはないものと見なされます。
   * @param string $mimeType ファイルの MIME タイプ。未指定時は {@link finfo_file()} 関数により MIME タイプが自動識別されます。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addUploadFile($name, $filePath = NULL, $mimeType = NULL)
  {
    $this->setPostEncoding(self::CONTENT_TYPE_MULTIPART_FORM_DATA);

    if ($filePath === NULL) {
      $this->_files[$name] = array();

    } else {
      if (!Delta_FileUtils::isAbsolutePath($filePath)) {
        $filePath = Delta_FileUtils::buildAbsolutePath($filePath);
      }

      $this->_files[$name]['path'] = $filePath;
      $this->_files[$name]['type'] = $mimeType;
    }

    return $this;
  }

  /**
   * 設定されているファイルデータを削除します。
   *
   * @param string $name 削除対象のパラメータ名。未指定の場合は設定済みの全てのファイルを削除します。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearUploadFile($name = NULL)
  {
    if ($name === NULL) {
      $this->_files = array();
    } else {
      unset($this->_files[$name]);
    }

    return $this;
  }

  /**
   * POST データの送信フォーマットを設定します。
   *
   * @param string $postFormat FORMAT_*** 定数を指定。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPostFormat($postFormat)
  {
    if ($postFormat === self::FORMAT_JSON) {
      $this->addHeader('Content-Type', $postFormat);
      $this->_postFormat = $postFormat;

    } else {
      $message = sprintf('Format is not supported.', $postFormat);
      throw new Delta_UnsupportedException($message);
    }

    return $this;
  }

  /**
   * POST データのエンコード方法を設定します。
   * {@link addUploadFile()} メソッドがコールされた場合、データのエンコーディング形式は 'multipart/form-data' に固定となります。
   *
   * @param string $postEncoding MIME_TYPE_* 定数を指定。既定値は {@link CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENCODED}。
   * @return Delta_HttpRequestSender Delta_HttpRequestSender オブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPostEncoding($postEncoding)
  {
    $this->_postEncoding = $postEncoding;

    return $this;
  }

  /**
   * リクエストする URI を構築します。
   *
   * @return string リクエストする URI を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildRequestURI()
  {
    if (Delta_StringUtils::nullOrEmpty($this->_requestPath)) {
      $requestURI = $this->_baseURI;

    } else {
      $baseURI = rtrim($this->_baseURI, '/');

      if (substr($this->_requestPath, 0, 1) !== '/') {
        $requestPath = '/' . $this->_requestPath;
      } else {
        $requestPath = $this->_requestPath;
      }

      $requestURI = $baseURI . $requestPath;
    }

    if ($this->_requestMethod == Delta_HttpRequest::HTTP_GET) {
      $data = http_build_query($this->_parameters, '', '&');

      if (strpos($requestURI, '?') !== FALSE) {
        $requestURI .= '&' . $data;
      } else {
        $requestURI .= '?' . $data;
      }
    }

    return $requestURI;
  }

  /**
   * サーバにリクエストを送信します。
   *
   * @return Delta_HttpResponseParser {@link Delta_HttpResponseParser} のインスタンスを返します。
   * @throws Delta_ConnectException 指定された URI に接続できなかった場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function send()
  {
    $parameters = $this->_parameters;
    $requestURI = $this->buildRequestURI();
    $parse = parse_url($requestURI);

    if (empty($parse['host'])) {
      throw new Delta_ConnectException('Request URI is not set');
    }

    // ヘッダの構築
    $headers = array();
    $headers[] = sprintf('Host: %s', $parse['host']);

    $hasContentType = FALSE;

    if (sizeof($parameters)) {
      switch ($this->_requestMethod) {
        case Delta_HttpRequest::HTTP_GET:
          $data = http_build_query($parameters, '', '&');
          break;

        case Delta_HttpRequest::HTTP_POST:

          // マルチパート構成
          if ($this->_postEncoding === self::CONTENT_TYPE_MULTIPART_FORM_DATA) {
            $data = NULL;
            $boundary = str_repeat('-', 20) . Delta_StringUtils::buildRandomString();

            // データパートの生成
            foreach ($parameters as $name => $value) {
              $data .= sprintf("--%s\r\n"
                ."Content-Disposition: form-data; name=\"%s\""
                ."\r\n\r\n"
                ."%s\r\n",
                $boundary,
                $name,
                $value);
            }

            // ファイルが含まれる場合
            $fileSize = sizeof($this->_files);

            if ($fileSize) {
              $count = 0;

              foreach ($this->_files as $fileName => $attributes) {
                $count++;
                $hasUploadFile = TRUE;

                if (sizeof($attributes)) {
                  if ($attributes['type'] === NULL) {
                    $info = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($info, $attributes['path']);
                    finfo_close($info);

                  } else {
                    $mimeType = $attributes['type'];
                  }

                  $data .= sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n"
                    ."Content-Type: %s\r\n"
                    ."Content-Transfer-Encoding: binary\r\n\r\n"
                    ."%s\r\n",
                    $fileName,
                    basename($attributes['path']),
                    $mimeType,
                    file_get_contents($attributes['path']));
                } else {
                  $data .= sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"\"\r\n"
                    ."Content-Type: application/octet-stream\r\n\r\n\r\n",
                    $fileName);
                }

                if ($count == $fileSize) {
                  $data .= sprintf("--%s--\r\n", $boundary);
                } else {
                  $data .= sprintf("--%s\r\n", $boundary);
                }

                $this->_options['http']['content'] = $data;
              }

            // MIME パートにファイルが含まれない場合
            } else {
              $data .= sprintf("--%s--\r\n", $boundary);
              $this->_options['http']['content'] = $data;
            }

            $headers[] = 'Content-Type: multipart/form-data; boundary='. $boundary;
            $hasContentType = TRUE;
            break;

          } // end if

        // HTTP_POST (ファイルなし) の場合も実行される
        case Delta_HttpRequest::HTTP_PUT:
          switch ($this->_postFormat) {
            case self::FORMAT_JSON:
              $internalEncoding = Delta_Config::getApplication()->get('charset.default');

              if ($internalEncoding !== 'UTF-8') {
                mb_convert_variables('UTF-8', $internalEncoding, $parameters);
              }

              // see: http://bugs.php.net/bug.php?id=49366
              $data = str_replace('\/', '/', json_encode($parameters));
              break;

            default:
              $data = http_build_query($parameters, '', '&');
              break;
          }

          $this->_options['http']['content'] = $data;
          $headers[] = sprintf('Content-Length: %d', strlen($data));

          break;
      }
    }

    //  ヘッダリストの作成
    foreach ($this->_headers as $name => $value) {
      $headers[] = sprintf('%s: %s', $name, $value);

      if (strcasecmp($name, 'Content-Type') === 0) {
        $hasContentType = TRUE;
      }
    }

    // ヘッダに Content-Type が含まれない場合はデフォルト値を指定
    if (!$hasContentType) {
      if ($this->_requestMethod === Delta_HttpRequest::HTTP_POST) {
        $headers[] = sprintf('Content-Type: %s', $this->_postEncoding);
      } else {
        $headers[] = sprintf('Content-Type: %s', self::CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENCODED);
      }
    }

    // 'header' に関しては添字形式の配列が指定可能とマニュアルにあるが、環境によっては正常に動作しなかったので文字列として格納
    $this->_options['http']['header'] = implode("\r\n", $headers);

    $this->_options['http']['method'] = $this->_requestMethod;
    $this->_options['http']['max_redirects'] = $this->_maxRedirect;
    $this->_options['http']['protocol_version'] = $this->_protocolVersion;
    $this->_options['http']['timeout'] = $this->_readTimeout;
    $this->_options['http']['ignore_errors'] = TRUE; // 4xx、5xx エラーは検知できるが、ホスト名の存在チェックは行われない

    $context = stream_context_create($this->_options);
    $data = @file_get_contents($requestURI, FALSE, $context);

    if (is_array($http_response_header)) {
      return new Delta_HttpResponseParser($http_response_header, $data);

    } else {
      $message = sprintf('Could not connect to URI. [%s]', $this->_baseURI);
      throw new Delta_RequestException($message);
    }
  }
}
