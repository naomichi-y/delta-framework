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
 * Delta_LogRotateNullBasedPolicy のローテートを処理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.executor
 */
class Delta_LogRotateNullBasedExecutor extends Delta_LogRotateExecutor
{
  /**
   * @see Delta_LogRotatePolicy::isRotateRequired()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isRotateRequired()
  {
    return FALSE;
  }

  /**
   * @see Delta_LogRotatePolicy::rotate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rotate()
  {}
}
