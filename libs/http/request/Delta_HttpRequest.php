<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.request
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/net/agent/Delta_UserAgent.php';

/**
 * クライアントから要求された HTTP リクエストを管理します。
 *
 * このクラスは 'request' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_WebApplication::getRequest()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.request
 */

class Delta_HttpRequest extends Delta_Object
{
  /**
   * HTTP GET リクエスト定数。
   */
  const HTTP_GET = 'GET';

  /**
   * HTTP POST リクエスト定数。
   */
  const HTTP_POST = 'POST';

  /**
   * HTTP PUT リクエスト定数。
   */
  const HTTP_PUT = 'PUT';

  /**
   * HTTP DELETE リクエスト定数。
   */
  const HTTP_DELETE = 'DELETE';

  /**
   * HTTP HEAD リクエスト定数。
   */
  const HTTP_HEAD = 'HEAD';

  /**
   * HTTP OPTIONS リクエスト定数。
   */
  const HTTP_OPTIONS = 'OPTIONS';

  /**
   * BASIC 認証定数。
   */
  const AUTH_TYPE_BASIC = 'BASIC';

  /**
   * DIGEST 認証定数。
   */
  const AUTH_TYPE_DIGEST = 'DIGEST';

  /**
   * ルート情報。
   * @var Delta_Route
   */
  private $_route = FALSE;

  /**
   * アプリケーション設定属性。
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * 入力エンコーディング。
   * @var string
   */
  private $_inputEncoding = 'UTF-8';

  /**
   * GET パラメータ。
   * @var array
   */
  private $_queryData = array();

  /**
   * POST パラメータ。
   * @var array
   */
  private $_postData = array();

  /**
   * リクエスト属性。
   * @var array
   */
  private $_attributes = array();

  /**
   * リクエストヘッダ。
   * @var array
   */
  private $_headers;

  /**
   * エラーコンテキスト。
   * @var array
   */
  private $_error;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_config = Delta_Config::getApplication();

    // リクエストエンコーディングの判定
    if ($this->_config->getBoolean('controller.detectUserAgent')) {
      $encoding = $this->getUserAgent()->getEncoding();
    } else {
      $encoding = $this->_config->getString('charset.default');
    }

    if (sizeof($_GET)) {
      $this->_queryData = $this->escapeXSS($_GET, $encoding);
    }

    if (sizeof($_POST)) {
      $this->_postData = $this->escapeXSS($_POST, $encoding);

    // 'post_max_size' 以上のデータがアップロードされていないか検知する
    } else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $error = error_get_last();

