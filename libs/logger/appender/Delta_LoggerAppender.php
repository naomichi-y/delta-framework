<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_Logger} の制御に従ってログを出力するロギングのための抽象クラスです。
 *
 * application.yml の設定例:
 * <code>
 * logger:
 *   # ログアペンダ ID。
 *   {appender_name}:
 *     # ロガークラス名。
 *     class:
 *
 *     # ロギングレベルのビットマスク値。デフォルトでは全てのログレベルを対象とする。
 *     mask: 0
 *
 *     # 例外オブジェクトをロギングする場合の出力形式。
 *     #  - simple: 例外メッセージをシンプルな文字列形式で出力。
 *     #  - trace: 例外のスタックトレースを出力。
 *     exception: simple
 *
 *     # 同じ内容のメッセージが interval 秒以内に送信されようとした場合はロギング処理をスキップします。
 *     # interval 秒以降に同じメッセージが送信された場合、スキップされたログの回数が取得可能です。
 *     interval: 0
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger.appender
 */
abstract class Delta_LoggerAppender extends Delta_Object
{
  /**
   * アペンダ ID。
   * @var string
   */
  protected $_appenderId;

  /**
   * {@link Delta_ParameterHolder} オブジェクト。
   * @var Delta_ParameterHolder
   */
  protected $_holder;

  /**
   * ログの送信が許可されているかどうか。
   * @var bool
   */
  private $_writable = FALSE;

  /**
   * ロギングがスキップされた回数。
   * @var int
   */
  private $_skipCount = 0;

  /**
   * コンストラクタ。
   *
   * @param string $appenderId ログアペンダ ID。
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($appenderId, Delta_ParameterHolder $holder)
  {
    $this->_appenderId = $appenderId;
    $this->_holder = $holder;
  }

  /**
   * ログアペンダに interval 属性を割り当てます。
   *
   * @param mixed $message 出力するメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bindInterval($message)
  {
    $interval = $this->_holder->getInt('interval', 0);

    if ($interval) {
      $cache = Delta_CacheManager::getInstance(Delta_CacheManager::CACHE_TYPE_FILE);
      $hash = md5($this->_appenderId . $message);

      $values = array();
      $values['time'] = $_SERVER['REQUEST_TIME'];

      $lockFile = 'tmp/lock/logger.appender.Delta_LoggerAppender';
      $lock = new Delta_FileLock($lockFile);

      if ($lock->lock()) {
        // 以前に同じログを書き込もうとしたことがあるか
        if ($cache->hasCached($hash, 'logger_interval')) {
          $cacheValues = $cache->get($hash, 'logger_interval');

          // 前回ロギングしてから $interval 秒経過しているか
          if ($_SERVER['REQUEST_TIME'] - $cacheValues['time'] > $interval) {
            $values['count'] = 0;

            $this->_writable = TRUE;
            $this->_skipCount = $cacheValues['count'];

          } else {
            $values['count'] = $cacheValues['count'] + 1;

            $this->_skipCount = $values['count'];
            $this->_writable = FALSE;
          }

        } else {
          $values['count'] = 0;

          $this->_writable = TRUE;
          $this->_skipCount = 0;
        }

        $cache->set($hash, $values, 'logger_interval');
        $lock->unlock();
      }

    } else {
      $this->_writable = TRUE;
      $this->_skipCount = 0;
    }
  }

  /**
   * ログが書き込み可能な状態にあるかどうかチェックします。
   *
   * @return boolean ログの書き込みが可能な場合は TRUE、不可能な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isWritable()
  {
    return $this->_writable;
  }

  /**
   * 'interval' 属性の設定により、ログの書き込みがスキップされた回数を取得します。
   *
   * @return int ログの書き込みがスキップされた回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSkipCount()
  {
    return $this->_skipCount;
  }

  /**
   */
  public function getParameterHolder()
  {
    return $this->_holder;
  }

  /**
   * ログを出力します。
   *
   * @param string $className ロギング対象のクラス名。
   * @param int $type ログのタイプ。Delta_Logger::MASK_* 定数で指定。
   * @param mixed $message 出力するメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function write($className, $type, $message);

  /**
   * ログの出力フォーマットを取得します。
   *
   * @param string $className ロギング対象のクラス名。
   * @param int $type ログのタイプ。Delta_Logger::MASK_* 定数で指定。
   * @param mixed $message 出力するメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLogFormat($className, $type, $message)
  {
    $date = date($this->_holder->getString('dateFormat'));
    $typeName = Delta_Logger::getTypeName($type);
    $buffer = NULL;

    if (is_object($message)) {
      if ($message instanceof Exception) {
        $exception = $this->_holder->getString('exception');

        if ($exception === 'trace') {
          $buffer = Delta_CommonUtils::convertVariableToString($message);

        } else {
          $buffer = sprintf("\"%s\" \"%s\" (Line:%s)",
                            $message->getMessage(),
                            $message->getFile(),
                            $message->getLine());
        }

      } else {
        $buffer = Delta_CommonUtils::convertVariableToString($message);
      }

    } else {
      $buffer = Delta_CommonUtils::convertVariableToString($message);
    }

    $format = sprintf("%s %s [%s] - %s",
                      $date,
                      $typeName,
                      $className,
                      $buffer);

    $skipCount = $this->getSkipCount();

    if ($skipCount) {
      $format .= sprintf(' (%s Messages skipped)', number_format($skipCount));
    }

    return $format;
  }
}
