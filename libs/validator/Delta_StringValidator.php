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
 *     allowAlphabet: TRUE
 *
 *     # 文字列が数値で構成されているかどうかを検証する。
 *     allowNumeric: TRUE
 *
 *     # 文字列がダッシュ、アンダースコアで構成されているか検証する。
 *     allowDash: TRUE
 *
 *     # 文字列に許可されない文字が含まれる場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 * </code>
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

    if (strlen($this->_fieldValue)) {
      $allowAlphabet = $this->_conditions->getBoolean('allowAlphabet', TRUE);
      $allowNumeric = $this->_conditions->getBoolean('allowNumeric', TRUE);
      $allowDash = $this->_conditions->getBoolean('allowDash', TRUE);

      $pattern = NULL;

      if ($allowAlphabet) {
        $pattern = 'a-zA-Z';
      }

      if ($allowNumeric) {
        $pattern .= '0-9';
      }

      if ($allowDash) {
        $pattern .= '\-_';
      }

      $pattern = '/^[' . $pattern . ']+$/';

      if (!preg_match($pattern, $this->_fieldValue)) {
        $this->setError('formatError');
        $result = FALSE;
      }
    }

    return $result;
  }
}
