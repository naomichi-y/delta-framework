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
 * PC (携帯、スマートフォンを除く) 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentDefaultAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'Default';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgent::isValid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($userAgent)
  {
    $adapters = Delta_UserAgent::getAdapters();

    foreach ($adapters as $className) {
      if (call_user_func(array($className, 'isValid'), $userAgent)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * @see Delta_UserAgentAdapter::isDefault()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isDefault()
  {
    return TRUE;
  }
}
