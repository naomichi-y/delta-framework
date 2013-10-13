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
 * 入力された文字列の前後にある空白文字を取り除きます。
 * 除外対象となる文字の一覧は、PHP マニュアルの {@link trim()} 関数を参照して下さい。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_TrimConverter
 * </code>
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
 * @todo 2.0 ドキュメント更新
 */
class Delta_WhitespaceSanitizer extends Delta_Sanitizer
{
  /**
   * @see Delta_Converter::sanitize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitize()
  {
    return trim($this->_fieldValue);
  }
}
