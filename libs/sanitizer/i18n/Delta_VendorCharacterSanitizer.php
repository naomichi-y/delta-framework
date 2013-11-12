<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer.i18n
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 入力値から機種依存文字 (Windows-31J) を除去します。
 * Unicode 6.0 に対応した絵文字を除去したい場合は、{@link Delta_EmojiTrimConverter} クラスを利用して下さい。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_VendorCharacterTrimConverter
 * </code>
 *
 * @link Delta_VendorCharacterValidator 機種依存とみなされる文字について
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer.i18n
 * @todo 2.0 ドキュメント更新
 */
class Delta_VendorCharacterSanitizer extends Delta_Sanitizer
{
  /**
   * @see Delta_Converter::sanitizer()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitize()
  {
    mb_substitute_character('none');

    $fieldValue = $this->_fieldValue;
    $detectEncoding = mb_detect_encoding($fieldValue, 'UTF-8, SJIS-win');

    if ($detectEncoding !== 'UTF-8') {
      $fieldValue = mb_convert_encoding($fieldValue, 'UTF-8', $detectEncoding);
    }

    $fieldValue = preg_replace('/\p{Co}/u', '', $fieldValue);

    $sjisValue = mb_convert_encoding($fieldValue, 'Shift_JIS', 'UTF-8');
    $fieldValue = mb_convert_encoding($sjisValue, 'UTF-8', 'Shift_JIS');

    return $fieldValue;
  }
}
