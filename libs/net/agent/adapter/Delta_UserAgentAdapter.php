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
 * クライアントのユーザエージェント情報を提供するアダプタの抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent.adapter
 */
abstract class Delta_UserAgentAdapter extends Delta_Object
{
  /**
   * ユーザエージェント文字列。
   * @var string
   */
  protected $_userAgent;

  /**
   * コンストラクタ。
   *
   * @param string $userAgent ユーザエージェント文字列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($userAgent)
  {
    $this->_userAgent = $userAgent;
  }

  /**
   * ユーザエージェントを解析します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parse()
  {}

  /**
   * アダプタ名を取得します。
   *
   * @return string アダプタ名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getAdapterName();

  /**
   * ユーザエージェントの文字列を取得します。
   *
   * @return string ユーザエージェントの文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFullName()
  {
    return $this->_userAgent;
  }

  /**
   * ユーザエージェントがアダプタに準拠しているか判定します。
   *
   * @param string $userAgent ユーザエージェント文字列。
   * @return bool ユーザエージェントがアダプタに準拠している場合は TRUE、準拠していない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($userAgent)
  {
    return FALSE;
  }

  /**
   * エンコーディング形式を取得します。
   *
   * @return string エンコーディング形式を返します。
   *   既定値は application.yml に定義された 'charset.default' 属性を参照します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEncoding()
  {
    return Delta_Config::getApplication()->getString('charset.default');
  }

  /**
   * クライアントにレスポンスを返す際の一般的な Content-Type を取得します。
   *
   * @return string 出力を返す際の一般的な Content-Type を返します。
   *   デフォルトでは 'text/html; charset=UTF-8' (エンコーディング形式は getEncoding() の戻り値) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getContentType()
  {
    return 'text/html; charset=' . $this->getEncoding();
  }

  /**
   * ユーザエージェントが PC (携帯、スマートフォン以外) であるかどうか判定します。
   *
   * @return bool ユーザエージェントが PC の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isDefault()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが DoCoMo 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが DoCoMo 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isDoCoMo()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが AU 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが AU 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAU()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが SoftBank 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが SoftBank 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isSoftBank()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが DoCoMo、AU、SoftBank 端末のいずれかであるかどうか判定します。
   *
   * @return bool ユーザエージェントが DoCoMo、AU、SoftBank 端末のいずれかの場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isMobile()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが iPhone 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが iPhone 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isIPhone()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが iPad 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが iPad 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isIPad()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが Android 端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが Android 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAndroid()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが Android タブレット端末であるかどうか判定します。
   *
   * @return bool ユーザエージェントが Android 端末の場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isAndroidTablet()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが iPhone、Android 端末のいずれかであるかどうか判定します。
   *
   * @return bool ユーザエージェントが iPhone、Android 端末のいずれかの場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isSmartphone()
  {
    return FALSE;
  }

  /**
   * ユーザエージェントが iPad、Android タブレット端末のいずれかであるかどうか判定します。
   *
   * @return bool ユーザエージェントが iPad、Android タブレット端末のいずれかの場合は TRUE、それ以外の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isTablet()
  {
    return FALSE;
  }

  /**
   * @see Delta_UserAgentAdapter::getFullName()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __toString()
  {
    return $this->_userAgent;
  }
}
