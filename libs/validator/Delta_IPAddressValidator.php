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
 *     formatError: {default_message}
 *
 *     # グローバル IP アドレスを許可するかどうか。
 *     allowGlobal: TRUE
 *
 *     # プライベート IP アドレスを許可するかどうか。
 *     allowPrivate: TRUE
 *
 *     # 許可されない IP アドレスが指定された場合に通知するメッセージ。
 *     denyError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_IPAddressValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'ipAddress';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      // IP アドレスの妥当性をチェック
      $verifyIpAddress = long2ip(ip2long($this->_fieldValue));

      if ($this->_fieldValue != $verifyIpAddress) {
        $this->setError('formatError');
        $result = FALSE;

      } else {
        // IP アドレスが許可されるネットワークアドレスかチェック
        $isPrivateIPAddress = Delta_NetworkUtils::isPrivateIPAddress($this->_fieldValue);

        $allowPrivate = $this->_conditions->getBoolean('allowPrivate', TRUE);
        $allowGlobal = $this->_conditions->getBoolean('allowGlobal', TRUE);

        if (!$allowPrivate && $isPrivateIPAddress || !$allowGlobal && !$isPrivateIPAddress) {
          $this->setError('denyError');
          $result = FALSE;
        }
      }
    }

    return $result;
  }
}
