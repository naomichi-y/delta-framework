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
 * メールアドレスのフォーマットが正当なものであるかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_EMailValidator
 *
 *     # TRUE を指定した場合、RFC822 に準拠した厳格なパターンチェックを行う。
 *     # 尚、携帯電話のベンダーによって、RFC に準拠していないアドレスパターンが一部使用される可能性がある。
 *     #   - DoCoMo: @ の直前で '.' が使用可能
 *     #   - Vodafone: '/'、'+'、'?' といった文字が使用可能
 *     # RFC に準拠した検証を行う際は、一部のキャリアで検証エラーが発生しうる点に注意が必要です。
 *     strict: FALSE
 *
 *     # TRUE を指定した場合、ドメイン名が実在するものかどうか DNS (A、MX、CNAME レコード) に問い合わせを行う。
 *     # Windows 環境では動作しないためチェックはスローされます。
 *     domainCheck: FALSE
 *
 *     # メールアドレスのフォーマットが不正な場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @link http://www.ietf.org/rfc/rfc0822.txt Standard for ARPA Internet Text Messages
 * @link http://www.din.or.jp/~ohzaki/perl.htm#Mail メールアドレスの正規表現
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_EMailValidator extends Delta_Validator
{
  /**
   * 曖昧な E-Mail アドレスの正規表現パターン。
   */
  const EMAIL_TRANSITIONAL_PATTERN = '/^(([a-z0-9!#*+\/=?^_{|}~-]+(?:\.+[a-z0-9!#*+\/=?^_{|}~-]+)*)\.*\@((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?))$/';

  /**
   * 厳格な E-Mail アドレスの正規表現パターン。(RFC-822 準拠)
   */
  const EMAIL_STRICT_PATTERN = '/^((?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*)/$';

  /**
   * E-Mail アドレスの書式が正当なものであるかチェックします。
   *
   * @param string $value チェック対象の E-Mail アドレス。
   * @param bool $strict 厳格な E-Mail アドレスのチェックを行う場合は TRUE、あいまいなチェックを行う場合は FALSE を指定。規定値は FALSE。
   * @param bool $domainCheck メールアドレスのドメインパートが存在するか DNS レコードをチェックする。既定値は FALSE。(Windows では動作しないため、必ず TRUE を返す)
   * @return bool E-Mail アドレスの書式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $strict = FALSE, $domainCheck = FALSE)
  {
    if ($strict) {
      $mask = self::EMAIL_STRICT_PATTERN;
    } else {
      $mask = self::EMAIL_TRANSITIONAL_PATTERN;
    }

    if (preg_match($mask, $value)) {
      if ($domainCheck && function_exists('checkdnsrr')) {
        $domain = substr($value, strpos($value, '@') + 1);

        // MX レコードが未定義の場合は A レコードをチェック (CNAME の使用は実際のところ RFC2821 違反)
        if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'CNAME')) {
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
    $strict = $holder->getBoolean('strict');
    $domainCheck = $holder->getBoolean('domainCheck');

    if (strlen($value) == 0 || self::isValid($value, $strict, $domainCheck)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('E-Mail format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
