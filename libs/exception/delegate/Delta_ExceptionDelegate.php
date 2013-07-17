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
 * {@link Delta_ExceptionHandler} で捕捉した例外を扱うための抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception.delegate
 */

abstract class Delta_ExceptionDelegate extends Delta_Object
{
  /**
   * アプリケーションの実行環境 (Web、またはコンソール) に合わせて例外を扱うためのメソッドを起動します。
   *   o Web アプリケーションの場合: {@link catchOnApplication()}、{@link catchOnWeb()} メソッドを実行します。
   *   o コンソールアプリケーションの場合: {@link catchOnApplication()}、{@link catchOnConsole()} メソッドを実行します。
   *
   * @param Exception $exception {@link Exception}、または Exception を継承した例外オブジェクトのインスタンス。
   * @param Delta_ParameterHolder $holder 例外デリゲートオプション。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function invoker(Exception $exception, Delta_ParameterHolder $holder = NULL)
  {
    if ($holder === NULL) {
      $holder = new Delta_ParameterHolder();
    }

    static::catchOnApplication($exception, $holder);

    if (Delta_BootLoader::isBootTypeWeb()) {
      static::catchOnWeb($exception, $holder);

    } else {
      static::catchOnConsole($exception, $holder);
    }
  }

  /**
   * アプリケーションから例外がスローされた際に、{@link invoker()} メソッドによってコールされます。
   *
   * @see Delta_ExceptionDelegate::invoker()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnApplication(Exception $exception, Delta_ParameterHolder $holder)
  {}

  /**
   * Web アプリケーションで例外がスローされた際に、{@link invoker()} メソッドによってコールされます。
   * このメソッドは、{@link catchOnApplication()} がコールされた後に実行されます。
   *
   * @param Exception $exception {@link invoker()} メソッドを参照。
   * @see Delta_ExceptionDelegate::invoker()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnWeb(Exception $exception, Delta_ParameterHolder $holder)
  {}

  /**
   * コンソールアプリケーションで例外がスローされた際に、{@link invoker()} メソッドによってコールされます。
   * このメソッドは、{@link catchOnApplication()} がコールされた後に実行されます。
   *
   * @param Exception $exception {@link invoker()} メソッドを参照。
   * @see Delta_ExceptionDelegate::invoker()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnConsole(Exception $exception, Delta_ParameterHolder $holder)
  {}
}
