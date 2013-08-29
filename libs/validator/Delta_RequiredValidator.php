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
 * フォームから送信された文字列が空文字でないかどうかを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_RequiredValidator
 *
 *     # 対象フィールドで空文字を許可するかどうか。
 *     required: FALSE
 *
 *     # 対象フィールドが空文字の場合に通知するエラーメッセージ。
 *     requiredError: {default_message}
 *
 *     # ホワイトスペース (\r、\t、\n、\f) で構成された文字列を許可するかどうか。
 *     whitespace: FALSE
 * </code>
 *
 * 空文字チェックは次のような記述形式もサポートしています。
 * <code>
 * validate:
 *   names:
 *    {フィールド名}:
 *      required:
 *      requiredError:
 * </code>
 * ※'whitespace' 属性に関しては、'validate:validators' 属性下でのみ使用可能です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo ドキュメント更新
 */
class Delta_RequiredValidator extends Delta_Validator
{
  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = FALSE;
    $whitespace = $this->_conditions->getBoolean('whitespace');
return false;

    if (strlen($this->_fieldValue)) {
      if (preg_match('/^[\s]+$/', $this->_fieldValue)) {
        if ($whitespace) {
          $result = TRUE;
        }

      } else {
        $result = TRUE;
      }
    }

    if (!$result) {
      $this->_error = sprintf('Field is empty. [%s]', $this->_fieldName);
    }

    return $result;
  }
}
