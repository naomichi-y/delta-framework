<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.response
 * @copyright Copyright (c) delta framework project.
 */

/**
 * Delta_HttpRequestSender から発行したリクエストに対応するレスポンスを解析します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.response
 */

class Delta_HttpResponseParser extends Delta_Object
{
  /**
   * 生の応答ヘッダ。
   */
  private $_rawHeader = array();

  /**
   * 応答ヘッダリスト。
   * @var array
   */
  private $_headers;

  /**
   * 応答データ。
   * @var string
   */
  private $_contents;

  /**
   * 応答プロトコルバージョン。
   * @var float
   */
  private $_protocolVersion;

  /**
   * 応答ステータス。
   * @var int
   */
  private $_status;

  /**
   * リダイレクト回数。
   * @var int
   */
  private $_redirectCount = -1;

  /**
   * コンストラクタ。
   *
   * @param array $headers ヘッダ配列。
   *   array('HTTP/1.1 200 OK', 'Content-Type: text/html', ...) の形式で指定。
   * @param string $contents コンテンツパート。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $headers, $contents)
  {
    $this->_contents = $contents;
    $this->_rawHeader = $headers;

    $this->parseHeader($headers);
  }

  /**
   * レスポンスヘッダを解析します。
   *
   * @param array $headers ヘッダ配列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseHeader(array $headers)
  {
    foreach ($headers as $header) {
      $pos = strpos($header, ':');

      if ($pos === FALSE) {
        $array = explode(' ', strtolower($header), 2);

        $this->_protocolVersion = (float) substr($array[0], strpos($array[0], '/') + 1);
        $this->_status = (int) substr($array[1], 0, strpos($array[1], ' '));

        $this->_redirectCount++;

      } else {
        $name = substr($header, 0, $pos);
        $value = substr($header, $pos + 1);

        if (isset($this->_headers[$this->_redirectCount][$name])) {
          if (is_array($this->_headers[$this->_redirectCount][$name])) {
            array_push($this->_headers[$this->_redirectCount][$name], trim($value));

          } else {
            $array = array($this->_headers[$this->_redirectCount][$name], trim($value));
            $this->_headers[$this->_redirectCount][$name] = $array;
          }

        } else {
          $this->_headers[$this->_redirectCount][$name] = trim($value);
        }
      }
    }
  }

  /**
   * 応答コンテンツのエンコーディングを取得します。
   * 初めに Content-Type ヘッダを調べ、ヘッダが存在しない場合はレスポンスデータに含まれる META を解析します。
   *
   * @return string エンコーディングのタイプを返します。解析できなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEncoding()
  {
    $contentType = $this->getHeader('Content-Type');
    $regexp = '/charset=([0-9a-zA-Z\-_]+)/i';

    preg_match($regexp, $contentType, $matches);

    if (sizeof($matches)) {
      return $matches[1];
    }

    preg_match($regexp, $this->_contents, $matches);

    if (sizeof($matches)) {
      return $matches[1];
    }

    return FALSE;
  }

  /**
   * 応答レスポンスにおける HTTP プロトコルのバージョンを取得します。
   *
   * @return float HTTP プロトコルのバージョンを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getProtocolVersion()
  {
    return $this->_protocolVersion;
  }

  /**
   * 応答レスポンスにおける HTTP ステータスコードを取得します。
   *
   * @return int HTTP ステータスコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getStatus()
  {
    return $this->_status;
  }

  /**
   * 生のレスポンスヘッダを取得します。
   *
   * @return array 生のレスポンスヘッダを返します。
   *   返される値は array('HTTP/1.1 200 OK', 'Content-Type: text/html', ...) という形式になります。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRawHeader()
  {
    return $this->_rawHeader;
  }

  /**
   * ヘッダ配列を取得します。
   *
   * @param int $count レスポンスヘッダはリダイレクト単位で紐付いています。
   *   例えばリクエスト送信後に 1 回のリダイレクトが行われた場合、0 を指定した場合はリダイレクト応答ヘッダ、1 を指定した場合は最終応答ヘッダが返されます。
   *   未指定の場合は最終応答ヘッダを返します。
   * @return array ヘッダ名をキーとする連想配列を返します。(ヘッダ値は配列で構成される可能性もあります)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaders($count = NULL)
  {
    if ($count === NULL) {
      $count = $this->_redirectCount;
    }

    return $this->_headers[$count];
  }

  /**
   * 指定されたヘッダ名に対応する値を取得します。
   * このメソッドは最終応答ヘッダ情報のみ返します。
   * リダイレクト中のレスポンスヘッダを参照する場合は、{@link getHeaders()} メソッドを使用して下さい。
   *
   * @param string $name 取得対象のヘッダ名。
   * @return mixed ヘッダの値を返します。同じ名前で複数の値が設定されてる場合は配列を返す場合があります。
   *   また、該当するヘッダが見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeader($name)
  {
    $headers = $this->_headers[$this->_redirectCount];

    if (!is_array($headers)) {
      return NULL;
    }

    foreach ($headers as $headerName => $headerValue) {
      if (strcasecmp($name, $headerName) === 0) {
        return $headerValue;
      }
    }

    return NULL;
  }

  /**
   * Cookie を取得します。
   *
   * @return array Cookie 配列を返します。 Cookie が設定されていない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCookies()
  {
    $value = $this->getHeader('Set-Cookie');

    if (is_string($value)) {
      return array($value);
    }

    return $value;
  }

  /**
   * Cookie の値を返します。
   * このメソッドは、値以外の属性 (expires、domain、path、secure など) を返さないことに注意して下さい。
   *
   * @param string $name 取得する Cookie 名。未指定の場合は全ての値を結合した文字列を返します。
   * @return string Cookie 文字列を返します。Cookie が設定されていない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCookie($name = NULL)
  {
    $cookies = $this->getCookies();

    if (is_array($cookies)) {
      $string = NULL;

      foreach ($cookies as $cookie) {
        $array = explode(';', $cookie);

        if ($name === NULL) {
          $string .= $array[0] . '; ';
        } else if (strpos($array[0], $name . '=') === 0) {
          $string = substr($array[0], strlen($name) + 1);
          break;
        }
      }

      return rtrim($string);
    }

    return NULL;
  }

  /**
   * Content-Type ヘッダの値を取得します。
   *
   * @return string Content-Type ヘッダの値を返します。
   * @see Delta_HttpResponseParser::getHeader()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentType()
  {
    return $this->getHeader('Content-Type');
  }

  /**
   * 応答コンテンツを取得します。
   * コンテンツが gzip や deflate で圧縮されてる場合は伸張を試みます。
   *
   * @param bool $convertEncoding コンテンツのエンコーディングを、強制的にアプリケーションのデフォルトエンコーディングに変換します。
   *   <i>(Shift_JIS でエンコードされたコンテンツ内に機種依存文字が含まれる場合、コンテンツの内容を正しく解析できない可能性があります)</i>
   * @return string 応答コンテンツを返します。
   * @throws RuntimeException 圧縮されたコンテンツを伸張するためのライブラリが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContents($convertEncoding = FALSE)
  {
    $contents = $this->_contents;
    $contentEncoding = $this->getHeader('Content-Encoding');

    // コンテンツが圧縮されてる場合は伸張
    if ($contentEncoding === 'gzip' || $contentEncoding === 'deflate') {
      if (!extension_loaded('zlib')) {
        $message = 'Compressed content is received. but find the zlib library to decompress.';
        throw new RuntimeException($message);

      } else if ($contentEncoding === 'gzip') {
        $contents = Delta_StringUtils::decodeGzip($contents);
      } else {
        $contents = gzuncompress($contents);
      }
    }

    if ($convertEncoding) {
      $sourceEncoding = $this->getEncoding();
      $destinationEncoding = Delta_Config::getApplication()->get('charset.default');

      if (strcasecmp($sourceEncoding, $destinationEncoding) !== 0) {
        $contents = mb_convert_encoding($contents, $destinationEncoding, $sourceEncoding);
      }
    }

    return $contents;
  }

  /**
   * 応答データを JSON 形式でデコードし、JSON 配列またはオブジェクトを取得します。
   * このメソッドはレスポンスヘッダに含まれる Content-Type をチェックする点に注意して下さい。
   * サポートされる Content-Type は次の通りです。
   *   - application/json
   *   - text/javascript
   * @param bool $assoc JSON 文字列を配列として返す場合は TRUE、stdClass オブジェクトとして返す場合は FALSE を指定。
   * @return mixed JSON データを返します。assoc の指定により返されるデータ型が変化します。
   *   データが JSON 形式として見なされなかった場合は FALSE を返します。
   * @see Delta_HttpResponseParser::getContents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getJSONData($assoc = FALSE)
  {
    $mimeType = preg_replace('/(.+);.*/', '\1', $this->getContentType());
    $mimeType = strtolower($mimeType);

