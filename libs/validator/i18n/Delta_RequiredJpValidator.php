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
 * 日本語に対応した空文字検証のためのバリデータです。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_RequiredJpValidator
 *
 *     # 全角空白文字 (\x81\x40) に対応。基本機能は親クラスに準ずる。
 *     whitespace:
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 */
class Delta_RequiredJpValidator extends Delta_RequiredValidator
{
  /**
   * @see Delta_RequiredValidator::hasWhitespace()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasWhitespace($value)
  {
    $encoding = Delta_Config::getApplication()->get('charset.default');
    $value = mb_convert_encoding($value, 'Shift_JIS', $encoding);

    // \x81\x40: 全角スペース
    if (preg_match('/^(\s|\x81[\x40])+$/', $value)) {
      return TRUE;
    }

    return FALSE;
  }
}
