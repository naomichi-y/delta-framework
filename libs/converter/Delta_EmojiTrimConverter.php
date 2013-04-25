<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 入力値から絵文字 (Unicode 6.0 Emoji Symbols) を除去します。
 * ヒューチャーフォンの絵文字を除去する場合は {@link Delta_VendorCharacterTrimConverter} クラスを利用して下さい。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_EmojiTrimConverter
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 */
class Delta_EmojiTrimConverter extends Delta_Converter
{
  /**
   * @see Delta_Converter::convert()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convert($string)
  {
    $detectEncoding = mb_detect_encoding($string, 'UTF-8');

    if ($detectEncoding !== 'UTF-8') {
      $string = mb_convert_encoding($string, 'UTF-8', $detectEncoding);
    }

    $pattern = Delta_EmojiValidator::getRegexpPattern();
    $string = preg_replace($pattern, '', $string);

    return $string;
  }
}
