<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 同じ文字列の連続性を検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_SequenceStringValidator
 *
 *     # 連続した文字をエラーと見なす文字数。
 *     size:
 *
 *     # マルチバイト文字を対象とするかどうか。
 *     multibyte: FALSE
 *
 *     # 同じ文字が 'size' 以上連続した場合に通知するエラーメッセージ。
 *     sizeError: {default_message}
 * </code>
 * ※'size' 属性の指定は必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 マニュアル更新
 */
class Delta_SequenceStringValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'sequenceString';

  /**
   * @throws Delta_ConfigurationException 必須属性が未指定の場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    $size = $this->_conditions->getString('size');
    $multibyte = $this->_conditions->getBoolean('multibyte');

    if ($size === NULL) {
      $message = 'Required attribute is not specified. [size]';
      throw new Delta_ConfigurationException($message);
    }

    // マルチバイトの連続文字をチェックするかどうか
    $regexpOption = NULL;

    if ($multibyte) {
      $detectEncoding = mb_detect_encoding($this->_fieldValue);

      if ($detectEncoding !== 'UTF-8') {
        $value = mb_convert_encoding($value, 'UTF-8', $detectEncoding);
      }

      $regexpOption = 'u';
    }

    $regexp = sprintf('/(.)\1{%s,}/%s', $size - 1, $regexpOption);

    if (preg_match($regexp, $this->_fieldValue)) {
      $this->setError('sizeError');
      $result = FALSE;
    }

    return FALSE;
  }
}
