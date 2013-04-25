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
 * ローテートしないポリシーを定義します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.policy
 */
class Delta_LogRotateNullBasedPolicy extends Delta_LogRotatePolicy
{
  /**
   * @see Delta_LogRotatePolicy::getExecutorClassName()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExecutorClassName()
  {
    return 'Delta_LogRotateNullBasedExecutor';
  }
}
