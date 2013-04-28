<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * イベントリスナのための抽象クラスです。
 * Web アプリケーション用カスタムイベントリスナを作成する場合は、{@link Delta_WebApplicationEventListener}、コンソールアプリケーション用カスタムイベントリスナを作成する場合は {@link Delta_ConsoleApplicationEventListener} を継承すると良いでしょう。
 * <code>
 * class CustomEventListener extends Delta_WebApplicationEventListener
 * {
 *   // 'postCreateInstance' イベントを {@link Delta_KernelEventObserver オブザーバ} に通知
 *   public function {@link getListenEvents}()
 *   {
 *     return array('postCreateInstance');
 *   }
 * }
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 */

abstract class Delta_ApplicationEventListener extends Delta_Object
{
  /**
   * {@link Delta_KernelEventObserver オブザーバ} に通知するイベントリストを取得します。
   * 例えば '{@link Delta_KernelEventObserver::preOutput() preOutput}') をイベントとしてオブザーバに追加したい場合は、array('preOutput') を返すようにします。
   *
   * @return array オブザーバに通知するイベントリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getListenEvents();

  /**
   * リスナの実行を許可する起動モードを取得します。
   *
   * @return array 起動モード定数 ({@link Delta_BootLoader::BOOT_MODE_WEB}、あるいは {@link Delta_BootLoader::BOOT_MODE_CONSOLE)} を返します。
   *   Web とコンソール両方を許可する場合は、Delta_BootLoader::BOOT_MODE_WEB|Delta_BootLoader::BOOT_MODE_CONSOLE を返します。
   */
  abstract public function getBootMode();

  /**
   * イベントリスナのインスタンスが生成された直後に (アプリケーションが {@link Delta_BootLoader ブートローダ} によって初期化されるタイミングで) 起動します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function postCreateInstance()
  {}

  /**
   * フレームワークの処理が完了する直前に起動します。
   * このメソッドはプログラム内で {@link http://php.net/manual/function.exit.php exit} を宣言した場合も実行される点に注意して下さい。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function preShutdown()
  {}
}
