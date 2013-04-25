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
 * アプリケーションで発生した例外の原因と発生箇所を {@link Delta_Logger} 経由でファイルシステムや標準出力にロギングします。
 * 本ハンドラを有効にするには、あらかじめ application.yml に次の記述を定義しておく必要があります。
 *
 * application.yml の設定例:
 * <code>
 * exception:
 *   # 対象とする例外。(Exception 指定時は全ての例外を捕捉)
 *   - type: {@link Exception}
 *
 *     # 例外委譲クラスの指定。
 *     delegate: {@link Delta_ExceptionLoggingDelegate}
 *
 *     # ログレベルの指定。(オプション)
 *     level: <?php echo {@link Delta_Logger::LOGGER_MASK_ERROR} ?>
 * </code>
 *
 * application.yml の設定例:
 * <code>
 * # ログアペンダの定義。
 * logger:
 *   errorFileAppender:
 *     mask: <?php echo Delta_Logger::LOGGER_MASK_ERROR ?>
 *     class: {@link Delta_LoggerFileAppender}
 *     file: error.log
 *     rotate:
 *       type: date
 *       datePattern: Y-m
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception.delegate
 */
class Delta_ExceptionLoggingDelegate extends Delta_ExceptionDelegate
{
  /**
   * @see Delta_ExceptionDelegate::catchOnApplication()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected static function catchOnApplication(Exception $exception, Delta_ParameterHolder $holder)
  {
    $level = $holder->getInt('level', Delta_Logger::LOGGER_MASK_ERROR);
    $logger = Delta_Logger::getLogger(get_class($exception));

    call_user_func_array(array($logger, 'send'), array($level, $exception));
  }
}
