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
 * フォームから送信された文字列が指定範囲内の数値に収まっているかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_RangeValidator
 *
 *     # 有効数値の下限値。
 *     min:
 *
 *     # 有効数値の上限値。
 *     max:
 *
 *     # 小数点以下の入力を許可する場合は TRUE を指定。
 *     allowFloat: FALSE
 *
 *     # 小数点が指定された場合に通知するエラーメッセージ。('allowFloat' が FALSE の場合のみ)
 *     floatError: {default_message}
 *
 *     # 数値が指定範囲外の場合に通知するエラーメッセージ。
 *     rangeError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_RangeValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'range';

  /**
   * @throws Delta_ConfigurationException 必須属性が未指定の場合に発生。
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      // 整数値であるか検証
      if (is_numeric($this->_fieldValue)) {
        $allowFloat = $this->_conditions->getBoolean('allowFloat');

        // 浮動小数点数の入力が許可されているか
        if (strpos($this->_fieldValue, '.') !== FALSE && !$allowFloat) {
          $result = FALSE;
          $this->setError('floatError');

        } else {
          // 上限と下限の数値範囲チェック
          if ($this->_conditions->hasName('min') && $this->_conditions->hasName('max')) {
            if ($this->_conditions->getFloat('min') > $this->_fieldValue || $this->_fieldValue > $this->_conditions->getFloat('max')) {
              $result = FALSE;
              $this->setError('rangeError');
            }

          } else {
            $message = 'Required attribute is not specified. [min, max]';
            throw new Delta_ConfigurationException($message);
          }
        }

      } else {
        $result = FALSE;
        $this->setError('rangeError');
      }
    }

    return $result;
  }
}
