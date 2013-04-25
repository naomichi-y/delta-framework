<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ロギングしたメッセージをシリアライズした状態でソケットへ送信します。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerSocketAppender
 *
 *     # 接続先ホスト名。
 *     host: localhost
 *
 *     # 接続先ポート番号。
 *     port: 1980
 *
 *     # 接続タイムアウト秒。
 *     timeout: 30
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerSocketAppender extends Delta_LoggerAppender
{
  /**
   * ソケットオブジェクトを取得します。
   *
   * @return Delta_Socket Delta_Socket のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function getSocket()
  {
    static $socket;

    if ($socket === NULL) {
      $host = $this->holder->getString('host', 'localhost');
      $port = $this->holder->getInt('port', 1980);
      $timeout = $this->holder->getInt('timeout', 30);

      $socket = new Delta_Socket();
      $socket->connect($host, $port, $timeout);
    }

    return $socket;
  }

  /**
   * @see Delta_LoggerAppender::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    $message = $this->getLogFormat($className, $type, $message) . "\n";
    $message = serialize($message);

    $this->getSocket()->write($message);
  }

  /**
   * オブジェクトの破棄を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    $this->getSocket()->close();
  }
}
