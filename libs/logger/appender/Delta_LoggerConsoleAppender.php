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
 * ロギングしたメッセージを標準出力に表示します。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerConsoleAppender
 *
 *     # 出力タイプの指定
 *     #   - stdout: 標準出力
 *     #   - stderr: 標準エラー
 *     type: stdout
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerConsoleAppender extends Delta_LoggerAppender
{
  /**
   * @var string
   */
  private $_type;

  /**
   * @see Delta_LoggerAppender::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($appenderId, Delta_ParameterHolder $holder)
  {
    parent::__construct($appenderId, $holder);

    $this->_type = $holder->getString('type');
  }

  /**
   * @see Delta_LoggerAppender::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    $message = $this->getLogFormat($className, $type, $message) . PHP_EOL;

    if ($this->_type === 'stderr') {
      $stream = 'php://stderr';
    } else {
      $stream = 'php://stdout';
    }

    $fp = fopen($stream, 'w');
    fwrite($fp, $message);
    fflush($fp);
    fclose($fp);
  }
}
