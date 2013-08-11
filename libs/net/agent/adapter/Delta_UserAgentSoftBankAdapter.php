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
 * SoftBank 端末のためのユーザエージェントアダプタです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
class Delta_UserAgentSoftBankAdapter extends Delta_UserAgentAdapter
{
  /**
   * アダプタ名。
   */
  const ADAPTER_NAME = 'SoftBank';

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapterName()
  {
    return self::ADAPTER_NAME;
  }

  /**
   * @see Delta_UserAgentAdapter::isSoftBank()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isSoftBank()
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
    if (preg_match('/^SoftBank|^Vodafone|^J-PHONE/', $userAgent)) {
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
   * ユーザ ID (契約者 ID) を取得します。
   *
   * @return string ユーザ ID (15 桁の英数字) を返します。
   *   ユーザ ID を取得できない (またはユーザが ID の通知を無効に指定した) 場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUserId()
  {
    $request = Delta_FrontController::getInstance()->getRequest();

    return $request->getEnvironment('HTTP_X_JPHONE_UID');
  }

  /**
   * 個体識別番号を取得します。
   *
   * @return string 個体識別番号 (P 型は 11 桁、それ以降は 15 桁の数字) を返します。
   *   個体識別番号が取得できない (またはユーザが個体識別番号の通知を無効に設定している) 場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSerialNumber()
  {
    if (preg_match('/^.+\/SN([0-9a-zA-Z]+).*$/', $this->_userAgent, $matches)) {
      return $matches[1];
    }

    return NULL;
  }
}
