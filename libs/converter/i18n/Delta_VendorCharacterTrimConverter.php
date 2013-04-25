<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter.i18n
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
 * @package converter.i18n
 */
class Delta_VendorCharacterTrimConverter extends Delta_Converter
{
  /**
   * @see Delta_Converter::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($converterId, Delta_ParameterHolder $holder)
  {
    parent::__construct($converterId, $holder);

    // mb_convert_encoding() で失敗した文字を削除する
    mb_substitute_character('none');
  }

  /**
   * @see Delta_Converter::convert()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convert($string)
  {
    $detectEncoding = mb_detect_encoding($string, 'UTF-8, SJIS-win');

    if ($detectEncoding !== 'UTF-8') {
      $string = mb_convert_encoding($string, 'UTF-8', $detectEncoding);
    }

    $string = preg_replace('/\p{Co}/u', '', $string);

    $sjisValue = mb_convert_encoding($string, 'Shift_JIS', 'UTF-8');
    $revertValue = mb_convert_encoding($sjisValue, 'UTF-8', 'Shift_JIS');

    return $revertValue;
  }
}
