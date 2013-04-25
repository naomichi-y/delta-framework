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
 * フォームから送信された文字列の長さを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_LengthValidator
 *
 *     # 文字列のカウント方式。
 *     # マルチバイト文字としてカウントする場合は TRUE、バイト数でカウントする場合は FALSE を指定。
 *     multibyte: TRUE
 *
 *     # 文字列を構成する最小の長さ。
 *     minLength:
 *
 *     # 文字列長が 'minLength' を満たさない場合に通知するエラーメッセージ。
 *     minLengthError: {default_message}
 *
 *     # 文字列を構成する最大の長さ。
 *     maxLength:
 *
 *     # 文字列長が 'maxLength' を超える場合に通知するエラーメッセージ。
 *     maxLengthError: {default_message}
 *
 *     # 文字列を構成する固定の長さ。
 *     matchLength:
 *
 *     # 文字列長が 'matchLength' 未満、または 'maxLength' を超えた場合に通知するエラーメッセージ。
 *     matchLengthError: {default_message}
 * </code>
 * ※'minLength'、'maxLength'、'matchLength' のいずれかの指定は必須です。また、'minLength' と 'maxLength' は同時に指定することもできます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_LengthValidator extends Delta_Validator
{
  /**
   * @throws Delta_ConfigurationException 必須属性がビヘイビアに定義されていない場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    if ($holder->getBoolean('multibyte', TRUE)) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
      $length = mb_strlen($value, $encoding);
    } else {
      $length = strlen($value);
    }

    if ($length == 0) {
      return TRUE;
    }

    if ($holder->hasName('matchLength')) {
      if ($holder->getInt('matchLength') != $length) {
        $message = $holder->getString('matchLengthError');

        if ($message === NULL) {
          $message = sprintf('Length of the character is not matched. [%s]', $fieldName);
        }

        $this->sendError($fieldName, $message);

        return FALSE;
      }

    } else {
      $hasMinLength = $holder->hasName('minLength');
      $hasMaxLength = $holder->hasName('maxLength');

      if ($hasMinLength && $length < $holder->getInt('minLength')) {
        $message = $holder->getString('minLengthError');

        if ($message === NULL) {
          $message = sprintf('Character is too short. [%s]', $fieldName);
        }

        $this->sendError($fieldName, $message);

        return FALSE;

      } else if ($hasMaxLength && $length > $holder->getInt('maxLength')) {
        $message = $holder->getString('maxLengthError');

        if ($message === NULL) {
          $message = sprintf('Character is too long. [%s]', $fieldName);
        }

        $this->sendError($fieldName, $message);

        return FALSE;
      }

      if (!$hasMinLength && !$hasMaxLength) {
        $message = sprintf('\'minLength\' or \'maxLength\' or \'matchLength\' validator attribute is undefined.');
        throw new Delta_ConfigurationException($message);
      }
    }

    return TRUE;
  }
}
