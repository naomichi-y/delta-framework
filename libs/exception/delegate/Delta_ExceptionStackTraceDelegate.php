<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception.delegate
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アプリケーションで発生した例外をスタックトレース形式で出力します。
 * 本ハンドラを有効にするには、あらかじめ application.yml に次の記述を定義しておく必要があります。
 *
 * <strong>このクラスはデバッグモード有効時のみスタックトレースを出力します。
 * デバッグ無効時は例外メッセージが {@link Delta_ErrorHandler::invokeFatalError()} メソッドに送信される点に注意して下さい。</strong>
 *
 * application.yml の設定例:
 * <code>
 * exception:
 *   # 対象とする例外 (Exception 指定時は全ての例外を捕捉)
 *   - type: Exception
 *
 *     # 例外委譲クラスの指定
 *     delegate: Delta_ExceptionStackTraceDelegate
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception.delegate
 */
class Delta_ExceptionStackTraceDelegate extends Delta_ExceptionDelegate
{
  /**
   * @see Delta_ExceptionDelegate::catchOnApplication()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnApplication(Exception $exception, Delta_ParameterHolder $holder)
  {
    self::clearBuffer();

    if (!Delta_DebugUtils::isDebug()) {
      Delta_ErrorHandler::invokeFatalError(E_ERROR,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine());
    }
  }

  /**
   * バッファに含まれる全てのデータを破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function clearBuffer()
  {
    if (Delta_BootLoader::isBootTypeWeb()) {
      $response = Delta_FrontController::getInstance()->getResponse();
      $response->clear();
    }

    $i = ob_get_level();

    for (; $i > 1; $i--) {
      ob_end_clean();
    }

    ob_start();
  }

  /**
   * Web アプリケーションで発生した例外のスタックトレースを出力します。
   *
   * @see Delta_ExceptionDelegate::catchOnWeb()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnWeb(Exception $exception, Delta_ParameterHolder $holder)
  {
    $internalEncoding = Delta_Config::getApplication()->get('charset.default');
    $trace = $exception->getTrace();
    $message = $exception->getMessage();

    // ソケット通信エラーなど OS から直接エラーが返された場合、例外メッセージが UTF-8 と異なる場合がある
    if (!mb_check_encoding($message, $internalEncoding)) {
      // 可能な限り文字コードの判定を行う
      $detectEncoding = mb_detect_encoding($message, 'ASCII, JIS, UTF-8, EUC-JP, SJIS');
      $message = mb_convert_encoding($message, $internalEncoding, $detectEncoding);
    }

    $inspector = new Delta_CodeInspector();
    $code = $inspector->buildFromException($exception);

    $view = new Delta_View(new Delta_BaseRenderer());
    $view->setAttribute('exception', $exception, FALSE);
    $view->setAttribute('message', $message);

    if ($internalEncoding === $internalEncoding) {
      $trace = $code;
    } else {
      $trace = mb_convert_encoding($code, $internalEncoding, $internalEncoding);
    }

    $view->setAttribute('trace', $trace, FALSE);

    $path = DELTA_ROOT_DIR . '/skeleton/views/exception.php';

    $view->setViewPath($path);
    $view->execute();
  }

  /**
   * コンソールアプリケーションで発生した例外のスタックトレースを出力します。
   *
   * @see Delta_ExceptionDelegate::catchOnConsole()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnConsole(Exception $exception, Delta_ParameterHolder $holder)
  {
    $className = get_class($exception);

    $trace = $exception->getTrace();
    $message = $exception->getMessage();

    $directlyFactor = $trace[0];
    $file = $exception->getFile();
    $line = $exception->getLine();
    $referenceCode = TRUE;

    $buffer = sprintf(
      "%s: %s\n  at %s%s()\n",
      $className,
      $message,
      isset($directlyFactor['class']) ? $directlyFactor['class'] . '#' : '',
      $directlyFactor['function']);

    if (strcmp($className, 'ErrorException') != 0) {
      $buffer .= sprintf("  in %s [Line: %s]\n", $file, $line);
      $applicationFactor = array();

      // DELTA_LIBS_DIR の DIRECTORY_SEPARATOR を OS の標準に合わせておく
      $deltaLibsDir = str_replace('/', DIRECTORY_SEPARATOR, DELTA_LIBS_DIR);

      // フレームワーク内部で例外が発生した場合はトレース情報から実行元を探す
      if (strpos($file, $deltaLibsDir) === 0) {
        foreach ($trace as $stack) {
          if (isset($stack['file']) && strpos($stack['file'], $deltaLibsDir) === 0) {
            continue;
          } else if (isset($stack['class']) && strpos($stack['class'], 'Delta_') === 0) {
            continue;
          }

          $applicationFactor = $stack;
          break;
        }

        if (empty($applicationFactor['file'])) {
          $referenceCode = FALSE;;

        } else {
          $file = $applicationFactor['file'];
          $line = $applicationFactor['line'];
        }

      } else {
        $referenceCode = FALSE;
      }
    }

    if ($referenceCode) {
      $buffer .= sprintf("  (ref: %s [Line: %d])\n", $file, $line);
    }

    $buffer = Delta_ANSIGraphic::build($buffer, Delta_ANSIGraphic::FOREGROUND_RED|Delta_ANSIGraphic::ATTRIBUTE_BOLD);
    error_log($buffer, 4);
  }
}
