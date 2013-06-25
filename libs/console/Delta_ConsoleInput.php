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
 * コンソールアプリケーションに渡された入力情報を管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 */

class Delta_ConsoleInput extends Delta_Object
{
  /**
   * @var bool
   */
  private $_isBootTypeConsole;

  /**
   * @var array
   */
  private $_coreOptions = array();

  /**
   * @var string
   */
  private $_commandExecutePath;

  /**
   * @var string
   */
  private $_commandPath;

  /**
   * @var string
   */
  private $_commandName;

  /**
   * @var array
   */
  private $_argumentValues = array();

  /**
   * @var array
   */
  private $_arguments = array();

  /**
   * @var array
   */
  private $_options = array();

  /**
   * @var Delta_ConsoleDialog
   */
  private $_dialog;

  /**
   * コンソールアプリケーションに渡された入力情報を解析します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function parse()
  {
    // deltac (deltac.bat) を取り除く
    $argv = $_SERVER['argv'];
    array_shift($argv);

    $this->_isBootTypeConsole = Delta_BootLoader::isBootTypeConsole();

    // コマンドパスの取得
    if (sizeof($argv))  {
      if ($this->_isBootTypeConsole) {
        $this->parseCoreArgumentsAndOptions($argv);
        $this->parseCommand();

        array_shift($argv);
      }

      $this->parseCommandArgumentsAndOptions($argv);
    }
  }

  /**
   * deltac に渡された引数とオプションを解析します。
   *
   * @param array $argv コンソールから受け取った引数のリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseCoreArgumentsAndOptions(array $argv)
  {
    $coreOptions = array();

    foreach ($argv as $argument) {
      if (substr($argument, 0, 2) === '--') {
        $coreOptions += $this->parseOption($argument);

      // deltac に渡された引数 (通常はコマンド名、及びコマンド引数、オプション) を取得
      } else {
        $this->_commandExecutePath = $argument;
        break;
      }
    }

    $this->_coreOptions = $coreOptions;
  }

  /**
   * コマンドの実行パスを取得します。
   * <code>
   * // './deltac HelloWorld foo bar baz' を実行した場合、文字列で './deltac HelloWorld foo bar baz' を返す
   * $executePath = $input->getExecutePath();
   * </code>
   *
   * @return string コマンドの実行パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExecutePath()
  {
    return implode(' ', $_SERVER['argv']);
  }

  /**
   * 実行するコマンドを解析します。
   *
   * @throws RuntimeException コマンドの書式が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseCommand()
  {
    $commandExecutePath = $this->_commandExecutePath;

    if ($commandExecutePath !== NULL) {
      // ディレクトリパス形式
      if (strpos($commandExecutePath, '/') !== FALSE || strpos($commandExecutePath, '\\') !== FALSE) {
        $commandPath = $commandExecutePath;

      // コマンドパス形式
      } else {
        // 外部パス
        if (($pos = strpos($commandExecutePath, ':')) !== FALSE) {
          $autoloadId = substr($commandExecutePath, 0, $pos);
          $commandPath = substr($commandExecutePath, $pos + 1);

          if ($commandPath === FALSE) {
            $message = sprintf('Command path format is invalid. [%s]', $commandExecutePath);
            throw new RuntimeException($message);
          }

          $basePath = Delta_Config::getApplication()->get('autoload')->get($autoloadId);

          if ($basePath === NULL) {
            $message = sprintf('Can\'t find autoloadId. [%s]', $autoloadId);
            throw new RuntimeException($message);
          }

          $commandPath = sprintf('%s%s%sCommand.php',
            $basePath,
            DIRECTORY_SEPARATOR,
            str_replace('.', '/', $commandPath));

        // 内部パス
        } else {
          $commandPath = sprintf('%s%sconsole%scommands%s%sCommand.php',
            APP_ROOT_DIR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            str_replace('.', '/', $commandExecutePath));
        }
      }

      if (!is_file($commandPath)) {
        $message = sprintf('Can\'t find the path command. [%s]', $commandPath);
        throw new RuntimeException($message);
      }

      $info = pathinfo($commandPath);

      $this->_commandPath = $commandPath;
      $this->_commandName = $info['filename'];

    } // end if
  }

  /**
   * コマンドに渡された引数とオプションを解析します。
   *
   * @param array $argv コンソールから受け取った引数のリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseCommandArgumentsAndOptions(array $argv)
  {
    $argumentValues = array();
    $options = array();

    foreach ($argv as $argument) {
      // 引数の解析
      if (substr($argument, 0, 1) !== '-') {
        $argumentValues[] = $argument;

      // オプションの解析
      } else {
        $options += $this->parseOption($argument);
      }
    }

    $this->_argumentValues = $argumentValues;
    $this->_options = $options;
  }

  /**
   * オプション文字列をキーと値で構成される配列に変換します。
   *
   * @param string $option オプション文字列。
   * @return array オプションのキーと値で構成される配列を返します。
   *   オプションが値を持たない場合、配列値には FALSE が格納されます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseOption($option)
  {
    $array = array();
    $split = explode('=', $option);
    $key = ltrim($split[0], '-');

    if (isset($split[1])) {
      $array[$key] = trim($split[1]);
    } else {
      $array[$key] = FALSE;
    }

    return $array;
  }

  /**
   * {@link Delta_ConsoleCommand::configure()} で設定されたパラメータ条件を元に、コマンドに渡された引数とオプションの妥当性を検証します。
   *
   * @param Delta_ConsoleInputConfigure $configure パラメータ条件オブジェクト。
   * @throws InvalidArgumentException コマンドに渡された引数・オプションの書式が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate(Delta_ConsoleInputConfigure $configure)
  {
    // 引数の検証
    $configureArguments = $configure->getArguments();

    if (sizeof($configureArguments)) {
      $argumentIndex = 0;

      foreach ($configureArguments as $name => $attributes) {
        // 必須引数
        if ($attributes['type'] == Delta_ConsoleInputConfigure::INPUT_REQUIRED) {
          if (empty($this->_argumentValues[$argumentIndex])) {
            $message = sprintf('Please specify argument \'%s\'.', $name);
            throw new InvalidArgumentException($message);
          }

          $this->_arguments[$name] = $this->_argumentValues[$argumentIndex];

        // オプション引数
        } else {
          if (empty($this->_argumentValues[$argumentIndex])) {
            $this->_arguments[$name] = NULL;
          } else {
            $this->_arguments[$name] = $this->_argumentValues[$argumentIndex];
          }
        }

        $argumentIndex++;
      }

      // 引数が多い場合に発生
      if (isset($this->_argumentValues[$argumentIndex])) {
        $message = sprintf('Too many arguments. [%s]', $this->_argumentValues[$argumentIndex]);
        throw new InvalidArgumentException($message);
      }
    }

    // オプションの検証
    $configureOptions = $configure->getOptions();

    foreach ($configureOptions as $name => $attributes) {
      // 引数を持つ
      if ($attributes['type'] == Delta_ConsoleInputConfigure::OPTION_VALUE_HAVE) {
        if ($this->hasOption($name)) {
          $value = $this->getOption($name);

          // 値を持たない
          if ($value === NULL) {
            $message = sprintf('\'-%s\' argument is requires a value.', $name);
            throw new InvalidArgumentException($message);

          // コールバック関数を用いた検証
          } else if (isset($attributes['callback'])) {
            $message = NULL;

            if (!call_user_func_array($attributes['callback'], array($value, &$message))) {
              if ($message === NULL) {
                $message = sprintf('\'-%s=%s\' option is invalid value.', $name, $value);
              }

              throw new InvalidArgumentException($message);
            }
          }
        }

      // 引数を持たない
      } else {
        if ($this->hasOption($name) && $this->getOption($name) !== FALSE) {
          $message = sprintf('\'-%s\' argument can\'t set a value.', $name);
          throw new InvalidArgumentException($message);
        }
      }
    }
  }

  /**
   * コマンドパスを取得します。
   *
   * @return string コマンドパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCommandPath()
  {
    return $this->_commandPath;
  }

  /**
   * コマンド名を取得します。
   *
   * @param bool $withSuffix 接尾辞 'Command' を付加する場合は TRUE を指定。
   * @return string コマンド名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCommandName($withSuffix = FALSE)
  {
    if ($withSuffix) {
      return $this->_commandName;
    }

    return substr($this->_commandName, 0, strrpos($this->_commandName, 'Command'));
  }

  /**
   * コマンドに引数 name が指定されているかチェックします。
   *
   * @param string $name チェック対象の引数名。
   * @return bool 引数が指定されている場合は TRUE、指定されていない場合は FALSE を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasArgument($name)
  {
    return isset($this->_arguments[$name]);
  }

  /**
   * コマンドに指定されている引数 name の値を取得します。
   *
   * @param string $name 取得対象の引数名。
   * @param mixed $alternative 引数が未指定の場合に返す代替値。
   * @return string 引数の値を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getArgument($name, $alternative = NULL)
  {
    if (isset($this->_arguments[$name])) {
      return $this->_arguments[$name];
    }

    return $alternative;
  }

  /**
   * コマンドに指定されている全ての引数を取得します。
   *
   * @return array コマンドに指定されている全ての引数を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getArguments()
  {
    return $this->_arguments;
  }

  /**
   * deltac にオプション name が指定されているかチェックします。
   *
   * @param string $name チェック対象のオプション名。ハイフンの記述は不要。
   * @return bool オプションが指定されている場合は TRUE、指定されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasCoreOption($name)
  {
    return array_key_exists($name, $this->_coreOptions);
  }

  /**
   * deltac に指定されているオプション name の値を取得します。
   *
   * @param string $name 取得対象のオプション名。ハイフンの記述は不要。
   * @return string オプションの値を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCoreOption($name, $alternative = NULL)
  {
    if ($this->hasOption($name)) {
      return $this->_options[$name];
    }

    return $alternative;
  }

  /**
   * deltac に指定されている全てのオプションを取得します。
   *
   * @return array deltac に指定されている全てのオプションを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCoreOptions()
  {
    return $this->_coreOptions;
  }

  /**
   * コマンドにオプション name が指定されているかチェックします。
   *
   * @param string $name チェック対象のオプション名。ハイフンの記述は不要。
   * @return bool オプションが指定されている場合は TRUE、指定されていない場合は FALSE を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasOption($name)
  {
    return array_key_exists($name, $this->_options);
  }

  /**
   * コマンドに指定されているオプション name の値を取得します。
   *
   * @param string $name 取得対象のオプション名。ハイフンの記述は不要。
   * @param mixed $alternative オプションが未指定の場合に返す代替値。
   * @return string オプションの値を返します。{@link Delta_ConsoleInputConfigure::OPTION_VALUE_NONE 値を持たないオプション} は NULL を返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOption($name, $alternative = NULL)
  {
    if ($this->hasOption($name)) {
      return $this->_options[$name];
    }

    return $alternative;
  }

  /**
   * コマンドに指定されている全てのオプションを取得します。
   *
   * @return array コマンドに指定されている全てのオプションを返します。
   * @see Delta_ConsoleCommand::configure()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOptions()
  {
    return $this->_options;
  }

  /**
   * 対話式ダイアログを取得します。
   *
   * @return Delta_ConsoleDialog 対話式ダイアログオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDialog()
  {
    if ($this->_dialog === NULL) {
      $this->_dialog = new Delta_ConsoleDialog();
    }

    return $this->_dialog;
  }
}
