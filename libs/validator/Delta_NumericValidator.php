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
 * フォームから送信された文字列が数値として正当なものであるかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_NumericValidator
 *
 *     # 小数点以下の入力を許可する場合は TRUE を指定。
 *     allowFloat: FLASE
 *
 *     # 小数点が指定された場合に通知するエラーメッセージ。('allowFloat' が FALSE の場合のみ)
 *     floatError: {default_message}
 *
 *     # 許可されない文字が含まれた場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_NumericValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'numeric';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      // 文字列が数値 (浮動小数点数を含む) で構成されているかチェック
      if (!is_numeric($this->_fieldValue)) {
        $this->setError('formatError');
        $result = FALSE;

      } else {
        $allowFloat = $this->_conditions->getBoolean('allowFloat');

        // 浮動小数点数を含む数値文字列が許可されているかチェック
        if (strpos($this->_fieldValue, '.') !== FALSE && !$allowFloat) {
          $this->setError('floatError');
          $result = FALSE;
        }
      }
    }

    return $result;
  }
}
