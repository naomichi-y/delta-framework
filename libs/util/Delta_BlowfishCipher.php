<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * Blowfish アルゴリズムを用いたデータの暗号化・複合化機能を提供します。
 * <code>
 * $cipher = new Delta_BlowfishCipher();
 *
 * // 初期化ベクトルの指定
 * // CBC モード (デフォルトの暗号化モード) では必須指定
 * $cipher->setInitializationVector('rfehi7f9');
 *
 * // データの暗号化
 * $cipherText = $cipher->encrypt('Hello world!');
 *
 * // 例えば 'i/kgy+I2F6ZH1Ng+AQNziQ==' (Base64 エンコードされた文字列) を返す
 * echo $cipherText;
 *
 * // データの複合化
 * $rawText = $cipher->decrypt($cipherText);
 *
 * // 'Hello world!' を返す
 * echo $rawText;
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util
 */
class Delta_BlowfishCipher extends Delta_Object
{
  /**
   * 暗号モード定数。(ECB)
   */
  const MODE_ECB = 10;

  /**
   * 暗号モード定数。(CBC)
   */
  const MODE_CBC = 11;

  /**
   * パディング方式定数。(パディングを行わない)
   */
  const PADDING_NONE = 20;

  /**
   * パディング方式定数。(RFC1424 で規定された方式)
   */
  const PADDING_RFC = 21;

  /**
   * パディング方式定数。(ブロック長に満たない部分を NULL バイトで埋める)
   */
  const PADDING_ZERO = 22;

  /**
   * @var string
   */
  private $_secretKey;

  /**
   * @var int
   */
  private $_mode = self::MODE_CBC;

  /**
   * @var int
   */
  private $_padding = self::PADDING_RFC;

  /**
   * @var string
   */
  private $_initializationVector;

  /**
   * コンストラクタ。
   *
   * @param string $secretKey 暗号化に使用する秘密鍵。
   *   未指定の場合はアプリケーション固有の秘密鍵 (application.yml に定義された 'secretKey') が使用される。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($secretKey = NULL)
  {
    Delta_CommonUtils::loadVendorLibrary('blowfish/blowfish.php');

    if ($secretKey === NULL) {
      $this->_secretKey = Delta_Config::getApplication()->getString('secretKey');
    } else {
      $this->_secretKey = $secretKey;
    }
  }

  /**
   * 暗号モードを設定します。
   * デフォルトの暗号モードは CBC (MODE_CBC) となります。
   *
   * @param int $mode 暗号モードを MODE_* 定数で指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setMode($mode)
  {
    $this->_mode = $mode;
  }

  /**
   * CBC で利用する初期化ベクトル (IV) を設定します。
   *
   * @param string $initializationVector 初期化ベクトル。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setInitializationVector($initializationVector)
  {
    $this->_initializationVector = $initializationVector;
  }

  /**
   * パディング方式を設定します。
   * デフォルトのパディング方式は RFC 1424 (PADDING_RFC) となります。
   *
   * @param int $padding パディング方式を PADDING_* 定数で指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPadding($padding)
  {
    $this->_padding = $padding;
  }

  /**
   * データを暗号化します。
   * 暗号化モードに CBC を利用する場合は、{@link setInitializationVector() 初期化ベクトル} を設定する必要があります。
   *
   * @param string $string 暗号化するデータ。
   * @param bool $base64encode 暗号化されたデータを Base64 でエンコードする場合は TRUE を指定。
   * @return string 暗号化されたデータ文字列を返します。
   * @throws RuntimeException CBC モードで初期化ベクトルが未設定の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function encrypt($string, $base64encode = TRUE)
  {
    if ($this->_mode == self::MODE_CBC && $this->_initializationVector === NULL) {
      $message = 'IV is not set. (setInitializationVector())';
      throw new RuntimeException($message);
    }

    $result = Blowfish::encrypt($string,
      $this->_secretKey,
      $this->_mode,
      $this->_padding,
      $this->_initializationVector);

    if ($base64encode) {
      $result = base64_encode($result);
    }

    return $result;
  }

  /**
   * 暗号化されたデータを複合します。
   *
   * @param string $string 暗号化されたデータ (または Base64 エンコードされた) 文字列。
   * @param bool $base64decode データが Base64 でエンコードされている場合は TRUE を指定。
   * @return string 複合したデータ文字列を返します。データの複合化に失敗した場合は FALSE を返します。
   * @throws RuntimeException CBC モードで初期化ベクトルが未設定の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function decrypt($string, $base64decode = TRUE)
  {
    $result = FALSE;

    if ($this->_mode == self::MODE_CBC && $this->_initializationVector === NULL) {
      $message = 'IV is not set. (setInitializationVector())';
      throw new RuntimeException($message);
    }

    if ($base64decode) {
      $string = base64_decode($string);
    }

    try {
      $result = Blowfish::decrypt($string,
        $this->_secretKey,
        $this->_mode,
        $this->_padding,
        $this->_initializationVector);

    // 複合化に失敗した場合
    } catch (ErrorException $e) {}

    return $result;
  }
}
