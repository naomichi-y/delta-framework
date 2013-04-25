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
 * 日付によるログローテートを処理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.executor
 */
class Delta_LogRotateDateBasedExecutor extends Delta_LogRotateExecutor
{
  /**
   * @var array
   */
  private $_rotateLogs = array();

  /**
   * @see Delta_LogRotatePolicy::isRotateRequired()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isRotateRequired()
  {
    $result = FALSE;
    $generation = $this->_logRotatePolicy->getGeneration();

    if ($generation != Delta_LogRotatePolicy::GENERATION_UNLIMITED) {
      $this->_rotateLogs = $this->getRotateLogs();

      if (sizeof($this->_rotateLogs) > $generation) {
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * @see Delta_LogRotatePolicy::rotate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rotate()
  {
    $generation = $this->_logRotatePolicy->getGeneration();

    if ($generation != Delta_LogRotatePolicy::GENERATION_UNLIMITED) {
      $j = sizeof($this->_rotateLogs) - $generation;

      for ($i = 0; $i < $j; $i++) {
        if (is_file($this->_rotateLogs[$i])) {
          unlink($this->_rotateLogs[$i]);
        }
      }
    }
  }
}
