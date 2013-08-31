<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

require DELTA_LIBS_DIR . '/console/Delta_ConsoleCommand.php';
require DELTA_LIBS_DIR . '/console/Delta_ConsoleInput.php';
require DELTA_LIBS_DIR . '/console/Delta_ConsoleInputConfigure.php';
require DELTA_LIBS_DIR . '/console/Delta_ConsoleOutput.php';

/**
 * コンソールアプリケーションのためのコマンドラインインタフェースを提供します。
 * コンソールで './deltac' (Windows 環境の場合は ./deltac.bat) を実行することでコマンドの起動方法を確認することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 */

class Delta_Console extends Delta_Object
{
  /**
   * オブザーバオブジェクト。
   * @var Delta_KernelEventObserver
   */
  private $_observer;

  /**
   * @var string
   */
  private $_commandName;

  /**
   * コンストラクタ。
   *
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {
    $this->_observer = new Delta_KernelEventObserver(Delta_BootLoader::BOOT_MODE_CONSOLE);
    $this->_observer->initialize();
  }

  /**
   * コンソールのインスタンスオブジェクトを取得します。
   *
   * @return Delta_Console コンソールのインスタンスオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_Console();
    }

    return $instance;
  }

  /**
   * コンソールアプリケーションを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function start()
  {
    $input = new Delta_ConsoleInput();
    $input->parse();

    $commandPath = $input->getCommandPath();

    // コマンドパスが見つからない場合はヘルプを表示
    if ($commandPath !== NULL) {
      require $commandPath;

      $this->_commandName = $input->getCommandName(TRUE);
      $output = new Delta_ConsoleOutput();
      $configure = new Delta_ConsoleInputConfigure();

      if ($input->hasCoreOption('silent')) {
        $output->setSilentMode(TRUE);
      }

      $commandClass = new $this->_commandName($input, $output);
      $commandClass->configure($configure);

      $input->validate($configure);
      $commandClass->execute();

      $this->_observer->dispatchEvent('postProcess');

    } else {
      $this->showUsage();
    }
  }

  /**
   * 実行中のコマンド名を取得します。
   *
   * @return string 実行中のコマンド名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCommandName()
  {
    return $this->_commandName;
  }

  /**
   * コンソールアプリケーションに関するヘルプを表示します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function showUsage()
  {
    if (stripos(PHP_OS, 'WIN') !== FALSE) {
      $commandName = 'deltac';
    } else {
      $commandName = 'deltac.bat';
    }

    $message = sprintf("USAGE:\n"
      ."  ./%s [DELTAC_OPTIONS] [ARGUMENT] [COMMAND_OPTIONS]\n"
      ."\n"
      ."DELTAC OPTIONS:\n"
      ."  --silent\n"
      ."    Hide all output messages.\n\n"
      ."  --help\n"
      ."    Show how to use the command.\n\n"
      ."ARGUMENT:\n"
      ."  {command_path} ({argument} {argument}...}\n"
      ."    Run the '{APP_ROOT_DIR}/console/commands/{command_path}Command.php' command.\n"
      ."    If you want to run 'foo/bar/BazCommand.php', please specified 'foo.bar.Baz' or file path.\n\n"
      ."  {autoload_id}:{command_path} ({argument} {argument}...)\n"
      ."    Run the command as defined in the 'autoload' path. (see application.yml)\n\n"
      ."COMMAND OPTIONS:\n"
      ."  --{option_name} (-{option_name})\n"
      ."    Set optional argument with key.\n\n"
      ."  --{option_name}={option_value} (-{option_name}={option_value})\n"
      ."    Set optional argument with key-value.",
      $commandName,
      $commandName);

    $output = new Delta_ConsoleOutput();
    $output->writeLine($message);
  }
}
