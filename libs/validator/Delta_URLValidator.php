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
 * URL のフォーマットが正当なものであるかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_URLValidator
 *
 *     # URL に含まれるクエリパラメータを許可する場合は TRUE を指定。
 *     query: FALSE
 *
 *     # URL のフォーマットが不正な場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @link http://www.din.or.jp/~ohzaki/perl.htm URI(URL) の正規表現
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_URLValidator extends Delta_Validator
{
  /**
   * URL の正規表現パターン。
   */
  const URL_PATTERN = '/^(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/]+)$/';

  /**
   * クエリパラメータを含む URL の正規表現パターン。
   */
  const URL_QUERY_PATTERN = '/^(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/';

  /**
   * URL の書式が正当なものであるかチェックします。
   *
   * @param string $value チェック対象の URL。
   * @param bool $query クエリパラメータを許可する場合は TRUE、許可しない場合は FALSE。規定値は FALSE。
   * @return bool URL の書式が正当なものかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isValid($value, $query = FALSE)
  {
    if ($query) {
      $mask = self::URL_QUERY_PATTERN;
    } else {
      $mask = self::URL_PATTERN;
    }

    if (preg_match($mask, $value)) {
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
    $query = $holder->getBoolean('query');

    if (strlen($value) == 0 || self::isValid($value, $query)) {
      return TRUE;
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('URL format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
