<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * コンソールアプリケーションのコマンド機能を提供します。
 * 全てのコマンドは Delta_ConsoleCommand クラスを継承する必要があります。
 * <code>
 * {console/commands/HelloWorldCommand.php}
 * class HelloWorldCommand extends Delta_ConsoleCommand
 * {
 *   public function execute()
 *   {
 *     $this->getOutput()->writeLine('Hello world!');
 *   }
 * }
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 */

abstract class Delta_ConsoleCommand extends Delta_Object
{
  /**
   * @var Delta_ConsoleInput
   */
  private $_input;

  /**
   * @var Delta_ConsoleOutput
   */
  private $_output;

  /**
   * コンストラクタ。
   *
   * @param Delta_ConsoleInput $input コンソール入力オブジェクト。
   * @param Delta_ConsoleOutput $output コンソール出力オブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_ConsoleInput $input, Delta_ConsoleOutput $output)
  {
    $this->_input = $input;
    $this->_output = $output;
  }

  /**
   * コンソール入力オブジェクトを取得します。
   *
   * @return Delta_ConsoleInput コンソール入力オブジェクトを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInput()
  {
    return $this->_input;
  }

  /**
   * コンソール出力オブジェクトを取得します。
   *
   * @return Delta_ConsoleOutput コンソール出力オブジェクトを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOutput()
  {
    return $this->_output;
  }

  /**
   * コマンドで利用可能な引数とオプションを定義します。
   * このメソッドは {@link execute()} の直前にコールされ、{@link Delta_ConsoleInput::validate()} メソッドによりパラメータの検証が行われます。
   *
   * @param Delta_ConsoleInputConfigure $configure コマンドで利用可能な引数とオプションを管理するオブジェクト。
   * @see Delta_ConsoleInputConfigure
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function configure(Delta_ConsoleInputConfigure $configure)
  {}

  /**
   * コマンドを実行します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function execute();

  /**
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDatabase()
  {
    return Delta_DatabaseManager::getInstance();
  }
}
