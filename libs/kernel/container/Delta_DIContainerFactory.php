<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * DI コンテナを生成するためのファクトリクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 */
class Delta_DIContainerFactory
{
  /**
   * {@link Delta_DIContainer} オブジェクト。
   * @var Delta_DIContainer
   */
  private static $_container;

  /**
   * プライベートコンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * DI コンテナを初期化します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function initialize()
  {
    self::$_container = new Delta_DIContainer();
  }

  /**
   * Delta_DIContainer オブジェクトを取得します。
   *
   * @return Delta_DIContainer DI コンテナのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getContainer()
  {
    if (self::$_container === NULL) {
      throw new RuntimeException('DI container is not initialized.');
    }

    return self::$_container;
  }
}
