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
 * Delta_LogRotateDateBasedPolicy のローテートを処理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.rotator.policy
 */
class Delta_LogRotateDateBasedPolicy extends Delta_LogRotatePolicy
{
  /**
   * 月次ローテート。
   */
  const PATTERN_MONTHLY = 'Y-m';

  /**
   * 週次ローテート。
   */
  const PATTERN_WEEKLY = 'Y-W';

  /**
   * 日次ローテート。
   */
  const PATTERN_DAILY = 'Y-m-d';

  /**
   * 毎時ローテート。
   */
  const PATTERN_HOURLY = 'Y-m-d-H';

  /**
   * @see Delta_LogRotatePolicy::$_generation
   */
  protected $_generation = parent::GENERATION_UNLIMITED;

  /**
   * @var string
   */
  private $_datePattern = self::PATTERN_DAILY;

  /**
   * @see Delta_LogRotatePolicy::getWritePath()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getWritePath()
  {
    return sprintf('%s.%s', $this->_basePath, date($this->_datePattern));
  }

  /**
   * ファイル名に付加する日付のフォーマットを設定します。
   * Delta_LogRotateDateBasedPolicy::PATTERN_* 定数を指定、または {@link date()} が識別可能なフォーマットを指定することができます。
   * 例えばファイル名が 'error'、フォーマットが Delta_LogRotateDateBasedPolicy::PATTERN_DAILY の場合、出力対象のファイル名は 'error.1980-08-06' となります。
   * フォーマットが未指定の場合は日次ローテートが有効になります。
   *
   * @param string $datePattern 日付のフォーマット。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDatePattern($datePattern)
  {
    $this->_datePattern = $datePattern;
  }

  /**
   * ファイル名に付加する日付のフォーマットを取得します。
   *
   * @return string ファイル名に付加する日付のフォーマットを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDatePattern()
  {
    return $this->_datePattern;
  }

  /**
   * @see Delta_LogRotatePolicy::getExecutorClassName()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExecutorClassName()
  {
    return 'Delta_LogRotateDateBasedExecutor';
  }
}
