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
 * 電話番号の書式が正当なものであるかどうか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_PhoneNumberValidator
 *
 *     # 対象とする国。(現在は 'jp' のみ対応)
 *     country: jp
 *
 *     # 電話番号フィールド 1
 *     number1:
 *
 *     # 電話番号フィールド 2
 *     number2:
 *
 *     # 電話番号フィールド 3
 *     number3:
 *
 *     # ハイフンを許可するか ('number*' を指定した場合は無効)
 *     hyphnate: TRUE
 *
 *     # 電話番号の書式が不正な場合に通知するメッセージ。
 *     phoneNumberError: {default_message}
 * </code>
 * o 'number*' が未指定の場合は、{validator_id} フィールドを用いた検証が実行されます。
 * o 現在のところ、国際番号はサポートしていません。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */

class Delta_PhoneNumberValidator extends Delta_Validator
{
  /**
   * 国別のハイフンを含む電話番号パターン、ハイフンを含まない電話番号パターンリスト。
   * @var array
   */
  private static $_patterns = array(
    'jp' => array('/^\d{1,5}-\d{1,5}-\d{1,5}$/', '/^\d{10,11}$/')
  );

  /**
   * 電話番号の形式が正当なものであるかどうかチェックします。
   *
   * @param string $value チェック対象の電話番号。
   * @param bool $hyphnate ハイフンを許可するかどうか。
   * @return bool 電話番号の形式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $hyphnate = TRUE)
  {
    $numberLength = strlen($value);
    $result = NULL;

    if ($hyphnate) {
      if ($numberLength != 12 && $numberLength != 13) {
        $result = FALSE;
      }
    } else {
      // 固定電話 (10 桁)、携帯電話 (11 桁) の長さチェック
      if ($numberLength != 10 && $numberLength != 11) {
        $result = FALSE;
      }
    }

    if ($result === NULL) {
      if ($hyphnate) {
        $pattern = self::$_patterns['jp'][0];
      } else {
        $pattern = self::$_patterns['jp'][1];
      }

      $result = preg_match($pattern, $value);
    }

    return $result;
  }

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $form = Delta_ActionForm::getInstance();
    $holder = $this->buildParameterHolder($variables);

    $number1 = $form->get($holder->getString('number1'));
    $number2 = $form->get($holder->getString('number2'));
    $number3 = $form->get($holder->getString('number3'));

    $hyphnate = $holder->getBoolean('hyphnate', FALSE);

    if (strlen($number1 . $number2 . $number3)) {
      $fullPhoneNumber = sprintf('%s-%s-%s', $number1, $number2, $number3);
      $hyphnate = TRUE;

    } else {
      $fullPhoneNumber = $value;
    }

    if (!self::isValid($fullPhoneNumber, $hyphnate)) {
      $message = $holder->get('phoneNumberError');

      if ($message === NULL) {
        $message = sprintf('Format of phone number is invalid. [%s]', $fieldName);
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    return TRUE;
  }
}
