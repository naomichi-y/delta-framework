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
 * フォームから送信された文字列が指定範囲内の数値に収まっているかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_RangeValidator
 *
 *     # 有効数値の下限値。
 *     min:
 *
 *     # 有効数値の上限値。
 *     max:
 *
 *     # 小数点以下の入力を許可する場合は TRUE を指定。
 *     float:
 *
 *     # 数値が指定範囲外の場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_RangeValidator extends Delta_Validator
{
  /**
   * @throws Delta_ConfigurationException 必須属性がビヘイビアに定義されていない場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    if (strlen($value) == 0) {
      return TRUE;
    }

    // 整数値であるか検証
    if (is_numeric($value)) {
      $float = $holder->getBoolean('float');

      if ($float || (!$float && strpos($value, '.') === FALSE)) {
        // min、max 値による範囲検証
        if ($holder->hasName('min') && $holder->hasName('max')) {
          if ($holder->getInt('min') <= $value && $value <= $holder->getInt('max')) {
            return TRUE;
          }

        } else {
          $message = sprintf('\'min\' and \'max\' attribute is undefined.');
          throw new Delta_ConfigurationException($message);
        }
      }
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('Out of range value was specified. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
