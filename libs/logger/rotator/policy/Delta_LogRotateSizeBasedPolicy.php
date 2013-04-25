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
 * Delta_LogRotateSizeBasedPolicy のローテートを処理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.policy
 */
class Delta_LogRotateSizeBasedPolicy extends Delta_LogRotatePolicy
{
  /**
   * @var int
   */
  private $_maxSize = 10485760;

  /**
   * ファイルサイズの上限を設定します。
   * バイト数による指定のほか、'1024KB'、'10MB' といった単位を含めたサイズ指定も可能です。
   * 既定の上限サイズは 10485760 (10 MB) となります。
   *
   * @param mixed $maxSize ファイルサイズの上限。
   *   指定可能な形式は {@link Delta_NumberUtils::realBytes()} を参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setMaxSize($maxSize)
  {
    if (is_string($maxSize)) {
      $maxSize = Delta_NumberUtils::realBytes($maxSize);
    }

    $this->_maxSize = $maxSize;
  }

  /**
   * ファイルサイズの上限を取得します。
   *
   * @return int ファイルサイズの上限を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMaxSize()
  {
    return $this->_maxSize;
  }

  /**
   * @see Delta_LogRotatePolicy::getExecutorClassName()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExecutorClassName()
  {
    return 'Delta_LogRotateSizeBasedExecutor';
  }
}
