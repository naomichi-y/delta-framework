<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
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
 * @package sanitizer
 * @todo 2.0
 */
class Delta_EmojiSanitizer extends Delta_Sanitizer
{
  /**
   * @see Delta_Converter::sanitize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitize()
  {
    $fieldValue = $this->_fieldValue;

    // 絵文字パターンはUTF-8で比較する
    $detectEncoding = mb_detect_encoding($fieldValue);

    if ($detectEncoding !== 'UTF-8') {
      $fieldValue = mb_convert_encoding($fieldValue, 'UTF-8', $detectEncoding);
    }

    // 絵文字の正規表現パターンを取得
    $regexpPath = DELTA_LIBS_DIR . '/sanitizer/emoji_regexp_utf8.txt';
    $regexp = file_get_contents($regexpPath);

    // 絵文字を全て '' 文字に変換
    $fieldValue = preg_replace($regexp, '', $this->_fieldValue);

    return $fieldValue;
  }
}
