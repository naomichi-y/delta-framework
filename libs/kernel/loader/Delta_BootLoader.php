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
require DELTA_LIBS_DIR . '/kernel/observer/Delta_KernelEventObserver.php';

require DELTA_LIBS_DIR . '/kernel/loader/Delta_ClassLoader.php';
require DELTA_LIBS_DIR . '/kernel/path/Delta_AppPathManager.php';
require DELTA_LIBS_DIR . '/kernel/handler/Delta_ErrorHandler.php';
require DELTA_LIBS_DIR . '/kernel/handler/Delta_ExceptionHandler.php';
require DELTA_LIBS_DIR . '/kernel/observer/listener/Delta_ApplicationEventListener.php';

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
  const BOOT_MODE_WEB = 1;

  /**
   * 起動モード定数。(コンソールアプリケーション)
   */
  const BOOT_MODE_CONSOLE = 2;

  /**
   * 起動モード定数。(delta コマンド)
   */
  const BOOT_MODE_COMMAND = 4;

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
  private static $_bootMode;

  /**
   * @var int
   */
  private static $_configType;

  /**
   * Web アプリケーションを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function startWebApplication()
  {
    require DELTA_LIBS_DIR . '/controller/Delta_WebApplication.php';
    require DELTA_LIBS_DIR . '/kernel/observer/listener/Delta_WebApplicationEventListener.php';

    self::$_bootMode = self::BOOT_MODE_WEB;
    self::$_configType = self::CONFIG_TYPE_DEFAULT;

    self::startApplication();

    Delta_FrontController::getInstance()->dispatch();
  }

  /**
   * コントロールパネルを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function startControlPanel()
  {
    self::$_bootMode = self::BOOT_MODE_WEB;
    self::$_configType = self::CONFIG_TYPE_POLICY;

    self::startApplication();

    // cpanel モジュールをクラスローダに追加
    Delta_ClassLoader::addSearchPath(DELTA_ROOT_DIR . '/webapps/cpanel/libs');

    // 設定ファイルの初期化
    $appConfig = Delta_Config::getApplication();
    $projectAppConfig = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);

    $appConfig->set('database', $projectAppConfig->getArray('database'));
    $appConfig->set('cpanel', $projectAppConfig->getArray('cpanel'));
    $appConfig->set('response.callback', 'none');

    Delta_FrontController::getInstance()->dispatch();
  }

  /**
   * コンソールアプリケーションを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function startConsoleApplication()
  {
    require DELTA_LIBS_DIR . '/kernel/observer/listener/Delta_ConsoleApplicationEventListener.php';

    self::$_bootMode = self::BOOT_MODE_CONSOLE;
    self::$_configType = self::CONFIG_TYPE_DEFAULT;

    self::startApplication();

    Delta_Console::getInstance()->start();
  }

  /**
   * delta コマンドを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function startDeltaCommand()
  {
    self::$_bootMode = self::BOOT_MODE_COMMAND;
    self::$_configType = self::CONFIG_TYPE_POLICY;

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

  /**
   * ブートローダの起動モードを取得します。
   *
   * @return string ブートローダの起動モードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getBootMode()
  {
    return self::$_bootMode;
  }

  /**
   * ブートローダが Web アプリケーションモードで起動しているかどうかチェックします。
   *
   * @return bool ブートローダが Web アプリケーションモードで起動している場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isBootTypeWeb()
  {
    if (self::getBootMode() == self::BOOT_MODE_WEB) {
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
    if (self::getBootMode() == self::BOOT_MODE_CONSOLE) {
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
    if (self::getBootMode() == self::BOOT_MODE_COMMAND) {
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

    Delta_DIContainerFactory::Initialize();
  }
}

