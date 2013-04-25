<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentAndroidAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentAndroidTabletAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentAUAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentDefaultAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentDoCoMoAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentIPhoneAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentIPadAdapter.php';
require DELTA_LIBS_DIR . '/net/agent/adapter/Delta_UserAgentSoftBankAdapter.php';

/**
 * クライアントのブラウザ情報を識別するためのアダプタクラスを提供します。
 * Delta_UserAgent にはあらかじめいくつかのアダプタが登録されています。
 *   - {@link Delta_UserAgentDoCoMoAdapter}
 *   - {@link Delta_UserAgentAUAdapter}
 *   - {@link Delta_UserAgentSoftBankAdapter}
 *   - {@link Delta_UserAgentIPhoneAdapter}
 *   - {@link Delta_UserAgentIPadAdapter}
 *   - {@link Delta_UserAgentAndroidAdapter}
 *   - {@link Delta_UserAgentAndroidTabletAdapter}
 *   - {@link Delta_UserAgentDefaultAdapter}
 * 開発者はこれらに加え、新しいエージェントアダプタを登録することも可能です。
 * 詳しくは {@link addAdapter()} メソッドを参照して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.agent
 */
class Delta_UserAgent extends Delta_Object
{
  /**
   * アダプタリスト。
   * @var array
   */
  private $_adapters = array(
    'Delta_UserAgentDoCoMoAdapter',
    'Delta_UserAgentAUAdapter',
    'Delta_UserAgentSoftBankAdapter',
    'Delta_UserAgentIPhoneAdapter',
    'Delta_UserAgentIPadAdapter',
    'Delta_UserAgentAndroidAdapter',
    'Delta_UserAgentAndroidTabletAdapter'
  );

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * Delta_UserAgent のインスタンスを取得します。
   *
   * @return Delta_UserAgent Delta_UserAgent のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance = NULL;

    if ($instance === NULL) {
      $instance = new Delta_UserAgent();
    }

    return $instance;
  }

  /**
   * ユーザエージェントアダプタを追加します。
   *
   * @param string $className {@link Delta_UserAgentAdapter} を実装したアダプタのクラス名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addAdapter($className)
  {
    array_unshift($this->_adapters, $className);
  }

  /**
   * 登録されている全てのアダプタリストを取得します。
   *
   * @return array 登録されている全てのアダプタリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapters()
  {
    return $this->_adapters;
  }

  /**
   * ユーザエージェントのアダプタを取得します。
   *
   * @param string $userAgent ユーザエージェント文字列。
   * @return Delta_UserAgentAdapter Delta_UserAgentAdapter のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapter($userAgent)
  {
    $adapter = NULL;

    foreach ($this->_adapters as $className) {
      if (call_user_func(array($className, 'isValid'), $userAgent)) {
        $adapter = $className;
        break;
      }
    }

    if ($adapter === NULL) {
      $adapter = 'Delta_UserAgentDefaultAdapter';
    }

    $adapter = new $adapter($userAgent);
    $adapter->parse();

    return $adapter;
  }
}
