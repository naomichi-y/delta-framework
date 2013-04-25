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
 * フォームから送信されたチェックボックスの状態を検証します。
 * チェック可能なフィールドタイプは、checkbox、または radio のいずれかとなります。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_CheckBoxValidator
 *
 *     # 送信可能なチェック数。
 *     # 指定された数より実際のチェック数が多い、または少ない場合はエラーとなる。
 *     requiredMatch:
 *
 *     # 送信されたチェック数が requiredMatch に一致しない場合に通知するエラーメッセージ。
 *     requiredMatchError: {default_message}
 *
 *     # 送信を許可する最低チェック数。
 *     requiredMin:
 *
 *     # 送信されたチェック数が requiredMin 以下の場合に通知するエラーメッセージ。
 *     requiredMinError: {default_message}
 *
 *     # 送信可能な最大チェック数。
 *     requiredMax:
 *
 *     # 送信されたチェック数が requiredMax を超えた場合に通知するエラーメッセージ。
 *     requiredMaxError: {default_message}
 * </code>
 * ※: 'requiredMatch'、'requiredMin'、'requiredMax' のいずれかの指定が必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_CheckBoxValidator extends Delta_Validator
{
  /**
   * @throws Delta_ConfigurationException 必須属性がビヘイビアに定義されていない場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);
    $size = sizeof($value);
    $message = NULL;

    if ($holder->hasName('requiredMatch')) {
      if ($holder->getInt('requiredMatch') != $size) {
        $message = $holder->getString('requiredMatchError');

        if ($message === NULL) {
          $message = sprintf('Check number does not match. [%s]', $fieldName);
        }
      }

    } else if ($holder->hasName('requiredMin')) {
      if ($holder->getInt('requiredMin') > $size) {
        $message = $holder->getString('requiredMinError');

        if ($message === NULL) {
          $message = sprintf('Check number does not satisfy. [%s]', $fieldName);
        }
      }

    } else if ($holder->hasName('requiredMax')) {
      if ($holder->getInt('requiredMax') < $size) {
        $message = $holder->getString('requiredMaxError');

        if ($message === NULL) {
          $message = sprintf('Check number is beyond the upper limit. [%s]', $fieldName);
        }
      }

    } else {
      $message = sprintf('\'requiredMatch\' or \'requiredMin\' or \'requiredMax\' validator attribute is undefined.');
      throw new Delta_ConfigurationException($message);
    }

    if ($message) {
      $this->sendError($fieldName, $message);
      return FALSE;
    }

    return TRUE;
  }
}
