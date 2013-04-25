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
 * ロギングしたメッセージを SYSLOG へ送信します。
 * Windows 環境では、SYSLOG サービスはイベントログとして扱われます。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerSyslogAppender
 *
 *     # システムログの各メッセージに追加される文字列。
 *     identity:
 *
 *     # ロギング用オプション。
 *     # 詳しくは {@link openlog()} 関数を参照。
 *     openlog:
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * ログレベルと SYSLOG レベルは次のようにマッピングされます。
 *   - {@link Delta_Logger::LOGGER_MASK_TRACE}: LOG_NOTICE
 *   - {@link Delta_Logger::LOGGER_MASK_DEBUG}: LOG_DEBUG
 *   - {@link Delta_Logger::LOGGER_MASK_INFO}: LOG_INFO
 *   - {@link Delta_Logger::LOGGER_MASK_WARNING}: LOG_WARNING
 *   - {@link Delta_Logger::LOGGER_MASK_ERROR}: LOG_ERR
 *   - {@link Delta_Logger::LOGGER_MASK_FATAL}: LOG_CRIT
 *
 * <i>LOG_EMERG、LOG_ALERT はマッピングされません。</i>
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerSyslogAppender extends Delta_LoggerAppender
{
  /**
   * SYSLOG 定数とフレームワークのエラー定数をマッピング。
   * @var array
   */
  private static $_mapping = array(
    Delta_Logger::LOGGER_MASK_TRACE => LOG_NOTICE,
    Delta_Logger::LOGGER_MASK_DEBUG => LOG_DEBUG,
    Delta_Logger::LOGGER_MASK_INFO => LOG_INFO,
    Delta_Logger::LOGGER_MASK_WARNING => LOG_WARNING,
    Delta_Logger::LOGGER_MASK_ERROR => LOG_ERR,
    Delta_Logger::LOGGER_MASK_FATAL => LOG_CRIT
  );

  /**
   * @see Delta_LoggerAppender::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($appenderId, Delta_ParameterHolder $holder)
  {
    parent::__construct($appenderId, $holder);

    $openlog = $holder->getInt('openlog');

    if ($openlog !== NULL) {
      $option = $openlog;
    } else {
      $option = LOG_ODELAY;
    }

    $identity = $holder->getBoolean('identity');
    openlog($identity, $option, LOG_SYSLOG);
  }

  /**
   * @see Delta_LoggerAppender::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    $message = $this->getLogFormat($className, $type, $message);
    syslog(self::$_mapping[$type], $message);
  }
}
