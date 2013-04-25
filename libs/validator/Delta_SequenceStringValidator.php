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
 */
class Delta_SequenceStringValidator extends Delta_Validator
{
  /**
   * 文字列 value に連続した文字が含まれるかどうかチェックします。
   *
   * @param string $value チェック対象の文字列。
   * @param int $size 連続した文字をエラーと見なす文字数。
   * @param bool $multibyte マルチバイト文字を対象とするか。
   * @return bool 連続した文字が含まれない (size 以下の) 場合に TRUE、含まれる場合に FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $size, $multibyte = FALSE)
  {
    $holder = $this->buildParameterHolder($variables);
    $regexpOption = NULL;

    if ($multibyte) {
      if (mb_detect_encoding($value) != 'UTF-8') {
        $fromEncoding = Delta_Config::getApplication()->get('charset.default');
        $value = mb_convert_encoding($value, 'UTF-8', $fromEncoding);
      }

      $regexpOption = 'u';
    }

    $regexp = sprintf('/(.)\1{%s,}/%s', $size - 1, $regexpOption);

    if (preg_match($regexp, $value)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @throws Delta_ConfigurationException 必須属性がビヘイビアに定義されていない場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    if (strlen($value) == 0) {
      return TRUE;
    }

    $size = $holder->getInt('size');
    $multibyte = $holder->getBoolean('multibyte');

    if ($size === NULL) {
      $message = 'Undefined \'size\' attribute.';
      throw new Delta_ConfigurationException($message);
    }

    if ($this->isValid($value, $size, $multibyte)) {
      return TRUE;
    }

    $message = $holder->getString('sizeError');

    if ($message === NULL) {
      $message = sprintf('Contains consecutive letters at %s characters.', $size);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
