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
 * Android 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentAndroidAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'Android';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgentAdapter::isAndroid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAndroid()
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
    if (preg_match('/Android/', $userAgent) && strpos($userAgent, 'Mobile') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }
}
