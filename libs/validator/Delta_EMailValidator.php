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
 *     formatError: {default_message}
 * </code>
 *
 * @link http://www.ietf.org/rfc/rfc0822.txt Standard for ARPA Internet Text Messages
 * @link http://www.din.or.jp/~ohzaki/perl.htm#Mail メールアドレスの正規表現
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @since 2.0 ドキュメント更新
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
   * @var string
   */
  protected $_validatorId = 'email';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      $strict = $this->_conditions->getBoolean('strict');
      $domainCheck = $this->_conditions->getBoolean('domainCheck');

      if ($strict) {
        $pattern = self::EMAIL_STRICT_PATTERN;
      } else {
        $pattern = self::EMAIL_TRANSITIONAL_PATTERN;
      }

      if (preg_match($pattern, $this->_fieldValue)) {
        if ($domainCheck) {
          $domainPart = substr($this->_fieldValue, strpos($this->_fieldValue, '@') + 1);

          // MX レコードが未定義の場合は A レコードをチェック (CNAME の使用は実際のところ RFC2821 違反)
          if (!checkdnsrr($domainPart, 'MX') && !checkdnsrr($domainPart, 'A') && !checkdnsrr($domainPart, 'CNAME')) {
            $result = FALSE;
          }
        }

      } else {
        $result = FALSE;
      }

      if (!$result) {
        $this->setError('formatError');
      }
    }

    return $result;
  }
}
