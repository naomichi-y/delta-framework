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
 * フォームから送信された文字列が英数字で構成されているか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_StringValidator
 *
 *     # 文字列が英字で構成されているかどうかを検証する。
 *     allowAlphabet: FALSE
 *
 *     # 文字列が数値で構成されているかどうかを検証する。
 *     allowNumeric: FALSE
 *
 *     # 文字列に許可されない文字が含まれる場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 * ※'allowAlphabet'、'allowNumeric' の両方が TRUE の場合、文字列が英数字で構成されているかどうかをチェックします。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_StringValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'string';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    $allowAlphabet = $this->_conditions->getBoolean('allowAlphabet', TRUE);
    $allowNumeric = $this->_conditions->getBoolean('allowNumeric', TRUE);

    // 文字列中のアルファベットを許可
    if ($allowAlphabet) {
      // 文字列中の数値を許可
      if ($allowNumeric) {
        if (!ctype_alnum($this->_fieldValue)) {
          $result = FALSE;
        }

      } else if (!ctype_alpha($this->_fieldValue)) {
        $result = FALSE;
      }

    // 文字列中の数値を許可
    } else if ($allowNumeric && !ctype_digit($this->_fieldValue)) {
      $result = FALSE;
    }

    if (!$result) {
      $this->setError('matchError');
    }

    return $result;
  }
}
