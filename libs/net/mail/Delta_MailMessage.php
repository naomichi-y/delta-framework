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
 * メールのメッセージを管理します。
 *
 * このクラスは実験的なステータスにあります。
 * これは、このクラスの動作、メソッド名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 */
class Delta_MailMessage extends Delta_MailPart
{
  /**
   * メールアドレスリスト定数。
   */
  const LIST_TYPE_ADDRESS = 1;

  /**
   * 送信(受信)者名リスト定数。
   */
  const LIST_TYPE_NAME = 2;

  /**
   * メールアドレス + 送信 (受信) 者名リスト定数。
   */
  const LIST_TYPE_BOTH = 4;

  /**
   * メールメッセージ。
   * @var string
   */
  private $_message;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($message)
  {
    parent::__construct();

    $this->_message = $message;
  }

  /**
   * メールメッセージを取得します。
   *
   * @return string メールメッセージを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMessage()
  {
    return $this->_message;
  }

  /**
   * Return-Path ヘッダのリストを取得します。
   *
   * @param int $listType リストタイプ定数の指定。
   * @return array Return-Path ヘッダのアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getReturnPathList($listType = self::LIST_TYPE_ADDRESS)
  {
    $returnPathList = Delta_MailParser::decodeHeader($this, 'return-path');
    $returnPathList = $this->parseAddressList($returnPathList, $listType);

    return $returnPathList;
  }

  /**
   * To ヘッダのリストを取得します。
   *
   * @param int $listType リストタイプ定数の指定。
   * @return array To ヘッダのアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getToList($listType = self::LIST_TYPE_ADDRESS)
  {
    $toList = Delta_MailParser::decodeHeader($this, 'to');
    $toList = $this->parseAddressList($toList, $listType);

    return $toList;
  }

  /**
   * Cc ヘッダのリストを取得します。
   *
   * @param int $listType リストタイプ定数の指定。
   * @return array CC ヘッダのアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCcList($listType = self::LIST_TYPE_ADDRESS)
  {
    $ccList = Delta_MailParser::decodeHeader($this, 'cc');
    $ccList = $this->parseAddressList($ccList, $listType);

    return $ccList;
  }

  /**
   * Reply-To ヘッダのリストを取得します。
   *
   * @param int $listType リストタイプ定数の指定。
   * @return array Reply-To ヘッダのアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getReplyToList($listType = self::LIST_TYPE_ADDRESS)
  {
    $replyToList = Delta_MailParser::decodeHeader($this, 'reply-to');
    $replyToList = $this->parseAddressList($replyToList, $listType);

    return $replyToList;
  }

  /**
   * From ヘッダのリストを取得します。
   *
   * @param int $listType リストタイプ定数の指定。
   * @return array From ヘッダのアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFromList($listType = self::LIST_TYPE_ADDRESS)
  {
    $fromList = Delta_MailParser::decodeHeader($this, 'from');
    $fromList = $this->parseAddressList($fromList, $listType);

    return $fromList;
  }

  /**
   * 日付ヘッダを取得します。
   * 同一ヘッダが複数指定存在する場合は、一番最後に設定されている値を返します。
   *
   * @return string 日付ヘッダを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDate()
  {
    $headers = $this->getHeaders();
    $result = NULL;

    if (isset($headers['date'])) {
      $result = Delta_ArrayUtils::lastValue($headers['date']);
    }

    return $result;
  }

  /**
   * Message-ID ヘッダを取得します。
   * 同一ヘッダが複数指定存在する場合は、一番最後に設定されている値を返します。
   *
   * @return string Message-ID ヘッダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMessageId()
  {
    $headers = $this->getHeaders();
    $result = NULL;

    if (isset($headers['message-id'])) {
      $result = Delta_ArrayUtils::lastValue($headers['message-id']);
      $result = str_replace(array('<', '>'), array('', ''), $result);
    }

    return $messageId;
  }

  /**
   * Subject ヘッダを取得します。
   * 同一ヘッダが複数指定存在する場合は、一番最初に設定されている値を返します。
   *
   * @return string Subject ヘッダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSubject()
  {
    $subject = Delta_MailParser::decodeHeader($this, 'subject', 0);

    return $subject;
  }

  /**
   * メッセージサイズを取得します。
   *
   * @return int メッセージサイズを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMessageSize()
  {
    return strlen($this->_message);
  }

  /**
   * アドレスリストを名前とメールアドレスに分けて解析します。
   *
   * @param mixed $addresses 解析するアドレスのリスト。(配列もしくは文字列)
   * @param int $listType リストタイプ定数の指定。
   * @return array 解析されたアドレスリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseAddressList($addresses, $listType)
  {
    if (is_array($addresses)) {
      $parseList = array();

      foreach ($addresses as $address) {
        if (strlen($address)) {
          $parseList = array_merge($parseList, $this->parseAddress($address, $listType));
        }
      }

      return $parseList;

    } else {
      if (strlen($addresses)) {
        return $this->parseAddress($addresses, $listType);
      }

      return NULL;
    }
  }

  /**
   * アドレスを名前とメールアドレス分けて解析します。
   *
   * @param string $address 解析するアドレス。
   * @param int $listType リストタイプ定数の指定。
   * @return array 解析されたアドレスリストを返します。$listType が {@link LIST_TYPE_BOTH} の場合は 'name' と 'address' から構成されるハッシュリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parseAddress($address, $listType)
  {
    $splits = Delta_StringUtils::splitExclude($address, ',', '"', FALSE);
    $parseList = array();
    $i = 0;

    foreach ($splits as $split) {
      $addresses = $this->splitAddress($split, $listType);

      if ($listType == self::LIST_TYPE_ADDRESS) {
        $parseList[$i] = $addresses['address'];

      } else if ($listType == self::LIST_TYPE_NAME) {
        $parseList[$i] = $addresses['name'];

      } else {
        $parseList[$i]['name'] = $addresses['name'];
        $parseList[$i]['address'] = $addresses['address'];
      }

      $i++;
    }

    return $parseList;
  }

  /**
   * アドレスヘッダを名前とメールアドレスに分割します。
   *
   * @param string $address ヘッダに追加されるアドレス形式。
   * @return array アドレスヘッダを分割したハッシュを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function splitAddress($address)
  {
    // RFC2822 に準拠
    $separate = array();
    $splits = Delta_StringUtils::splitExclude($address, '<', '"', TRUE);

    $j = sizeof($splits);

    if ($j > 1) {
      $separate['name'] = trim($splits[0]);
      $separate['address'] = str_replace(array('<', '>'), '', $splits[1]);

    } else {
      $separate['name'] = '';
      $separate['address'] = str_replace(array('<', '>'), '', $splits[0]);
    }

    $separate['name'] = str_replace('\"', '"', $separate['name']);
    $separate['address'] = str_replace('\"', '"', preg_replace('/\([^\)]+\)|\s|^"|"(@|:)/', '\1', $separate['address']));

    // グループアドレスの解析
    if (($pos = strpos($separate['address'], ':')) !== FALSE) {
      $separate['name'] = stripslashes(substr($separate['address'], 0, $pos));
      $separate['address'] = rtrim(substr($separate['address'], $pos + 1), ';');
    }

    return $separate;
  }
}
