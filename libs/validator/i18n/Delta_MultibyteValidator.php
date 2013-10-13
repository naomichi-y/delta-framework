<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 日本語に対応した文字列パターン検証のためのバリデータです。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_StringJpValidator
 *
 *     # 文字列がマルチバイトのみで構成されているか検証する。
 *     multibyteOnly: FALSE
 *
 *     # マルチバイト以外の文字が含まれている場合に通知するエラーメッセージ。('multibyteOnly' が TRUE の場合のみ)
 *     multibyteError: {default_message}
 *
 *     # 文字列がひらがなのみで構成されているか検証する。
 *     hiraganaOnly: TRUE
 *
 *     # ひらがな以外の文字が含まれている場合に通知するエラーメッセージ。('hiraganaOnly' が TRUE の場合のみ)
 *     hiraganaError: {default_message}
 *
 *     # 文字列がカタカナのみで構成されているか検証する。
 *     katakanaOnly: TRUE
 *
 *     # カタカナ以外の文字が含まれている場合に通知するエラーメッセージ。('katakanaOnly' が TRUE の場合のみ)
 *     katakanaError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 * @todo 2.0 ドキュメント更新
 */

class Delta_MultibyteValidator extends Delta_Validator
{
  /**
   * マルチバイトの正規表現パターン。
   */
  const STRING_MULTIBYTE_PATTERN = '/^[^\x01-\x7E]+$/';

  /**
   * ひらがなの正規表現パターン。(
   */
  const STRING_HIRAGANA_PATTERN = '/^(?:\xE3\x81[\x81-\xBF]|\xE3\x82[\x80-\x94])+$/';

  /**
   * カタカナの正規表現パターン。
   */
  const STRING_KATAKANA_PATTERN = '/^(?:\xE3\x82[\xA1-\xBF]|\xE3\x83[\x80-\xBA])+$/';

  /**
   * @var string
   */
  protected $_validatorId = 'multibyte';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_conditions)) {
      // 文字エンコーディングを UTF-8 に変換
      $detectEncoding = mb_detect_encoding($this->_fieldValue);

      if ($detectEncoding !== 'UTF-8') {
        $fieldValue = mb_convert_encoding($this->_fieldValue, 'UTF-8', $detectEncoding);
      } else {
        $fieldValue = $this->_fieldValue;
      }

      $multibyteOnly = $this->_conditions->getBoolean('multibyteOnly');
      $hiraganaOnly = $this->_conditions->getBoolean('hiraganaOnly');
      $katakanaOnly = $this->_conditions->getBoolean('katakanaOnly');

      // 文字列が全角文字のみで構成されているかチェック
      if ($multibyteOnly && !preg_match(self::STRING_MULTIBYTE_PATTERN, $fieldValue)) {
        $this->setError('multibyteError');
        $result = FALSE;
      }

      // 文字列をカタカナのみで構成されているかチェック
      if ($hiraganaOnly && !preg_match(self::STRING_HIRAGANA_PATTERN, $fieldValue)) {
        $this->setError('hiraganaError');
        $result = FALSE;
      }

      // 文字列をカタカナのみで構成されているかチェック
      if ($katakanaOnly && !preg_match(self::STRING_KATAKANA_PATTERN, $fieldValue)) {
        $this->setError('katakanaError');
        $result = FALSE;
      }
    }

    return $result;
  }
}
