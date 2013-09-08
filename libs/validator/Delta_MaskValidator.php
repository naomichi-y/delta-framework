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
 * フォームから送信された文字列の正当性を正規表現パターンによって検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_MaskValidator
 *
 *     # 正規表現のパターン。'/.../' 形式で指定。
 *     mask:
 *
 *     # 対象フィールドが正規表現パターンと一致しない場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_MaskValidator extends Delta_Validator
{
  protected $_validatorId = 'mask';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $mask = $this->_conditions->getString('mask');

    if ($mask === NULL) {
      $message = sprintf('Validate condition is undefined. [mask]');
      throw new Delta_ConfigurationException($message);
    }

    $result = preg_match($mask, $this->_fieldValue);

    if (!$result) {
      $this->setError('maskError');
    }

    return $result;
  }
}
