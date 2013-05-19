<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session.handler
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * セッションの管理を APC にハンドリングします。
 * このクラスを使う場合、実行環境に {@link http://www.php.net/manual/apc.installation.php APC モジュール} が組み込まれている必要があります。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session.handler
 */

class Delta_APCSessionHandler extends Delta_Object
{
  /**
   * セッションの生存期間。
   * @var int
   */
  private $_lifetime;

  /**
   * コンストラクタ。
   *
   * @param Delta_ParameterHolder $config セッションハンドラ属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct(Delta_ParameterHolder $config)
  {
    $this->_lifetime = ini_get("session.gc_maxlifetime");

    session_set_save_handler(array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc'));
  }

  /**
   * セッション管理を APC にハンドリングします。
   *
   * @param Delta_ParameterHolder $config セッションハンドラ属性。
   * @return Delta_APCSessionHandler Delta_APCSessionHandler のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function handler(Delta_ParameterHolder $config)
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_APCSessionHandler($config);
    }

    return $instance;
  }

  /**
   * セッションストレージへの接続を行います。
   *
   * @param string $savePath セッションの保存パス。
   * @param string $sessionName セッション名。
   * @return bool セッションストレージへの接続に成功した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function open($savePath, $sessionName)
  {
    return TRUE;
  }

  /**
   * セッションストレージへの接続を閉じます。
   * このメソッドはセッション操作が終了する際に実行されます。
   *
   * @return bool セッション操作が正常に終了した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    return TRUE;
  }

  /**
   * セッションに格納されている値を取得します。
   *
   * @param string $sessionId セッション ID。
   * @return string セッションに格納されている値を返します。値が存在しない場合は空文字を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function read($sessionId)
  {
    $value = apc_fetch($sessionId);

    if ($value !== FALSE) {
      return $value;
    }

    return '';
  }

  /**
   * セッションに値を書き込みます。
   * 通常はオブジェクトが破棄された後にコールされます。
   *
   * @param string $sessionId セッション ID。
   * @param mixed $sessionData 書き込むデータ。
   * @return bool 書き込みに成功したかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($sessionId, $sessionData)
  {
    return apc_store($sessionId, $sessionData, $this->_lifetime);
  }

  /**
   * セッションを破棄します。
   *
   * @param string $sessionId セッション ID。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function destroy($sessionId)
  {
    return apc_delete($sessionId);
  }

  /**
   * ガベージコレクタを起動します。
   *
   * @param int $lifetime セッションの生存期間。単位は秒。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function gc($lifetime)
  {
    return TRUE;
  }
}
