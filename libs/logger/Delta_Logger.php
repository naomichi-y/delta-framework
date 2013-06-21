<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アプリケーションにおいて任意のメッセージをロギングします。
 * (global_)properties ファイルに logger 属性を定義することで、アペンダ単位でのロギング条件が設定可能になります。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package logger
 */
class Delta_Logger extends Delta_Object
{
  /**
   * ロギングレベル定数。(TRACE)
   */
  const LOGGER_MASK_TRACE = 1;

  /**
   * ロギングレベル定数。(DEBUG)
   */
  const LOGGER_MASK_DEBUG = 2;

  /**
   * ロギングレベル定数。(INFORMATION)
   */
  const LOGGER_MASK_INFO = 4;

  /**
   * ロギングレベル定数。(WARNING)
   */
  const LOGGER_MASK_WARNING = 8;

  /**
   * ロギングレベル定数。(ERROR)
   */
  const LOGGER_MASK_ERROR = 16;

  /**
   * ロギングレベル定数。(FATAL)
   */
  const LOGGER_MASK_FATAL = 32;

  /**
   * ログ定数文字列リスト。
   * @var array
   */
  private static $_loggerTypes = array(
    self::LOGGER_MASK_TRACE => 'TRACE',
    self::LOGGER_MASK_DEBUG => 'DEBUG',
    self::LOGGER_MASK_INFO => 'INFO',
    self::LOGGER_MASK_WARNING => 'WARNING',
    self::LOGGER_MASK_ERROR => 'ERROR',
    self::LOGGER_MASK_FATAL => 'FATAL'
  );

  /**
   * 日付フォーマット。
   * @var string
   */
  private $_dateFormat = 'Y/m/d H:i:s';

  /**
   * getLogger() の引数で指定したクラス名。
   * @var string
   */
  private $_className;

  /**
   * 登録済みアペンダリスト。
   * @var array
   */
  private $_appenders = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * ロギングする日付フォーマットを指定します。
   * フォーマットを指定しない場合のデフォルト書式は 'Y/m/d H:i:s' (例: 2008/1/1 00:00:00) となります。
   *
   * @param string $dateFormat 日付フォーマット。日付文字列の書式は {@link date()} 関数のオプションが指定可能です。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDateFormat($dateFormat)
  {
    $this->_dateFormat = $dateFormat;
  }

  /**
   * Delta_Logger のインスタンスを取得します。
   *
   * @param string $className ロギング対象のクラス名。通常は {@link get_class()} を指定。
   * @param bool $readConfig logger 属性 (application.yml) を元にロガーを構築する場合は TRUE、空のロガーインスタンスを生成する場合は FALSE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getLogger($className, $readConfig = TRUE)
  {
    if ($readConfig) {
      static $instance;

      if ($instance === NULL) {
        $instance = new Delta_Logger();
        $appenders = Delta_Config::getApplication()->get('logger');

        if ($appenders) {
          foreach ($appenders as $appenderId => $holder) {
            $instance->addAppender($appenderId, $holder);
          }
        }
      }

    } else {
      $instance = new Delta_Logger();
    }

    $instance->_className = $className;

    return $instance;
  }

  /**
   * ロガーアペンダを追加します。
   *
   * @param string $appenderId アペンダ ID。
   * @param Delta_ParameterHolder $holder アペンダの属性リスト。'class: {アペンダクラス名}' は必須。
   * @throws Delta_ParseException アペンダクラス ('class' 属性) が未定義の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addAppender($appenderId, Delta_ParameterHolder $holder)
  {
    if ($holder->hasName('class')) {
      $className = $holder->getString('class');

      $loggerAppender = new $className($appenderId, $holder);
      $instance[$className] = $loggerAppender;

      if (!$holder->hasName('dateFormat')) {
        $holder->set('dateFormat', $this->_dateFormat);
      }

      $this->_appenders[] = $loggerAppender;

    } else {
      throw new Delta_ParseException('Appender attribute \'class\' is undefined.');
    }
  }

  /**
   * インスタンス内において、登録されている全てのアペンダを削除します。
   * (設定ファイルから読み込まれたアペンダも対象になります)
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clearAppenders()
  {
    $this->_appenders = array();
  }

  /**
   * メッセージをトレースレベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function trace($message)
  {
    $this->send(self::LOGGER_MASK_TRACE, $message);
  }

  /**
   * メッセージをデバッグレベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function debug($message)
  {
    $this->send(self::LOGGER_MASK_DEBUG, $message);
  }

  /**
   * メッセージを通知レベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function info($message)
  {
    $this->send(self::LOGGER_MASK_INFO, $message);
  }

  /**
   * メッセージを警告レベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function warn($message)
  {
    $this->send(self::LOGGER_MASK_WARNING, $message);
  }

  /**
   * メッセージをエラーレベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function error($message)
  {
    $this->send(self::LOGGER_MASK_ERROR, $message);
  }

  /**
   * メッセージを致命的エラーレベルでロギングします。
   *
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function fatal($message)
  {
    $this->send(self::LOGGER_MASK_FATAL, $message);
  }

  /**
   * ロギングメッセージをアペンダに送信します。
   *
   * @param int $type ログのタイプ。Delta_Logger::LOGGER_MASK_* を指定可能。
   * @param mixed $message ロギングするメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function send($type, $message)
  {
    $appenders = $this->_appenders;

    foreach ($appenders as $appender) {
      $mask = $appender->getParameterHolder()->getInt('mask', 0);

      if ($mask == 0 || $mask & $type) {
        $appender->bindInterval($message);

        if ($appender->isWritable()) {
          $appender->write($this->_className, $type, $message);
        }
      }
    }
  }

  /**
   * ログタイプの名前を取得します。
   *
   * @param int $type ログのタイプ。Delta_Logger::LOGGER_MASK_* を指定可能。
   * @return string ログタイプの名前を返します。{@link Delta_Logger::LOGGER_MASK_FATAL} を指定した場合は文字列 'FATAL' が返されます。
   * @throws Delta_ParseException ログタイプの指定が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getTypeName($type)
  {
    if (isset(self::$_loggerTypes[$type])) {
      return self::$_loggerTypes[$type];
    }

    $message = sprintf('Logger type is illegal. [%s]', $type);
    throw new Delta_ParseException($message);
  }
}
