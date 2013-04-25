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
 * iPad 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentIPadAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'iPad';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgentAdapter::isIPad()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isIPad()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgentAdapter::isTablet()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isTablet()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgent::isValid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($userAgent)
  {
    if (preg_match('/iPad/', $userAgent)) {
      return TRUE;
    }

    return FALSE;
  }
}
