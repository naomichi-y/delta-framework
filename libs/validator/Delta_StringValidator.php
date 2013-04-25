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
 * フォームから送信された文字列が英数字で構成されているか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_StringValidator
 *
 *     # 文字列が英字で構成されているかどうかを検証する。
 *     alphabet: FALSE
 *
 *     # 文字列が数値で構成されているかどうかを検証する。
 *     numeric: FALSE
 *
 *     # 文字列に許可されない文字が含まれる場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 * ※'alphabet'、'numeric' の両方が TRUE の場合、文字列が英数字で構成されているかどうかをチェックします。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_StringValidator extends Delta_Validator
{
  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    if (strlen($value) == 0) {
      return TRUE;
    }

    $alphabet = $holder->getBoolean('alphabet', TRUE);
    $numeric = $holder->getBoolean('numeric', TRUE);

    if ($alphabet) {
      if ($numeric) {
        if (ctype_alnum($value)) {
          return TRUE;
        }

      } else if (ctype_alpha($value)) {
        return TRUE;
      }

    } else if (ctype_digit($value)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('String format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
