<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.handler
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * PHP で発生するエラーを {@link ErrorException} オブジェクトに変換します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.handler
 */

class Delta_ErrorHandler
{
  /**
   * アプリケーション内で発生した PHP エラーを {@link ErrorException} オブジェクトに変換し、{@link Delta_ExceptionHandler::invoker()} に例外を送信します。
   *
   * @param int $type エラータイプ。E_* 定数を指定。
   * @param string $message 出力するエラーメッセージ。
   * @param string $file エラーが発生したファイル。
   * @param int $line エラーが発生した行数。
   * @param array $context エラーが発生したスコープ内の全ての変数を格納した配列。
   * @return bool TRUE を返す。(PHP 内部のエラーハンドラをコールしない)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function handler($type, $message, $file, $line, array $context = array())
  {
    // @エラー制御演算子付きで関数が実行された場合、error_reporting() は 0 を返す (エラー扱いとしない)
    if (error_reporting() != 0) {
      $catchLevel = Delta_Config::getApplication()->getInt('error.catchLevel');

      if ($catchLevel & $type) {
        throw new ErrorException($message, 0, $type, $file, $line);
      }
    }

    return TRUE;
  }

  /**
   * 実行時に発生した致命的なエラーを検知し、エラーが見つかった場合は {@liink invokeFatalError()} メソッドをコールします。
   *
   * 致命的なエラーと見なされるエラータイプ:
   *   - E_ERROR,
   *   - E_PARSE,
   *   - E_CORE_ERROR,
   *   - E_CORE_WARNING,
   *   - E_COMPILE_ERROR,
   *   - E_COMPILE_WARNING
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function detectFatalError()
  {
    $error = error_get_last();

    if (empty($error)) {
      return;
    }

    $fatals = array(
      E_ERROR,
      E_PARSE,
      E_CORE_ERROR,
      E_CORE_WARNING,
      E_COMPILE_ERROR,
      E_COMPILE_WARNING
    );

    if (in_array($error['type'], $fatals)) {
      self::invokeFatalError($error['type'], $error['message'], $error['file'], $error['line']);
    }
  }

  /**
   * フレームワーク内部で発生した致命的なエラーを SAPI の出力ハンドラに送信し、プログラムを終了します。
   * Web から実行した場合は SAPI の送信と同時にエラーメッセージをブラウザに出力し、クライアントへ HTTP ステータス 500 を返します。
   *
   * @param int $type エラータイプ。E_* 定数を指定。
   * @param string $message 出力するエラーメッセージ。
   * @param string $file エラーが発生したファイル名。
   * @param string $line エラーが発生した行数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function invokeFatalError($type, $message, $file, $line)
  {
    // PHP が出力する Fatal error メッセージを破棄
    ob_end_clean();

    if (Delta_BootLoader::isBootTypeWeb()) {
      // セッションハンドラを利用している場合、セッションを先に終了しておかないと (write() メソッドで) 致命的なエラーが発生する
      session_write_close();
    }

    $title = 'Detected application error!';
    $errorTypes = array(
      E_ERROR => 'Error',
      E_WARNING => 'Warning',
      E_PARSE => 'Parse',
      E_NOTICE => 'Notice',
      E_CORE_ERROR => 'Core error',
      E_CORE_WARNING => 'Core warning',
      E_COMPILE_ERROR => 'Compile error',
      E_COMPILE_WARNING => 'Compile warning',
      E_USER_ERROR => 'User error',
      E_USER_WARNING => 'User warning',
      E_USER_NOTICE => 'User notice',
      E_STRICT => 'Strict',
      E_RECOVERABLE_ERROR => 'Recoverable error',
      E_DEPRECATED => 'Deprecated',
      E_USER_DEPRECATED => 'User deprecated',
      E_ALL => 'ALL'
    );

    if (!isset($errorTypes[$type])) {
      $type = E_ERROR;
    }

    if (Delta_BootLoader::isBootTypeWeb()) {
      if (!headers_sent()) {
        header('HTTP/1.0 500 Internal Server Error');
      }

      if (ini_get('display_errors')) {
        $path = APP_ROOT_DIR . '/templates/html/fatal_error.php';

        if (!is_file($path)) {
          $path = DELTA_ROOT_DIR . '/skeleton/templates/fatal_error.php';
        }

        $options = array('format' => array('target' => $line));
        $variables = array(
          'title' => htmlentities($title),
          'type' => $errorTypes[$type],
          'message' => htmlentities($message),
          'file' => $file,
          'line' => $line,
        );

        // eval() でエラーが起きた場合、$file には ': eval()'d code' という文字列が含まれる
        if (is_file($file)) {
          $variables['code'] = Delta_DebugUtils::syntaxHighlight(Delta_FileUtils::readFile($file), $options);

        // Delta_PHPStringParser::parse() で Fatal エラーが発生 (eval() エラー)
        } else {
          $registry = Delta_Object::getRegistry();

          if ($registry->hasName('parseCode')) {
            $variables['code'] = Delta_DebugUtils::syntaxHighlight($registry->get('parseCode')->getCode(), $options);
          }
        }

        $require = function($variables, $path) {
          extract($variables);
          require $path;
        };

        $require($variables, $path);
      }

      // SAPI のログ出力ハンドラにメッセージを送信
      $message = sprintf('%s: %s [%s#Line: %s]', $title, $message, $file, $line);
      error_log($message, 4);

    } else {
      $buffer = sprintf("%s: %s\n"
        ."  Type: %s\n"
        ."  File: %s [Line: %s]\n",
        $title, $message, $errorTypes[$type], $file, $line);

      $output = new Delta_ConsoleOutput();
      $output->errorLine($buffer);
    }

    die();
  }
}
