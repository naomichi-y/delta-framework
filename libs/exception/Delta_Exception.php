<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * delta から発生する可能性がある例外の基底クラスです。
 * フレームワークのコアライブラリが全て Delta_Exception の子クラスの例外をスローする訳ではない点に注意して下さい。
 * 一部の例外は {@link http://www.php.net/manual/spl.exceptions.php SPL 例外} をスローさせる場合があります。
 *
 * フレームワークが提供する例外クラスでは補えない例外がある場合、独自の例外クラスを作成することも可能です。
 * 独自の例外クラスを作成する場合は {@link Delta_Exception} を基底クラスとすることを推奨します。
 * また、新しい例外クラスを作成する際は、SPL 例外に類似するクラスがないか確認しておくべきです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package exception
 */
class Delta_Exception extends Exception
{
  /**
   * トリガーコード定数。(PHP)
   */
  const TRIGGER_CODE_TYPE_PHP = 'php';

  /**
   * @var array
   */
  private $_attributes = array();

  /**
   * @var string
   */
  private $_triggerCode;

  /**
   * @var string
   */
  private $_triggerCodeType;

  /**
   * @var int
   */
  private $_triggerLine;

  /**
   * コンストラクタ。
   *
   * @param string $message スローする例外メッセージ。
   * @param int $code 例外コード。
   * @param Exception $previous 以前に使われた例外。
   * @see Exception::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($message = NULL, $code = 0, Exception $previous = NULL)
  {
    if ($message === NULL) {
      // Delta_xxxException から 'xxx' の要素名を取得
      $className = get_class($this);
      $pos = strpos($className, 'Exception');

      if ($pos !== FALSE) {
        $exceptionName = substr($className, 0, $pos);
        $prefix = 'Delta_';

        $pos = strpos($exceptionName, $prefix);

        if ($pos !== FALSE) {
          $exceptionName = substr($exceptionName, strlen($prefix));
        }

      } else {
        $exceptionName = $className;
      }

      $message = $exceptionName . ' exception occurred.';
    }

    parent::__construct($message, $code, $previous);
  }

  /**
   * 例外が発生したファイルを設定します。
   *
   * @param int $file 例外が発生したファイル。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFile($file)
  {
    $this->file = $file;
  }

  /**
   * 例外が発生した行を設定します。
   *
   * @param int $line 例外が発生した行。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setLine($line)
  {
    $this->line = $line;
  }

  /**
   * 例外に属性を設定します。
   *
   * @param string $name 設定する属性の名前。
   * @param mixed $value 属性が持つ値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttribute($name, $value)
  {
    $this->_attributes[$name] = $value;
  }

  /**
   * 例外に指定した属性が設定されているかチェックします。
   *
   * @param string $name チェック対象の属性名。
   * @return bool 指定した属性が設定されている場合は TRUE、未設定の場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttribute($name)
  {
    return array_key_exists($name, $this->_attributes);
  }

  /**
   * 例外に設定されている属性を取得します。
   *
   * @param string $name 取得する属性名。
   * @return mixed 属性が持つ値を返します。属性が存在しない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttribute($name)
  {
    $attribute = NULL;

    if (isset($this->_attributes[$name])) {
      $attribute = $this->_attributes[$name];
    }

    return $attribute;
  }

  /**
   * 例外に設定されている全ての属性を取得します。
   *
   * @return array 例外に設定されている全ての属性を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }

  /**
   * 例外を引き起こしたトリガーとなるコードを設定します。
   *
   * @param string $triggerCode トリガーコード。
   * @param string $triggerCodeType コードタイプ。TRIGGER_CODE_TYPE_* 定数を指定。
   * @param string $triggerLine トリガーコードに含まれるエラー行。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTrigger($triggerCode, $triggerCodeType = self::TRIGGER_CODE_TYPE_PHP, $triggerLine = NULL)
  {
    $this->_triggerCode = $triggerCode;
    $this->_triggerCodeType = $triggerCodeType;
    $this->_triggerLine = $triggerLine;
  }

  /**
   * 例外にトリガーコードが設定されているかどうかチェックします。
   *
   * @return bool トリガーコードが設定されている場合は TRUE、設定されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasTrigger()
  {
    $result = FALSE;

    if ($this->_triggerCode !== NULL) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 例外に設定されたトリガーコードを取得します。
   *
   * @return string トリガーコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTriggerCode()
  {
    return $this->_triggerCode;
  }

  /**
   * トリガーコードのタイプを取得します。
   *
   * @return string トリガーコードのタイプを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTriggerCodeType()
  {
    return $this->_triggerCodeType;
  }

  /**
   * トリガーコードに含まれるエラー行数を取得します。
   *
   * @return int トリガーコードに含まれるエラー行数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTriggerLine()
  {
    return $this->_triggerLine;
  }
}

