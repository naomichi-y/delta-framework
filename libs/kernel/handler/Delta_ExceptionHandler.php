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
 * アプリケーションで発生した全ての例外を捕捉する例外ハンドラーです。
 * 例外を独自にハンドリングしたい場合は、{@link Delta_ExceptionDelegate} を実装したクラスを作成する必要があります。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.handler
 */

class Delta_ExceptionHandler
{
  /**
   * 例外発生時にハンドリングされるメソッドです。
   * 発生した例外をキャッチする子ハンドラが存在する場合は処理を子ハンドラに移します。
   *
   * @param Exception $exception {@link Exception}、または Exception を継承した例外オブジェクトのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function handler(Exception $exception)
  {
    try {
      $exceptionConfig = Delta_Config::getApplication()->get('exception');
      $match = FALSE;

      if (sizeof($exceptionConfig)) {
        foreach ($exceptionConfig as $attributes) {
          $types = explode(',', $attributes->get('type'));
          $match = FALSE;

          foreach ($types as $type) {
            $type = trim($type);

            if (class_exists($type) && $exception instanceof $type) {
              $match = TRUE;
              break;
            }
          }

          if ($match) {
            $callback = array($attributes->get('delegate'), 'invoker');
            forward_static_call($callback, $exception, $attributes);

            // 例外の継続
            if (!$attributes->getBoolean('continue')) {
              break;
            }
          }
        }

      } else {
        $message ="Application exception occurred. "
                 ."But exception delegate is undefined. Please define a delegate.\n";
        Delta_ErrorHandler::invokeFatalError(E_ERROR, $message, __FILE__, __LINE__);
      }

      if (Delta_BootLoader::isBootTypeWeb()) {
        $buffer = ob_get_contents();
        ob_end_clean();

        $arguments = array($buffer);
        Delta_KernelEventObserver::getInstance()->dispatchEvent('preOutput', $arguments);
      }

    // 例外ハンドラ内で起こる全ての例外を捕捉
    } catch (Exception $e2) {
      try {
        // 可能な限りスタックトレースを出力
        Delta_ExceptionStackTraceDelegate::invoker($e2);

      } catch (Exception $e3) {
        Delta_ErrorHandler::invokeFatalError(
          E_ERROR,
          $exception->getMessage(),
          $exception->getFile(),
          $exception->getLine()
        );
      }
    }

    // 例外ハンドリング後に "Fatal error" が生成されないようプログラムを強制終了する
    // 'require' はファイルが見つからない場合に 'Warning' と 'Fatal' エラーを生成するが、
    // Delta_ErrorHandler::handler() が検知するのは Warning であり、Fatal を検知するのは
    // Delta_ErrorHandler::detectFatalError() メソッドとなる
    die();
  }
}
