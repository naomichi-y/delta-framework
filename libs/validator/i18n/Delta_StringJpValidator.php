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
 *     # 文字列が全角文字で構成されているかどうかを検証する。
 *     maskMultibyte: FALSE
 *
 *     # 文字列が平仮名で構成されているかどうかを検証する。
 *     maskHiragana: FALSE
 *
 *     # 文字列が片仮名で構成されているかどうかを検証する。
 *     maskKatakana: FALSE
 *
 *     # 文字列が半角仮名で構成されているかどうかを検証する。
 *     maskHalfKana: FALSE
 *
 *     # 文字列に許可されない文字が含まれる場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 * </code>
 * ※複数の 'mask*' 属性が TRUE に指定された場合、判定条件は OR となります。
 *
 * @link http://www.din.or.jp/~ohzaki/perl.htm#Character 文字の正規表現
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 */

class Delta_StringJpValidator extends Delta_StringValidator
{
  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    if (strlen($value) == 0) {
      return TRUE;
    }

    // Shift_JIS による正規表現を行う
    if (mb_detect_encoding($value) != 'Shift_JIS') {
      $fromEncoding = Delta_Config::getApplication()->get('charset.default');
      $value = mb_convert_encoding($value, 'Shift_JIS', $fromEncoding);
    }

    $regexp = NULL;

    // 全角検証
    if ($holder->getBoolean('maskMultibyte')) {
      $regexp = '(?:[\x81-\x9F\xE0-\xFC][\x40-\x7E\x80-\xFC])';
    }

    // 平仮名検証 (ぁ-ん゛゜ゝゞ)
    if ($holder->getBoolean('maskHiragana')) {
      $regexp = '(?:\x82[\x9F-\xF1]|\x81[\x4A\x4B\x54\x55])';
    }

    // 片仮名検証 (ァ-ヶ・ーヽヾ)
    if ($holder->getBoolean('maskKatakana')) {
      if (strlen($regexp)) {
        $regexp .= '|';
      }

      $regexp .= '(?:\x83[\x40-\x96]|\x81[\x45\x5B\x52\x53])';
    }

    // 半角仮名検証 (ヲ-゜)
    if ($holder->getBoolean('maskHalfKana')) {
      if (strlen($regexp)) {
        $regexp .= '|';
      }

      $regexp .= '[\xA6-\xDF]';
    }

    if (strlen($regexp)) {
      $regexp = '/^(' . $regexp . ')+$/';

      if (!preg_match($regexp, $value)) {
        $message = $holder->getString('matchError');

        if ($message === NULL) {
          $message = sprintf('Character string pattern is illegal. [%s]', $fieldName);
        }

        $this->sendError($fieldName, $message);

        return FALSE;

      }

      return TRUE;
    }

    return parent::validate($fieldName, $value, $holder);
  }
}
