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
 * フォームから送信された文字列の長さを検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_LengthValidator
 *
 *     # 文字列のカウント方式。
 *     # マルチバイト文字としてカウントする場合は TRUE、バイト数でカウントする場合は FALSE を指定。
 *     multibyte: TRUE
 *
 *     # 文字列を構成する最小の長さ。
 *     minLength:
 *
 *     # 文字列長が 'minLength' を満たさない場合に通知するエラーメッセージ。
 *     minLengthError: {default_message}
 *
 *     # 文字列を構成する最大の長さ。
 *     maxLength:
 *
 *     # 文字列長が 'maxLength' を超える場合に通知するエラーメッセージ。
 *     maxLengthError: {default_message}
 *
 *     # 文字列を構成する固定の長さ。
 *     matchLength:
 *
 *     # 文字列長が 'matchLength' 未満、または 'maxLength' を超えた場合に通知するエラーメッセージ。
 *     matchLengthError: {default_message}
 * </code>
 * ※'minLength'、'maxLength'、'matchLength' のいずれかの指定は必須です。また、'minLength' と 'maxLength' は同時に指定することもできます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */
class Delta_LengthValidator extends Delta_Validator
{
  protected $_validatorId = 'length';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    // 文字数をカウント
    if ($this->_conditions->getBoolean('multibyte', TRUE)) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
      $length = mb_strlen($this->_fieldValue, $encoding);

    // バイト数をカウント
    } else {
      $length = strlen($this->_fieldValue);
    }

    if ($this->_conditions->hasName('matchLength')) {
      if ($this->_conditions->getInt('matchLength') != $length) {
        $this->setError('matchLengthError');
        $result = FALSE;
      }

    } else {
      $hasMinLength = $this->_conditions->hasName('minLength');
      $hasMaxLength = $this->_conditions->hasName('maxLength');

      if ($hasMinLength && $length < $this->_conditions->getInt('minLength')) {
        $this->setError('minLengthError');
        $result = FALSE;

      } else if ($hasMaxLength && $length > $this->_conditions->getInt('maxLength')) {
        $this->setError('maxLengthError');
        $result = FALSE;
      }

      if (!$hasMinLength && !$hasMaxLength) {
        $message = sprintf('Validate condition is undefined. [matchLength, minLength, maxLength]');
        throw new Delta_ConfigurationException($message);
      }
    }

    return $result;
  }
}
