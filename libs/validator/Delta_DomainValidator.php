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
 *     checkRecord: FALSE
 *
 *     # ドメインのフォーマットが不正な場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_DomainValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'domain';

  /**
   * ドメインチェックパターン。
   */
  const DOMAIN_PATTERN = '/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      // ドメインの正規表現チェック
      if (preg_match(self::DOMAIN_PATTERN, $this->_fieldValue)) {
        $checkRecord = $this->_conditions->getBoolean('checkRecord');

        // レコードが存在するかチェック
        if ($checkRecord) {
          if (!checkdnsrr($this->_fieldValue, 'A') && !checkdnsrr($this->_fieldValue, 'CNAME')) {
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
