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
 * AU 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentAUAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'AU';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgentAdapter::isAU()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAU()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgentAdapter::isMobile()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isMobile()
  {
    return TRUE;
  }

  /**
   * @see Delta_UserAgent::isValid()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($userAgent)
  {
    if (preg_match('/^UP\.Browser|^KDDI/', $userAgent)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Delta_UserAgentAdapter::getEncoding()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEncoding()
  {
    return 'SJIS-win';
  }

  /**
   * EZ 番号 (29 桁の英数字) を取得します。
   *
   * @return string EZ 番号を返します。
   *   EZ 番号が取得できない (またはユーザが EZ 番号の通知を無効に設定している) 場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUserId()
  {
    $request = Delta_FrontController::getInstance()->getRequest();

    return $request->getEnvironment('HTTP_X_UP_SUBNO');
  }

  /**
   * {@link getUserId()} メソッドのエイリアスです。
   *
   * @deprecated このメソッドは将来的に破棄されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSerialNumber()
  {
    return $this->getUserId();
  }
}
