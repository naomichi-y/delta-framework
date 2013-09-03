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
 * フォームから送信されたチェックボックスの状態を検証します。
 * チェック可能なフィールドタイプは、checkbox、または radio のいずれかとなります。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_CheckBoxValidator
 *
 *     # 送信可能なチェック数。
 *     # 指定された数より実際のチェック数が多い、または少ない場合はエラーとなる。
 *     requiredMatch:
 *
 *     # 送信されたチェック数が requiredMatch に一致しない場合に通知するエラーメッセージ。
 *     requiredMatchError: {default_message}
 *
 *     # 送信を許可する最低チェック数。
 *     requiredMin:
 *
 *     # 送信されたチェック数が requiredMin 以下の場合に通知するエラーメッセージ。
 *     requiredMinError: {default_message}
 *
 *     # 送信可能な最大チェック数。
 *     requiredMax:
 *
 *     # 送信されたチェック数が requiredMax を超えた場合に通知するエラーメッセージ。
 *     requiredMaxError: {default_message}
 * </code>
 * ※: 'requiredMatch'、'requiredMin'、'requiredMax' のいずれかの指定が必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_CheckBoxValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'checkbox';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $checkedSize = sizeof($this->_fieldValue);
    $result = TRUE;

    // 指定の数だけチェックが付いているか
    if ($this->_conditions->hasName('requiredMatch')) {
      if ($this->_conditions->getInt('requiredMatch') != $checkedSize) {
        $this->_error = $this->buildError('requiredMatchError');
        $result = FALSE;
      }

    } else {
      $requiredMin = $this->_conditions->getInt('requiredMin');
      $requiredMax = $this->_conditions->getInt('requiredMax');

      // 最小チェック以上数を満たしているか
      if ($requiredMin) {
        if ($requiredMin > $checkedSize) {
          $this->_error = $this->buildError('requiredMinError');
          $result = FALSE;
        }
      }

      // 最大チェック数以下を満たしているか
      if ($requiredMax) {
        if ($requiredMax < $checkedSize) {
          $this->_error = $this->buildError('requiredMaxError');
          $result = FALSE;
        }
      }

      if (!$requiredMin && !$requiredMax) {
        $message = sprintf('Validate condition is undefined. [requiredMatch, requirdMin, requiredMax]');
        throw new Delta_ConfigurationException($message);
      }
    }

    return $result;
  }
}
