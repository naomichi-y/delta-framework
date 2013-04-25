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
 * ロギングしたメッセージを SAPI へ送信します。
 * 通常は Web サーバのエラーとしてロギングしますが、CLI から実行した場合は標準エラーとして出力します。
 * Web サーバが Apache 2 の場合、ログはデフォルトで "{Apache のインストールパス}/logs/error_log" に出力されます。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerSAPIAppender
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerSAPIAppender extends Delta_LoggerAppender
{
  /**
   * @see Delta_LoggerAppender::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    // error_log() が改行を含めるため、getLogFormat() には改行を追加しない
    $message = $this->getLogFormat($className, $type, $message);
    error_log($message, 0);
  }
}
