<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * メールのメッセージパートを管理します。
 *
 * このクラスは実験的なステータスにあります。
 * これは、このクラスの動作、メソッド名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 */
class Delta_MailPart extends Delta_Object
{
  /**
   * メールパートリスト。
   * @var array
   */
  private $_parts = array();

  /**
   * ヘッダリスト。
   * @var array
   */
  private $_headers = array();

  /**
   * Content-Type。
   * @var string
   */
  private $_contentType = 'text/plain';

  /**
   * Content-Type に設定されているパラメータリスト。
   * @var array
   */
  private $_contentTypeParameters = array();

  /**
   * エンコーディング方式。
   * @var string
   */
  private $_charset = 'US-ASCII';

  /**
   * メール本文。
   * @var string
   */
  private $_body;

  /**
   * Delta_MailAttachment リスト。
   * @var array
   */
  private $_attachmentFiles = array();

  /**
   * 改行コード。
   * @var string
   */
  protected $_lineFeed;

  /**
   * コンストラクタ。
   *
   * @param string $lineFeed パートの改行コード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($lineFeed = "\r\n")
  {
    $this->_lineFeed = $lineFeed;
  }

  /**
   * MIME パートを追加します。
   *
   * @param Delta_MailPart $parentPart 追加するパート。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addPart(Delta_MailPart $part)
  {
    $this->_parts[] = $part;
  }

  /**
   * MIME パートリストを取得します。
   *
   * @return array Delta_MailPart オブジェクト配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getParts()
  {
    return $this->_parts;
  }

  /**
   * メッセージが MIME 構成かチェックします。
   *
   * @return bool メッセージが MIME 構成の場合は TRUE、単純なテキストメッセージ形式の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasPart()
  {
    if (sizeof($this->_parts)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * ヘッダを追加します。
   *
   * @param string $name 追加対象のヘッダ名。既に同じヘッダが登録されてる場合は値を上書きします。大文字小文字は区別されません。
   * @param string $value ヘッダ値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addHeader($name, $value)
  {
    $this->removeHeader($name);
    $this->_headers[$name] = $value;
  }

  /**
   * 指定したヘッダを削除します。
   *
   * @param string $name 削除対象のヘッダ名。大文字小文字は区別されません。
   * @return bool ヘッダの削除に成功した場合は TRUE、ヘッダが存在しなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeHeader($name)
  {
    $headers = &$this->_headers;

    foreach ($headers as $searchName => $searchValue) {
      if (strcasecmp($searchName, $name) === 0) {
        unset($headers[$searchName]);

        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * 指定したヘッダ名が追加済みであるかどうかチェックします。
   *
   * @param string $name チェック対象のヘッダ名。大文字小文字は区別されません。
   * @return bool ヘッダが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasHeader($name)
  {
    $headers = $this->_headers;

    foreach ($headers as $searchName => $searchValue) {
      if (strcasecmp($searchName, $name) === 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * ヘッダリストを設定します。
   *
   * @param array $headers 名前と値で構成されるヘッダ配列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setHeaders(array $headers)
  {
    $this->_headers = $headers;
  }

  /**
   * ヘッダリストを取得します。
   *
   * @return array ヘッダ配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaders()
  {
    return $this->_headers;
  }

  /**
   * 指定したヘッダ名に対応する値を取得します。
   * 同一ヘッダが複数存在する場合は、一番最初に定義されているヘッダを読み込みます。
   *
   * @param string $name ヘッダ名。
   * @return string ヘッダ名に対応する値を返します。値が存在しない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaderValue($name)
  {
    $values = $this->getHeaderValues($name);

    if (is_array($values)) {
      return $values[0];
    }

    return $values;
  }

  /**
   * 指定したヘッダ名に対応する値を取得します。
   * 同一ヘッダが複数存在する場合は、値をリストとして取得します。
   *
   * @param string $name ヘッダ名。
   * @return array ヘッダ名に対応する値のリストを返します。値が存在しない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getHeaderValues($name)
  {
    $name = strtolower($name);
    $headers = $this->_headers;

    foreach ($headers as $headerName => $headerValue) {
      if (strcasecmp($headerName, $name) === 0) {
        return $headerValue;
      }
    }

    return NULL;
  }

  /**
   * Content-Type ヘッダを設定します。
   * Content-Type のオプションパラメータを設定する場合は、{@link setContentTypeParameters()} メソッドを使用して下さい。
   *
   * @param string $contentType Content-Type ヘッダ。(メディアタイプのみ)
   * @param array $parameters
   * @param bool $buildHeader
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @todo $parameters、$buildHeader 追加
   */
  public function setContentType($contentType, array $parameters = NULL, $buildHeader = FALSE)
  {
    $buffer = $contentType;

    if ($parameters) {
      foreach ($parameters as $name => $value) {
        $name = strtolower($name);
        $this->_contentTypeParameters[$name] = $value;

        if ($buildHeader) {
          $buffer .= '; ' . $name . '="' . $value . '"';
        }
      }
    }

    if ($buildHeader) {
      $this->addHeader('Content-Type', $buffer);
    }

    $this->_contentType = $contentType;
  }

