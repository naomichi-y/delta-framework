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

    // 絵文字パターンを UTF-8 で比較するため
    $detectEncoding = mb_detect_encoding($fieldValue);

    if ($detectEncoding !== 'UTF-8') {
      $fieldValue = mb_convert_encoding($fieldValue, 'UTF-8', $detectEncoding);
    }

    $pattern = '/(?:(?:(?:\x0023|[\x0030-x0039])\x20E3)|(?:\x1F1E8\x1F1F3|\x1F1E9\x1F1EA|\x1F1EA\x1F1F8|\x1F1EB\x1F1F7|\x1F1EC\x1F1E7|\x1F1EE\x1F1F9|\x1F1EF\x1F1F5|\x1F1F0\x1F1F7|\x1F1F7\x1F1FA|\x1F1FA\x1F1F8)|(?:\x00A9|\x00AE|\x2002|\x2003|\x2005|\x203C|\x2049|\x2122|\x2139|[\x2194-x2199]|\x21A9|\x21AA|\x231A|\x231B|\x23E9|\x23EA|\x23EB|\x23EC|\x23F0|\x23F3|\x24C2|\x25AA|\x25AB|\x25B6|\x25C0|\x25FB|\x25FC|\x25FD|\x25FE|\x2600|\x2601|\x260E|\x2611|\x2614|\x2615|\x261D|\x263A|\x2648|\x2649|\x264A|\x264B|\x264C|\x264D|\x264E|\x264F|\x2650|\x2651|\x2652|\x2653|\x2660|\x2663|\x2665|\x2666|\x2668|\x267B|\x267F|\x2693|\x26A0|\x26A1|\x26AA|\x26AB|\x26BD|\x26BE|\x26C4|\x26C5|\x26CE|\x26D4|\x26EA|\x26F2|\x26F3|\x26F5|\x26FA|\x26FD|\x2702|\x2705|\x2708|\x2709|\x270A|\x270B|\x270C|\x270F|\x2712|\x2714|\x2716|\x2728|\x2733|\x2734|\x2744|\x2747|\x274C|\x274E|\x2753|\x2754|\x2755|\x2757|\x2764|\x2795|\x2796|\x2797|\x27A1|\x27B0|\x2934|\x2935|\x2B05|\x2B06|\x2B07|\x2B1B|\x2B1C|\x2B50|\x2B55|\x3030|\x303D|\x3297|\x3299|\x1F004|\x1F0CF|\x1F170|\x1F171|\x1F17E|\x1F17F|\x1F18E|[\x1F191-x1F19A]|\x1F201|\x1F202|\x1F21A|\x1F22F|[\x1F232-x1F23A]|\x1F250|\x1F251|[\x1F300-x1F30C]|\x1F30F|\x1F311|\x1F313|\x1F314|\x1F315|\x1F319|\x1F31B|\x1F31F|\x1F320|\x1F330|\x1F331|\x1F334|\x1F335|[\x1F337-x1F34F]|[\x1F351-x1F37B|[\x1F380-x1F393]|[\x1F3A0-x1F3C4]|\x1F3C6|\x1F3C8|\x1F3CA|[\x1F3E0-x1F3E3]|[\x1F3E5-x1F3F0]|\x1F40C|\x1F40D|\x1F40E|\x1F411|\x1F412|\x1F414|\x1F417|\x1F418|\x1F419|[\x1F41A-x1F429]|[\x1F42B-x1F43E]|\x1F440|[\x1F442-x1F464]|[\x1F466-x1F46B]|[\x1F46E-x1F4AC]|[\x1F4AE-x1F4B5]|[\x1F4B8-x1F4EB]|[\x1F4EE|[\x1F4F0-x1F4F7]|[\x1F4F9-x1F4FC]|\x1F503|[\x1F50A-x1F514]|[[\x1F516-x1F52B]|[\x1F52E-x1F53D]|[\x1F550-x1F55B]|[\x1F5FB-x1F5FF]|[\x1F601-x1F606]|[\x1F609-x1F60D]|\x1F60F|\x1F612|\x1F613|\x1F614|\x1F616|\x1F618|\x1F61A|\x1F61C|\x1F61D|\x1F61E|[\x1F620-x1F625]|[\x1F628-x1F62B]|\x1F62D|[\x1F630-x1F633]|\x1F635|[\x1F637-x1F640]|[\x1F645-x1F680]|\x1F683|\x1F684|\x1F685|\x1F687|\x1F689|\x1F68C|\x1F68F|\x1F691|\x1F692|\x1F693|\x1F695|\x1F697|\x1F699|\x1F69A|\x1F6A2|\x1F6A4|\x1F6A5|[\x1F6A7-x1F6AD]|\x1F6B2|\x1F6B6|\x1F6B9|[\x1F6BA-x1F6BE]|\x1F6C0))/u';

    echo preg_match($pattern, $this->_fieldValue);exit;

    $fieldValue = preg_replace($pattern, '', $this->_fieldValue);

    return $fieldValue;
  }
}
