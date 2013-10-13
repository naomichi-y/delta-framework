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
 *     formatError: {default_message}
 * </code>
 *
 * @link http://www.din.or.jp/~ohzaki/perl.htm URI(URL) の正規表現
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_URLValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'url';

  /**
   * URL の正規表現パターン。
   */
  const URL_PATTERN = '/^(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/]+)$/';

  /**
   * クエリパラメータを含む URL の正規表現パターン。
   */
  const URL_QUERY_PATTERN = '/^(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      if ($this->_conditions->getBoolean('query')) {
        $pattern = self::URL_QUERY_PATTERN;
      } else {
        $pattern = self::URL_PATTERN;
      }

      // URL パターンのチェック
      if (!preg_match($pattern, $this->_fieldValue)) {
        $this->setError('formatError');
        $result = FALSE;
      }

      // プロトコルのチェック
      $isHttps = FALSE;

      if (substr($this->_fieldValue, 0, 5) === 'https') {
        $isHttps = TRUE;
      }

      $allowHttp = $this->_conditions->getBoolean('allowHttp');
      $allowHttps = $this->_conditions->getBoolean('allowHttps');

      // 許可されない http プロトコルが指定された
      if (!$allowHttp && !$isHttps) {
        $this->setError('httpError');
        $result = FALSE;

      // 許可されない https プロコトルが指定された
      } else if (!$allowHttps && $isHttps) {
        $this->setError('httpsError');
        $result = FALSE;
      }
    }

    return $result;
  }
}