  /**
   * Content-Type ヘッダを取得します。
   * Content-Type のオプションパラメータを取得する場合は、{@link getContentTypeParameters()} メソッドを使用して下さい。
   *
   * @param bool $typeOnly TRUE を指定するとタイプのみを返します。(サブタイプを返しません)
   * @return string Content-Type ヘッダ (メディアタイプのみ) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentType($typeOnly = FALSE)
  {
    $contentType = $this->_contentType;

    if ($typeOnly && ($pos = strpos($contentType, '/')) !== FALSE) {
      return substr($contentType, 0, $pos);
    }

    return $contentType;
  }

  /**
   * Content-Type ヘッダのオプションパラメータリストを取得します。
   *
   * @return array Content-Type ヘッダのオプションパラメータを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentTypeParameters()
  {
    return $this->_contentTypeParameters;
  }

  /**
   * Content-Type ヘッダのパラメータ値を取得します。
   *
   * @param string $name パラメータ名。
   * @return string パラメータに対応する値を取得します。値が存在しない場合は NULL を返します。
   */
  public function getContentTypeParameter($name)
  {
    $name = strtolower($name);

    if (isset($this->_contentTypeParameters[$name])) {
      return $this->_contentTypeParameters[$name];
    }

    return NULL;
  }

  /**
   * Content-Type ヘッダの文字セットパラメータを設定します。
   *
   * @param string $charset 文字セット。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCharset($charset)
  {
    $this->_charset = $charset;
  }

  /**
   * Content-Type ヘッダの文字セットパラメータを取得します。
   *
   * @return string 文字セットを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCharset()
  {
    return $this->_charset;
  }

  /**
   * Content-Transfer-Encoding ヘッダに値を設定します。
   *
   * @param string $contentTransferEncoding Content-Transfer-Encoding ヘッダに設定する値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setContentTransferEncoding($contentTransferEncoding)
  {
    $this->addHeader('Content-Transfer-Encoding', $contentTransferEncoding);
  }

  /**
   * Content-Transfer-Encoding ヘッダの値を取得します。
   *
   * @return string Content-Transfer-Encoding ヘッダの値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentTransferEncoding()
  {
    return $this->getHeaderValue('Content-Transfer-Encoding');
  }

  /**
   * メール本文を設定します。
   *
   * @param string $body メール本文。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBody($body)
  {
    $this->_body = $body;
  }

  /**
   * メール本文を取得します。
   *
   * @return string メール本文を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBody()
  {
    return $this->_body;
  }

  /**
   * メッセージに添付データを追加します。
   *
   * @param Delta_MailAttachment $attachmentFile 追加する添付パート。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @todo part オブジェクトなのに複数のファイルをアタッチできるのは仕組み的におかしい
   */
  public function addAttachmentFile($attachmentFile)
  {
    $this->_attachmentFiles[] = $attachmentFile;
  }

  /**
   * メッセージに添付されているデータリストを取得します。
   *
   * @return Delta_MailAttachment Delta_MailAttachment のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttachmentFiles()
  {
    return $this->_attachmentFiles;
  }

  /**
   * メッセージに添付データが含まれているかチェックします。
   *
   * @return bool メッセージに添付データが含まれる場合は TRUE、含まれない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttachment()
  {
    if (sizeof($this->_attachmentFiles)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   */
  public function buildPart($width)
  {
    $contents = NULL;

    $lineFeed = $this->_lineFeed;
    $headers = $this->getHeaders();

    foreach ($headers as $name => $value) {
      $header = $name . ': ' . $value;
      $contents .= self::splitHeader($header, $width, $lineFeed) . $lineFeed;
    }

    $body = $this->getBody();

    if ($body !== NULL) {
      $encoding = strtolower($this->getContentTransferEncoding());

      if ($encoding != 'quoted-printable') {
        $body = Delta_StringUtils::wordwrap($body, $width, $lineFeed, $this->getCharset());
      }

      $contents .= $lineFeed . $body . $lineFeed;
    }

    return $contents;
  }

  /**
   * ヘッダの長さが一行辺りの最大文字数を超過してる場合、';' 区切りで改行を追加します。
   *
   * @param string $header ヘッダ文字列。
   * @param int $width 一行辺りの最大許容文字数。
   * @param string $separator 追加する改行コード。
   * @return string 改行を含むヘッダ文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function splitHeader($header, $width, $separator = "\r\n")
  {
    if (strlen($header) > $width) {
      $split = explode(';', $header);
      $buffer = NULL;

      foreach ($split as $line) {
        $buffer .= trim($line, $separator) . ';' . $separator;
      }

      $header = trim($buffer, ';' . $separator);
    }

    return $header;
  }
}