      // PHP が出力するエラーメッセージを破棄
      if ($error) {
        ob_clean();
        $this->_error = $error['message'];
      }
    }

    $this->setInputEncoding($encoding);
  }

  /**
   * セッションオブジェクトを取得します。
   *
   * @return Delta_HttpSession セッションオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSession()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('session');
  }

  /**
   * リクエストデータにエラーが含まれるかどうかチェックします。
   *
   * @return bool リクエストデータにエラーが含まれる場合は TRUE を返します。
   *   サーバ (PHP の 'post_max_size') が許容するアップロードサイズを超えたアップロードを検知した場合は TRUE を返します。
   *   (この場合、PHP の仕様により $_POST は空に初期化される点に注意して下さい)
   *   エラーメッセージは {@link getError()} メソッドで取得することができます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasError()
  {
    $result = FALSE;

    if ($this->_error) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * リクエストに含まれるエラーメッセージを取得します。
   *
   * @return string リクエストに含まれるエラーメッセージを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getError()
  {
    return $this->_error;
  }

  /**
   * 入力エンコーディング形式を設定します。
   * このメソッドは入力エンコーディングが内部エンコーディングと異なる場合、全てのリクエストデータを内部エンコーディング形式に変換します。
   *
   * @param string $inputEncoding 入力エンコーディング形式。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setInputEncoding($inputEncoding)
  {
    $internalEncoding = $this->_config->get('charset.default');

    // 入力データを内部エンコーディング形式に変換する
    if ($inputEncoding !== $internalEncoding) {
      mb_convert_variables($internalEncoding,
        $inputEncoding,
        $this->_queryData,
        $this->_postData);

      if ($this->isPost()) {
        $requestData = $this->_postData;
      } else {
        $requestData = $this->_queryData;
      }

      // @todo 2.0
      exit;
      $form = Delta_ActionForm::getInstance();
      $form->setFields($requestData);
    }

    $this->_inputEncoding = $inputEncoding;
  }

  /**
   * 入力エンコーディングを取得します。
   *
   * @return string 入力エンコーディングを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInputEncoding()
  {
    return $this->_inputEncoding;
  }

  /**
   * 配列の各要素から XSS に繋がる値をエスケープします。
   *
   * @param mixed $value チェック対象の配列。ネスト構造の配列も指定可能。
   * @param string $encoding value のエンコーディング。値のエンコーディングにおける妥当性を検証します。
   *   NULL 指定時はエンコーディングの検証を行いません。
   * @throws Delta_RequestException クライアントから送信されたパラメータに不正な文字が含まれる場合に発生。
   * @return array XSS 対策が施された配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function escapeXSS($value, $encoding = NULL)
  {
    if (is_array($value)) {
      foreach ($value as &$string) {
        $string = $this->escapeXSS($string, $encoding);
      }

    } else {
      // PHP オプション 'magic_quotes_gpc' が有効な場合、シングルクォートやダブルクォート、バックスラッシュ、NULL には全てバックスラッシュでエスケープ処理が入る
      if (get_magic_quotes_gpc()) {
        // バックスラッシュを取り除く
        $value = stripslashes($value);
      }

      // NULL バイト攻撃の対策
      $value = str_replace("\0", '', $value);

      // 不正な改行コードを正しいものに直す
      // クライアントから送信される改行コードは改ざん可能なため、改行コードで文字列分割する処理などが正常に動作しなくなる可能性がある
      $value = Delta_StringUtils::replaceLinefeed($value);

      // エンコーディング形式の検証
      if ($encoding !== NULL && !mb_check_encoding($value, $encoding)) {
        $message = sprintf('Contains an invalid character in the request parameters. [%s]', $value);
        throw new Delta_RequestException($message);
      }
    }

    return $value;
  }

  /**
   * クライアントから指定したヘッダが送信されているかチェックします。
   *
   * @param string $name ヘッダ名。
   * @return bool ヘッダが送信されている場合は TRUE、送信されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasHeader($name)
  {
    if ($this->_headers === NULL) {
      $this->parseHeaders();
    }

    return isset($this->_headers[$name]);
  }

  /**
   * クライアントから送信された HTTP ヘッダを取得します。
   *
   * @param string $name 取得するヘッダ名。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @return mixed name に対応するヘッダを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeader($name, $alternative = NULL)
  {
    if ($this->_headers === NULL) {
      $this->parseHeaders();
    }

    return $this->getRequestValue($this->_headers, $name, $alternative, FALSE);
  }

  /**
   * Content-Type ヘッダを取得します。
   *
   * @return Content-Type ヘッダを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentType()
  {
    return $this->getHeader('Content-Type');
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseHeaders()
  {
    if ($this->_headers == NULL) {
      if (function_exists('apache_request_headers')) {
        $this->_headers = apache_request_headers();

      } else {
        $headers = array();

        foreach($_SERVER as $name => $value) {
          if (substr($name, 0, 5) == 'HTTP_') {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$name] = $value;
          }
        }

        $this->_headers = $headers;
      }
    }
  }

  /**
   * クライアントから送信された全ての HTTP ヘッダを取得します。
   *
   * @return array クライアントから送信された全ての HTTP ヘッダを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaders()
  {
    if ($this->_headers === NULL) {
      $this->parseHeaders();
    }

    return $this->_headers;
  }

  /**
   * サーバ環境変数を取得します。
   *
   * @param string $name 取得する環境変数名。$_SERVER で指定する値と同じ文字列が使用可能。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @return mixed name に対応するサーバ環境変数を返します。name が未指定の場合は配列形式で全てのサーバ環境変数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEnvironment($name = NULL, $alternative = NULL)
  {
    $value = $this->getRequestValue($_SERVER, $name, $alternative, FALSE);

    return $this->escapeXSS($value);
  }

  /**
   * クライアントから要求されたリクエストメソッドを取得します。
   *
   * @return int Delta_HttpRequest::HTTP_* 定数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMethod()
  {
    if (isset($_SERVER['REQUEST_METHOD'])) {
      return $_SERVER['REQUEST_METHOD'];
    }

    return NULL;
  }

  /**
   * GET リクエストが要求されたかどうかをチェックします。
   *
   * @return bool GET リクエストが要求された場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isGet()
  {
    if ($this->getMethod() === 'GET') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * POST リクエストが要求されたかどうかをチェックします。
   *
   * @return bool GET リクエストが要求された場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isPost()
  {
    if ($this->getMethod() === 'POST') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * クライアントが受け入れ可能な文字セットを取得します。
   *
   * @param bool $preferredOnly TRUE を指定した場合、最優先の文字セットのみ返します。
   * @return array クライアントが受け入れ可能な文字セットを返します。
   *   - preferredOnly が TRUE の場合: 文字セットを優先度の高い順にソートした配列形式で返します。
   *   - preferredOnly が FALSE の場合: 最優先の文字セットのみ返します。値が見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAcceptCharset($preferredOnly = FALSE)
  {
    $value = $this->getHeader('Accept-Charset');

    if ($value === NULL) {
      return FALSE;
    }

    $values = $this->splitAcceptHeader($value);

    if ($preferredOnly) {
      return $values[0];
    }

    return $values;
  }

  /**
   * クライアントが受け入れ可能な言語セットを取得します。
   *
   * @param bool $preferredOnly TRUE を指定した場合、最優先の言語セットのみ返します。
   * @return array クライアントが受け入れ可能な言語セットを返します。
   *   - preferredOnly が TRUE の場合: 言語セットを優先度の高い順にソートした配列形式で返します。
   *   - preferredOnly が FALSE の場合: 最優先の言語セットのみ返します。値が見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAcceptLanguage($preferredOnly = FALSE)
  {
    $value = $this->getHeader('Accept-Language');

    if ($value === NULL) {
      return FALSE;
    }

    $values = $this->splitAcceptHeader($value);

    if ($preferredOnly) {
      return $values[0];
    }

    return $values;
  }

  /**
   * クライアントが受け入れ可能なエンコーディングを取得します。
   *
   * @param bool $preferredOnly TRUE を指定した場合、最優先のエンコーディングのみ返します。
   * @return array クライアントが受け入れ可能なエンコーディングを返します。
   *   - preferredOnly が TRUE の場合: エンコーディングを優先度の高い順にソートした配列形式で返します。
   *   - preferredOnly が FALSE の場合: 最優先のエンコーディングのみ返します。値が見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAcceptEncoding($preferredOnly = FALSE)
  {
    $value = $this->getHeader('Accept-Encoding');

    if ($value === NULL) {
      return FALSE;
    }

    $values = $this->splitAcceptHeader($value);

    if ($preferredOnly) {
      return $values[0];
    }

    return $values;
  }

  /**
   * HTTP_ACCEPT_* ヘッダを区切り文字 (,) で分割し、フィールドを品質値の高い順でソートします。
   *
   * @param string $header HTTP_ACCEPT_* ヘッダ。
   * @return array 品質値の高い順でソートし直したフィールドのリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function splitAcceptHeader($header)
  {
    $accepts = array();
    $explode = explode(',', $header);

    foreach ($explode as $accept) {
      $acceptData = explode(';', $accept);

      if (sizeof($acceptData) > 1) {
        $encoding = array_shift($acceptData);
        $attributes = array();

        foreach ($acceptData as $data) {
          $explode = explode('=', $data);
          $name = trim($explode[0]);
          $value = trim($explode[1]);

          $attributes[$name] = $value;
        }

        $attributes['_encoding'] = trim($encoding);

        if (!isset($attributes['q'])) {
          $attributes['q'] = 1.0;
        }

        $accepts[] = $attributes;

      } else {
        // 品質が未指定の場合は 1.0 をデフォルトとする
        $accepts[] = array('_encoding' => trim($accept), 'q' => 1.0);
      }
    }

    // arsort() を使うと優先度が同じ値が存在する場合に順序が逆になってしまうので使用しない
    // ('utf-8;q=0.7,*;q=0.7' の場合、arsort() を使うと順序が *、utf-8 となる)
    usort($accepts, array($this, 'sortAcceptPriority'));
    $values = array();

    foreach ($accepts as $value) {
      $values[] = $value['_encoding'];
    }

    return $values;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function sortAcceptPriority($compare1, $compare2)
  {
    $result = -1;

    // 品質が同じ場合は先に指定された値 ($compare1) を優先する
    if ($compare1['q'] <= $compare2['q']) {
      $result = 1;
    }

    return $result;
  }

  /**
   * クライアントから要求された GET パラメータを取得します。
   *
   * @param string $name 取得する GET パラメータ名。
   *   同じ名前のパラメータが複数要求された場合は、一番最後に渡された値が取得されます。
   *   全てのパラメータを取得したい場合は {@link parseRawData()} メソッドを使用して下さい。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @param bool $emptyToAlternative name の値が空文字の時に alternative を返したい場合は TRUE を指定。
   * @return mixed name に対応する GET パラメータを返します。name が未指定の場合は配列形式で全ての GET パラメータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getQuery($name = NULL, $alternative = NULL, $emptyToAlternative = FALSE)
  {
    return $this->getRequestValue($this->_queryData, $name, $alternative, $emptyToAlternative);
  }

 /**
   * 指定した GET パラメータが要求されているかどうかチェックします。
   *
   * @param string $name チェック対象の GET パラメータ名。
   * @return bool パラメータ name が GET リクエストされている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasQuery($name)
  {
    return isset($this->_queryData[$name]);
  }

  /**
   * クライアントから要求された生のリクエストデータを解析し、{@link Delta_ParameterHolder} オブジェクトに変換します。
   * このメソッドは、'?id=100&id=200' のようにパラメータ名が同じデータを配列形式として取得したい場合に有効です。
   * また、リクエストが JSON (Content-Type が 'application/json') 形式の場合は、デコードした結果を返します。
   *
   * @param string $method リクエストメソッドの形式。{@link Delta_HttpRequest::HTTP_GET}、{@link Delta_HttpRequest::HTTP_POST} のいずれかを指定。
   * @return Delta_ParameterHolder パラメータホルダのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parseRawData($method = self::HTTP_GET)
  {
    if ($method == self::HTTP_GET) {
      $data = $this->getEnvironment('QUERY_STRING');
    } else {
      $data = file_get_contents("php://input");
    }

    $parse = Delta_MIME::parse($this->getContentType());

    if ($parse['content'] === 'application/json') {
      $data = json_decode($data, TRUE);

    } else {
      $data = Delta_URIUtils::buildQueryAssoc($data);
    }

    $hash = $this->escapeXSS($data, $this->_inputEncoding);
    $holder = new Delta_ParameterHolder($hash);

    return $holder;
  }

  /**
   * クライアントから要求された POST パラメータを取得します。
   *
   * @param string $name 取得する POST パラメータ名。
   *   同じ名前のパラメータが複数要求された場合は、一番最後に渡された値が取得されます。
   *   全てのパラメータを取得したい場合は {@link parseRawData()} メソッドを使用して下さい。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @param bool $emptyToAlternative name の値が空文字の時に alternative を返したい場合は TRUE を指定。
   * @return mixed name に対応する POST パラメータを返します。name が未指定の場合は配列形式で全ての POST パラメータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPost($name = NULL, $alternative = NULL, $emptyToAlternative = FALSE)
  {
    return $this->getRequestValue($this->_postData, $name, $alternative, $emptyToAlternative);
  }

  /**
   * 指定した POST パラメータが要求されているかどうかチェックします。
   *
   * @param string $name チェック対象の POST パラメータ名。
   * @return bool パラメータ name が POST リクエストされている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasPost($name)
  {
    return isset($this->_postData[$name]);
  }

  /**
   * リクエストされたルート情報を設定します。
   *
   * @param Delta_Route $route リクエストルート。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRoute(Delta_Route $route)
  {
    $this->_route = $route;
  }

  /**
   * リクエストされたルート情報を取得します。
   *
   * @return Delta_Route リクエストされたルート情報を返します。
   *   ルートが未確定の場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRoute()
  {
    return $this->_route;
  }

  /**
   * URI に含まれるパスホルダパラメータを取得します。
   *
   * @param string $name 取得するパスホルダパラメータ名。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @param bool $emptyToAlternative name の値が空文字の時に alternative を返したい場合は TRUE を指定。
   * @return mixed name に対応するパスホルダパラメータを返します。
   *   name が未指定の場合は配列形式で全てのパスホルダパラメータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPathHolder($name = NULL, $alternative = NULL, $emptyToAlternative = TRUE)
  {
    return $this->getRequestValue($this->_route->getPathHolder(), $name, $alternative, $emptyToAlternative);
  }

  /**
   * 指定したパスホルダパラメータがリクエスト URI に含まれているかどうかチェックします。
   *
   * @param string $name チェック対象のパスホルダパラメータ名。
   * @return bool パスホルダパラメータ name がリクエスト URI に含まれている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasPathHolder($name)
  {
    $bindings = $this->_route->getPathHolder();

    return isset($bindings[$name]);
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getRequestValue($data, $name, $alternative, $emptyToAlternative = FALSE)
  {
    if ($name === NULL) {
      return $data;

    } else {
      $data = Delta_ArrayUtils::find($data, $name);

      if ($data !== NULL) {
        if ($data === '' && $emptyToAlternative) {
          return $alternative;

        } else {
          return $data;
        }
      }
    }

    return $alternative;
  }

  /**
   * クライアントから要求されたパラメータを取得します。
   * {@link getParameters()} メソッドの項も参照して下さい。
   *
   * @param string $name 取得対象のパラメータ名。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @param bool $emptyToAlternative name の値が空文字の時に alternative を返したい場合は TRUE を指定。
   * @return mixed name に対応するパラメータを返します。
   * @see Delta_HttpRequest::getParameters()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getParameter($name, $alternative = NULL, $emptyToAlternative = FALSE)
  {
    $data = NULL;

    if ($this->hasQuery($name)) {
      $data = $this->getQuery($name, $alternative, $emptyToAlternative);

    } else if ($this->hasPost($name)) {
      $data = $this->getPost($name, $alternative, $emptyToAlternative);

    } else {
      $data = $alternative;
    }

    return $data;
  }

  /**
   * クライアントから要求された全てのパラメータを取得します。
   * パラメータには GET、POST、パスホルダパラメータのデータを含みますが、パラメータ間で重複する名前は GET、POST の順で値がマージされます。
   *
   * @return mixed クライアントから要求された全てのパラメータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getParameters()
  {
    static $parameters = NULL;

    if ($parameters === NULL) {
      $parameters = $this->_queryData + $this->_postData;
    }

    return $parameters;
  }

  /**
   * 指定したパラメータがクライアントから要求されているかどうかチェックします。
   * チェック対象となるパラメータは GET、POST、パスホルダパラメータになります。
   *
   * @param string $name チェック対象のパラメータ名。
   * @return bool パラメータ name がクライアントから要求されている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasParameter($name)
  {
    return array_key_exists($name, $this->getParameters());
  }

  /**
   * クライアントから要求されたリクエストがセキュアチャネル (HTTPS) ベースで行われているかチェックします。
   *
   * @return bool セキュアな通信が行われている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isSecure()
  {
    if ((isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') == 0) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') == 0)) {

      return TRUE;
    }

    return FALSE;
  }

  /**
   * リクエストオブジェクトに任意の属性を設定します。
   *
   * @param string $name リクエスト属性の名。
   * @param mixed $value リクエスト属性の値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttribute($name, $value)
  {
    $this->_attributes[$name] = $value;
  }

  /**
   * リクエストオブジェクトに設定されている属性を取得します。
   *
   * @param string $name 取得対象の属性名。
   * @param string $alternative 属性が存在しない場合に返す代替値。
   * @return mixed name に対応する属性を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttribute($name, $alternative = NULL)
  {
    if (isset($this->_attributes[$name])) {
      return $this->_attributes[$name];
    }

    return $alternative;
  }

  /**
   * リクエストオブジェクトに設定されている全ての属性を取得します。
   *
   * @return array リクエストオブジェクトに設定されている全ての属性を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }

  /**
   * リクエストオブジェクトに属性が設定されているかチェックします。
   *
   * @param string $name チェック対象の属性名。
   * @return bool 属性が登録されている場合は TRUE、登録されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttribute($name)
  {
    return isset($this->_attributes[$name]);
  }

  /**
   * リクエストオブジェクトに設定されている全ての属性を破棄します。
   *
   * @param string $name 破棄する属性名。未指定の場合は設定済みの全ての属性を破棄します。
   * @return bool 対象属性が破棄された場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearAttribute($name = NULL)
  {
    if ($name !== NULL) {
      if (isset($this->_attributes[$name])) {
        unset($this->_attributes[$name]);

      } else {
        return FALSE;
      }

    } else {
      unset($this->_attributes);
    }

    return TRUE;
  }

  /**
   * アプリケーションのベースとなる (パスやクエリを含まない) URL を取得します。
   *
   * @param bool $secure ベース URL を 'https' プロトコル形式で返す場合は TRUE、'http' プロトコル形式で返す場合は FALSE を指定。
   * @return string アプリケーションのベース URL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @since 1.1
   */
  public function getBaseURL($secure = FALSE)
  {
    if ($secure) {
      $baseUrl = sprintf('https://%s/', $this->getHost());
    } else {
      $baseUrl = sprintf('http://%s/', $this->getHost());
    }

    return $baseUrl;
  }

  /**
   * クライアントから要求されたリクエスト URL (http から始まるアドレス) を取得します。
   *
   * @param bool $withQuery URL にクエリパラメータを含める場合は TRUE、含めない場合は FALSE を指定。
   * @return string クライアントから要求されたリクエスト URL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getURL($withQuery = TRUE)
  {
    return $this->buildPath(TRUE, $withQuery);
  }

  /**
   * クライアントから要求されたリクエスト URI (ホスト名の後に続くパス) を取得します。
   *
   * @param bool $withQuery URI にクエリパラメータを含める場合は TRUE、含めない場合は FALSE を指定。
   * @return string クライアントから要求されたリクエスト URL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getURI($withQuery = TRUE)
  {
    return $this->buildPath(FALSE, $withQuery);
  }

  /**
   *
   *
   * @param bool $absolute
   * @param bool $withQuery
   * @return string
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildPath($absolute, $withQuery)
  {
    $requestURI = $this->getEnvironment('REQUEST_URI');

    if ($absolute) {
      $requestURI = sprintf('%s://%s%s', $this->getScheme(), $this->getHost(), $requestURI);
    }

    if (!$withQuery && ($pos = strpos($requestURI, '?')) !== FALSE) {
      $requestURI = substr($requestURI, 0, $pos);
    }

    return $requestURI;
  }

  /**
   * サーバのホスト名を取得します。
   *
   * @return string サーバのホスト名を返します。ホスト名が不明な場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHost()
  {
    return $this->getEnvironment('HTTP_HOST');
  }

  /**
   * クライアントのホスト名を取得します。
   *
   * @param bool $proxy {@link Delta_HttpRequest::getRemoteAddress()} メソッドを参照。
   * @return string クライアントのリモートホスト名を返します。ホスト名の解析に失敗した場合は代わりにリモート IP アドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRemoteHost($proxy = FALSE)
  {
    // プロキシ経由の場合、アクセス元のホスト名が HTTP_X_FORWARDED_FOR に格納される
    $forwardedFor = $this->getEnvironment('HTTP_X_FORWARDED_FOR');

    // 本来のホスト名を取得
    if ($forwardedFor !== NULL && !$proxy) {
      $remoteHost = $forwardedFor;

    // プロキシのホスト名を取得
    } else {
      $remoteHost = $this->getEnvironment('REMOTE_HOST');
    }

    $remoteAddress = $this->getRemoteAddress(FALSE, $proxy);

    if ($remoteHost === NULL) {
      return gethostbyaddr($remoteAddress);

    } else {
      if ($remoteHost == $remoteAddress) {
        return gethostbyaddr($remoteAddress);

      } else {
        return $remoteHost;
      }
    }
  }

  /**
   * クライアントが HTTP 認証済みの場合にログイン ID を取得します。
   *
   * @return string クライアントのログイン ID を返します。未認証の場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRemoteUser()
  {
    if (isset($_SERVER['REMOTE_USER'])) {
      return $_SERVER['REMOTE_USER'];
    }

    return NULL;
  }

  /**
   * クライアントから要求された HTTP 認証の形式を取得します。
   *
   * @return int 認証形式定数 AUTH_TYPE_* を返します。認証が要求されていない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAuthType()
  {
    if (isset($_SERVER['AUTH_TYPE'])) {
      $authType = strtoupper($_SERVER['AUTH_TYPE']);

      if ($authType == self::AUTH_TYPE_BASIC) {
        return self::AUTH_TYPE_BASIC;
      } else if ($authType == self::AUTH_TYPE_DIGEST) {
        return self::AUTH_TYPE_DIGEST;
      }
    }

    return NULL;
  }

  /**
   * クライアントの IP アドレスを取得します。
   *
   * @param bool $ip2long TRUE を指定した場合、取得した IP アドレスを数値形式に変換します。
   * @param bool $proxy クライアントがプロキシ経由での接続時に実クライアント IP、プロキシサーバ IP のどちらを返すかを指定。
   *   FALSE 指定時は実 IP アドレスを取得します。
   *   但し、proxy の設定は全てのプロキシサーバに対応している訳ではない点に注意して下さい。
   *   匿名プロキシサーバの種類によっては、実 IP としてプロキシサーバの IP を返す場合があります。
   * @return string クライアントの IP アドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRemoteAddress($ip2long = FALSE, $proxy = FALSE)
  {
    // Squid や Traffic-Server が出力するヘッダ
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !$proxy) {
      $address = $_SERVER['HTTP_CLIENT_IP'];

    // NetCsche が出力するヘッダ
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !$proxy) {
      $address = $_SERVER['HTTP_X_FORWARDED_FOR'];

    } else {
      $address = $this->getEnvironment('REMOTE_ADDR');
    }

    if ($ip2long) {
      return (string) Delta_NetworkUtils::convertIPToInteger($address);
    }

    return $address;
  }

  /**
   * クライアントのリファラー URI を取得します。
   *
   * @return string クライアントのリファラー URI を返します。URI が見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getReferrer()
  {
    // field is misspelled (RFC2616)
    return $this->getEnvironment('HTTP_REFERER');
  }

  /**
   * {@link Delta_HttpRequest::getReferrer()} へのエイリアスです。
   * <i>'Referer' は {@link http://www.ietf.org/rfc/rfc2616.txt RFC2616} におけるスペルミスであり、表記としては正しくありません。</i>
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getReferer()
  {
    return $this->getReferrer();
  }

  /**
   * 指定した Cookie がクライアントに設定されているかどうかチェックします。
   *
   * @param string $name チェック対象の Cookie 名。
   * @return bool Cookie がクライアントに設定されている場合は TRUE、設定されていない場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasCookie($name)
  {
    return isset($_COOKIE[$name]);
  }

  /**
   * クライアントから送信された Cookie を取得します。
   *
   * @param string $name 取得する Cookie 名。
   * @param string $alternative 値が存在しない場合に返す代替値。
   * @return mixed name に対応する Cookie を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCookie($name, $alternative = NULL)
  {
    $value = $this->getRequestValue($_COOKIE, $name, $alternative, FALSE);

    return $this->escapeXSS($value, $this->_inputEncoding);
  }

  /**
   * クライアントから送信された全ての Cookie を取得します。
   *
   * @return array クライアントから送信された全ての Cookie を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCookies()
  {
    return $this->escapeXSS($_COOKIE);
  }

  /**
   * クライアントから要求されたスキーム ('http'、または 'https') を取得します。
   *
   * @return string クライアントから要求されたスキームを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getScheme()
  {
    if ($this->isSecure()) {
      $scheme = 'https';
    } else {
      $scheme = 'http';
    }

    return $scheme;
  }

  /**
   * クライアントのユーザエージェントを取得します。
   *
   * @return Delta_UserAgentAdapter Delta_UserAgentAdapter のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUserAgent()
  {
    static $adapter = NULL;

    if ($adapter === NULL) {
      $userAgent = $this->getEnvironment('HTTP_USER_AGENT');
      $adapter = Delta_UserAgent::getInstance()->getAdapter($userAgent);
    }

    return $adapter;
  }

  /**
   * リクエストされた URI に含まれるサブドメイン部を取得します。
   *
   * @param int $tld TLD (Top Level Domain) の数を指定。規定値は 1。
   * @return string サブドメイン部を返します。リクエスト URI にサブドメインが含まれない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSubdomain($tld = 1)
  {
    $serverName = $this->getEnvironment('SERVER_NAME');
    $names = explode('.', $serverName);

    $append = NULL;
    $size = sizeof($names) - 1 - $tld;

    if ($size) {
      for ($i = 0; $i < $size; $i++) {
        $append .= $names[$i] . '.';
      }

      return rtrim($append, '.');
    }

    return NULL;
  }

  /**
   * クライアントから AJAX リクエストが送信されたかチェックします。
   * AJAX リクエストの判定は、クライアントプログラムから送信される 'X-Requested-With: XMLHttpRequest' ヘッダに依存します。
   *
   * 次に示すライブラリは標準で 'X-requested-with' ヘッダを送信します。
   *   - jQuery
   *   - prototype
   *   - YUI
   *   - Dojo
   *
   * @return bool クライアントから AJAX リクエストが送信された場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAjax()
  {
    if ($this->getEnvironment('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * リクエストアクセスがローカルネットワークから行われたものかチェックします。
   * ローカルネットワークの範囲はクラス A、B、C、及びローカルループバックアドレスとします。
   *   - 127.0.0.1-127.255.255.254
   *   - 192.168.0.0-192.168.255.255
   *   - 172.16.0.0-172.31.255.255
   *   - 10.0.0.0-10.255.255.255
   *
   * @return bool リクエストがローカルネットワークからのアクセスの場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isLocal()
  {
    $remoteAddress = $this->getRemoteAddress();

    $range[] = array();
    $range[] = '127.0.0.1-127.255.255.254';
    $range[] = '192.168.0.0-192.168.255.255';
    $range[] = '172.16.0.0-172.31.255.255';
    $range[] = '10.0.0.0-10.255.255.255';

    if (Delta_NetworkUtils::hasContainNetwork($range, $remoteAddress)) {
      return TRUE;
    }

    return FALSE;
  }
}
