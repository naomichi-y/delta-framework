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
 * ロギングしたメッセージをファイルへ書き込みます。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class: Delta_LoggerFileAppender
 *
 *     # ログの出力先。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
 *     # 'error.log' を指定した場合の出力先は {APP_ROOT_DIR}/logs/error.log となる。
 *     file: logs/delta.log
 *
 *     # ログファイルをローテートする際に用いる属性。
 *     rotate:
 *       # {@link Delta_LogRotatePolicy::setGeneration()}
 *       generation: 4
 *
 *       # {@link Delta_LogWriter::__construct()}
 *       appendMode: TRUE
 *
 *       # {@link Delta_LogWriter::setLinefeed()}
 *       linefeed: <?php echo PHP_EOL ?>
 *
 *       # ファイル作成時の権限を 8 進数で指定。(例えば 0644)
 *       # 未指定時は OS (実行ユーザ) のファイル書き込み権限に依存する。
 *       mode:
 *
 *       # ログのローテートタイプ。
 *       #   - date: 日付によるローテート。
 *       #   - size: ファイルサイズによるローテート。
 *       type:
 *
 *       ############################################################
 *       # type が 'date' の場合に指定可能なオプション
 *       ############################################################
 *       # {@link Delta_LogRotateDateBasedPolicy::setDatePattern()}
 *       datePattern: Y-m-d
 *
 *       ############################################################
 *       # type が 'size' の場合に指定可能なオプション
 *       ############################################################
 *       # {@link Delta_LogRotateSizeBasedPolicy::setMaxSize()}
 *       maxSize: 1MB
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_LoggerAppender} クラスを参照して下さい。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
class Delta_LoggerFileAppender extends Delta_LoggerAppender
{
  /**
   * @var Delta_LogWriter
   */
  private $_logWriter;

  /**
   * @see Delta_LoggerAppender::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($appenderId, Delta_ParameterHolder $holder)
  {
    parent::__construct($appenderId, $holder);

    $path = $holder->getString('file', 'delta.log');
    $mode = $holder->getString('mode');
    $rotate = $holder->get('rotate', array());

    if ($rotate->hasName('type')) {
      if ($rotate->getString('type') == 'date') {
        $policy = new Delta_LogRotateDateBasedPolicy($path);

        if ($rotate->hasName('datePattern')) {
          $policy->setDatePattern($rotate->getString('datePattern'));
        }

      } else {
        $policy = new Delta_LogRotateSizeBasedPolicy($path);
        $maxSize = $rotate->get('maxSize');

        if ($maxSize) {
         $policy->setMaxSize($maxSize);
        }
      }

    } else {
      $policy = new Delta_LogRotateNullBasedPolicy($path);
    }

    $generation = $rotate->getInt('generation', 4);
    $policy->setGeneration($generation);

    $appendAppend = $rotate->getBoolean('appendMode', TRUE);
    $linefeed = $rotate->getString('linefeed', PHP_EOL);

    $logWriter = new Delta_LogWriter($policy, $appendMode, FALSE);
    $logWriter->setLinefeed($linefeed);

    if ($mode !== NULL) {
      $mode = intval((string) $mode, 8);
      $logWriter->setMode($mode);
    }

    $this->_logWriter = $logWriter;
  }

  /**
   * @see Delta_Logger::write()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($className, $type, $message)
  {
    if ($this->_logWriter) {
      $message = $this->getLogFormat($className, $type, $message);
      $this->_logWriter->writeLine($message);
    }
  }
}
