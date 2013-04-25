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
 * ドメインのフォーマットが正当なものであるか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_DomainValidator
 *
 *     # TRUE を指定した場合、ドメイン名が実在するものかどうか DNS (A、CNAME レコード) に問い合わせを行う。
 *     # Windows 環境では動作しないためチェックはスローされます。
 *     domainCheck: FALSE
 *
 *     # ドメインのフォーマットが不正な場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_DomainValidator extends Delta_Validator
{
  /**
   * ドメインチェックパターン。
   */
  const DOMAIN_PATTERN = '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/';

  /**
   * ドメインの書式が正当なものであるかチェックします。
   *
   * @param string $value チェック対象のドメイン名。
   * @param bool $domainCheck ドメインが存在するか DNS レコード (A、CNAME) のチェックを行う。(Windows では動作しないため、必ず TRUE を返す)
   * @return bool ドメインの書式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $domainCheck = FALSE)
  {
    if (preg_match(self::DOMAIN_PATTERN, $value)) {
      if ($domainCheck && function_exists('checkdnsrr')) {
        if (checkdnsrr($value, 'A') || checkdnsrr($value, 'CNAME')) {
          return TRUE;
        }

      } else {
        return TRUE;
      }
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
    $domainCheck = $holder->getBoolean('domainCheck');

    if (strlen($value) == 0 || self::isValid($value, $domainCheck)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('Domain format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
