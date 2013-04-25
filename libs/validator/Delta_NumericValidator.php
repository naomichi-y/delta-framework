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
 * フォームから送信された文字列が数値として正当なものであるかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_NumericValidator
 *
 *     # 小数点以下の入力を許可する場合は TRUE を指定。
 *     float: FLASE
 *
 *     # 許可されない文字が含まれた場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_NumericValidator extends Delta_Validator
{
  /**
   * 数値の書式が正当なものであるかチェックします。
   *
   * @param string $value チェック対象の数値。
   * @param bool $float 小数点以下の入力を許可する場合は TRUE。
   * @return bool 数値の書式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $float = FALSE)
  {
    if (is_numeric($value) && ($float || (!$float && strpos($value, '.') === FALSE))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);
    $float = $holder->getBoolean('float');

    if (strlen($value) == 0 || self::isValid($value, $float)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('Numeric format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
