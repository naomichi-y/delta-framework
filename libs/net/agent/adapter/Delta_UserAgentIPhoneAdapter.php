<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * iPhone 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentIPhoneAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'iPhone';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgentAdapter::isIPhone()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isIPhone()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgentAdapter::isSmartphone()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isSmartphone()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgent::isValid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($userAgent)
  {
    if (preg_match('/iPhone/', $userAgent)) {
      return TRUE;
    }

    return FALSE;
  }
}
