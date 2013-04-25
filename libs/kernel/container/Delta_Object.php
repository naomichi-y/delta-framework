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
 * フレームワークのコンテキスト機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 */
abstract class Delta_Object
{
  /**
   * オブジェクトを文字列として取得します。
   *
   * @return string オブジェクトを文字列データとして返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __toString()
  {
    return Delta_CommonUtils::convertVariableToString($this);
  }

  /**
   * サービスのインスタンスを取得します。
   * 詳細については {@link Delta_ServiceFactory::get()} や {@link Delta_Service} クラスを参照して下さい。
   *
   * @param string $serviceName 取得するサービス名。
   * @return Delta_Service サービスのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getService($serviceName)
  {
    return Delta_ServiceFactory::get($serviceName);
  }

  /**
   * Delta_AppPathManager のインスタンスを取得します。
   *
   * @return Delta_AppPathManager Delta_AppPathManager のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAppPathManager()
  {
    return Delta_AppPathManager::getInstance();
  }

  /**
   * フレームワークのレジストリにアクセスします。
   *
   * @return Delta_ParameterHolder Delta_ParameterHolder のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getRegistry()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_ParameterHolder();
    }

    return $instance;
  }
}