    $array = array();
    $array[] = 'application/json';
    $array[] = 'text/javascript';

    if (in_array($mimeType, $array)) {
      return json_decode($this->getContents(), $assoc);
    }

    return FALSE;
  }

  /**
   * 応答データを XML 形式でデコードし、SimpleXMLElement オブジェクトを取得します。
   * このメソッドはレスポンスヘッダに含まれる Content-Type をチェックする点に注意して下さい。
   * サポートされる Content-Type は次の通りです。
   *   - +xml 接頭辞が付加されている ({@link http://tools.ietf.org/html/rfc3023 RFC 3023})
   *   - application/xml
   *   - text/xml
   *
   * @return SimpleXMLElement XML データを返します。
   *   データが XML 形式として見なされなかった場合は FALSE を返します。
   * @see Delta_HttpResponseParser::getContents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getXMLData()
  {
    $mimeType = preg_replace('/(.+);.*/', '\1', $this->getContentType());
    $mimeType = strtolower($mimeType);
    $isXML = FALSE;

    if (strpos($mimeType, '+xml') !== FALSE) {
      $isXML = TRUE;

    } else {
      // 非推奨
      $array = array();
      $array[] = 'application/xml';
      $array[] = 'text/xml';

      if (in_array($mimeType, $array)) {
        $isXML = TRUE;
      }
    }

    if ($isXML) {
      return simplexml_load_string($this->getContents());
    }

    return FALSE;
  }

  /**
   * リダイレクト実行回数を取得します。
   *
   * @return int リダイレクト実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRedirectCount()
  {
    return $this->_redirectCount;
  }
}
