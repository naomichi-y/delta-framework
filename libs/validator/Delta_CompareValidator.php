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
 * フォームから送信された 2 つのフィールドの文字列を比較します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_CompareValidator
 *
 *     # 比較対象フィールド。
 *     compareField:
 *
 *     # 比較対象ラベル
 *     compareLabel:
 *
 *     # 比較対象フィールドと値が異なる場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_CompareValidator extends Delta_Validator
{
  protected $_validatorId = 'compare';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    if (!isset($this->_conditions['compareField'])) {
      $message = sprintf('Validate condition is undefined. [compareField]');
      throw new Delta_ConfigurationException($message);

    } else if (!isset($this->_conditions['compareLabel'])) {
      $message = sprintf('Validate condition is undefined. [compareLabel]');
      throw new Delta_ConfigurationException($message);
    }

    $compareField = $this->_conditions->getString('compareField');

    $request = Delta_FrontController::getInstance()->getRequest();
    $compareValue = $request->getParameter($compareField);
    $result = TRUE;

    if (strcmp($this->_fieldValue, $compareValue) != 0) {
      $result = FALSE;
      $this->setError('matchError');
    }

    return $result;
  }
}
