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
 * IP アドレスのフォーマットが正当なものであるか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_IPAddressValidator
 *
 *     # IP アドレスのフォーマットが不正な場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_IPAddressValidator extends Delta_Validator
{
  /**
   * IP アドレスの書式が正当なものであるかチェックします。
   *
   * @param string $value チェック対象の IP アドレス。
   * @return bool IP アドレスの書式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value)
  {
    $verify = long2ip(ip2long($value));

    if ($value === $verify) {
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

    if (strlen($value) == 0 || self::isValid($value)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('IP Address format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
