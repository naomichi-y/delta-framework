<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ローテート機能を備えたログの出力機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger
 */
class Delta_LogWriter extends Delta_FileWriter
{
  /**
   * @var Delta_LogRotateExecutor
   */
  private $_logRotateExecutor;

  /**
   * コンストラクタ。
   *
   * @param Delta_LogRotatePolicy $rotatePattern ローテートパターンのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_LogRotatePolicy $rotatePattern)
  {
    $writePath = $rotatePattern->getWritePath();
    $executorClassName = $rotatePattern->getExecutorClassName();
    $this->_logRotateExecutor = new $executorClassName($writePath, $rotatePattern);

    parent::__construct($writePath);
  }

  /**
   * 全てのローテートログファイルを削除します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function deleteRotateLogs()
  {
    $logs = $this->_logRotateExecutor->getRotateLogs();

    foreach ($logs as $path) {
      if (is_file($path)) {
        unlink($path);
      }
    }

    $this->clear();
  }

  /**
   * デストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    try {
      if ($this->_logRotateExecutor->isRotateRequired()) {
        $this->_logRotateExecutor->rotate();
      }

      $this->flush();

    } catch (Exception $e) {
      Delta_ExceptionStackTraceDelegate::invoker($e);

      die();
    }
  }
}
