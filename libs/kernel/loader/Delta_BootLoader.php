<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * require files
 */
require DELTA_LIBS_DIR . '/kernel/container/Delta_Object.php';

require DELTA_LIBS_DIR . '/kernel/loader/Delta_ClassLoader.php';
require DELTA_LIBS_DIR . '/kernel/path/Delta_AppPathManager.php';
require DELTA_LIBS_DIR . '/kernel/handler/Delta_ErrorHandler.php';
require DELTA_LIBS_DIR . '/kernel/handler/Delta_ExceptionHandler.php';

/**
 * フレームワークを起動するブートローダ機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.loader
 */

class Delta_BootLoader
{
  /**
   * 起動モード定数。(Web アプリケーション)
   */
  const BOOT_TYPE_WEB = 1;

  /**
   * 起動モード定数。(コンソールアプリケーション)
   */
  const BOOT_TYPE_CONSOLE = 2;

  /**
   * 起動モード定数。(delta コマンド)
   */
  const BOOT_TYPE_COMMAND = 3;

  /**
   * コンフィグ定数。(デフォルト)
   */
  const CONFIG_TYPE_DEFAULT = 1;

  /**
   * コンフィグ定数。(ポリシーコンフィグの参照)
   */
  const CONFIG_TYPE_POLICY = 2;

  /**
   * @var int
   */
  private static $_bootType;

  /**
   * @var int
   */
  private static $_configType;

 /**
   * ブートローダを起動します。
   *
   * @param int $bootType ブートローダの起動モード。Delta_BootLoader::BOOT_TYPE_* 定数が指定可能。
   * @param int $configType コンフィグの参照モード。Delta_BootLoader::CONFIG_TYPE_* 定数が指定可能。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function run($bootType = self::BOOT_TYPE_WEB, $configType = self::CONFIG_TYPE_DEFAULT)
  {
    // エラーメッセージは記録しない (Delta_ErrorHandler::invokeFatalError() で処理する)
    ini_set('log_errors', 0);

    self::$_bootType = $bootType;
    self::$_configType = $configType;

    switch ($bootType) {
      case self::BOOT_TYPE_WEB:
        self::startApplication();
        break;

      case self::BOOT_TYPE_CONSOLE:
        self::startApplication();
        break;

      case self::BOOT_TYPE_COMMAND:
        self::startCommand();
        break;
    }
  }

  /**
   * ブートローダの起動モードを取得します。
   *
   * @return int ブートローダの起動モードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getBootType()
  {
    return self::$_bootType;
  }

  /**
   * ブートローダが Web アプリケーションモードで起動しているかどうかチェックします。
   *
   * @return bool ブートローダが Web アプリケーションモードで起動している場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isBootTypeWeb()
  {
    if (self::getBootType() == self::BOOT_TYPE_WEB) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * ブートローダがコンソールモードで起動しているかどうかチェックします。
   *
   * @return bool ブートローダがコンソールモードで起動している場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isBootTypeConsole()
  {
    if (self::getBootType() == self::BOOT_TYPE_CONSOLE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * ブートローダがコマンドモードで起動しているかどうかチェックします。
   *
   * @return bool ブートローダがコマンドモードで起動している場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isBootTypeCommand()
  {
    if (self::getBootType() == self::BOOT_TYPE_COMMAND) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * コンフィグの参照がアプリケーションモードであるかどうかチェックします。
   *
   * @return bool コンフィグの参照がアプリケーションモードの場合に TRUE を返します。
   * @var 1.15.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isConfigTypeDefault()
  {
    if (self::$_configType == self::CONFIG_TYPE_DEFAULT) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * コンフィグの参照がポリシーモードであるかどうかチェックします。
   *
   * @return bool コンフィグの参照がポリシーモードの場合に TRUE を返します。
   * @var 1.15.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isConfigTypePolicy()
  {
    if (self::$_configType == self::CONFIG_TYPE_POLICY) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * アプリケーションを開始します。
   *
   * @throws Delta_ConfigurationException {@link ini_set()} に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function startApplication()
  {
    // Fatal エラー発生時にエラーメッセージが出力されないよう抑制する
    // (エラーメッセージは Delta_ErrorHandler::detectFatalError() が出力する)
    ini_set('display_errors', 0);

    // エラー、例外ハンドラの登録
    set_error_handler(array('Delta_ErrorHandler', 'handler'));
    set_exception_handler(array('Delta_ExceptionHandler', 'handler'));
    register_shutdown_function(array('Delta_ErrorHandler', 'detectFatalError'));

    // クラスローダの初期化
    Delta_ClassLoader::initialize();

    $config = Delta_Config::getApplication();

    // php.ini のオーバーライド
    $phpConfig = $config->getArray('php');

    foreach ($phpConfig as $name => $value) {
      if (ini_set($name, $value) === FALSE) {
        $message = sprintf('Can\'t set \'%s\'. Can only set PHP_INI_USER or PHP_INI_ALL.', $name);
        throw new Delta_ConfigurationException($message);
      }
    }

    // アプリケーションパスの設定
    $themeConfig = $config->get('theme');
    Delta_AppPathManager::getInstance()->initialize($themeConfig);

    // オートロードパスの追加
    $autoloadConfig = $config->getArray('autoload');

    foreach ($autoloadConfig as $path) {
      Delta_ClassLoader::addSearchPath($path);
    }
  }

  /**
   * delta コマンドモードを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function startCommand()
  {
    set_error_handler(array('Delta_ErrorHandler', 'handler'));
    set_exception_handler(array('Delta_ExceptionHandler', 'handler'));

    Delta_ClassLoader::initialize();
    $manager = Delta_AppPathManager::getInstance();

    if (defined('APP_ROOT_DIR')) {
      $themeConfig = Delta_Config::getApplication()->get('theme');
      $manager->initialize($themeConfig);

    } else {
      $manager->initialize();
    }
  }
}

