<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package domain.service
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * サービスの抽象クラスです。
 * 全てのビジネスロジックは Delta_Service を継承したサービスクラス内で実装します。
 * <strong>サービスはシステムのユースケース単位で作成することを推奨します。</strong>
 *
 * 開発者が作成するのサービスは、クラス名のサフィックスとして 'Service' を付ける必要があります。
 * 例えば Greeting サービスであれば、クラス名は GreetingService となります。
 * 作成したサービスクラスは {APP_ROOT_DIR}/libs/service 下に配置して下さい。
 *
 * <code>
 * class GreetingService extends Delta_Service
 * {
 *   public function echo()
 *   {
 *     return 'Hello World!';
 *   }
 * }
 * </code>
 *
 * サービスのインスタンスは {@link Delta_ServiceFactory} から取得することができます。
 *
 * <code>
 * $greeting = Delta_ServiceFactory::get('Greeting');
 *
 * // {@link Delta_Object::getService()} を使った参照
 * $greeting = $this->getService('Greeting');
 * $greeting->echo();
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package domain.service
 */

abstract class Delta_Service extends Delta_Object
{
  /**
   * コンストラクタ。
   * このメソッドは処理の最後に {@link initialize()} をコールします。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->initialize();
  }

  /**
   * サービスの初期化を行うメソッドです。
   * {@link Delta_ServiceFactory::load()} メソッドでクラスをロードした場合、このメソッドは実行されない点に注意して下さい。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {}
}
