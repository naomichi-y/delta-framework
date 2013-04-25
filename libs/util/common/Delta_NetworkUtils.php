<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ネットワークに関する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_NetworkUtils
{
  /**
   * 指定した IP アドレスがネットワークアドレス ranges に含まれているかチェックします。
   *
   * @param mixed $ranges ネットワークアドレスの範囲を文字列または配列で指定。
   *   CIDR 形式の他、IP アドレスの全体マッチ、範囲マッチ ('192.168.0.1-192.168.1.255')、部分マッチ ('192.168.') 形式をサポート。
   * @param string $ipAddress チェック対象の IP アドレス。
   * @return bool IP アドレスがネットワークアドレスに含まれる場合は TRUE、含まれない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function hasContainNetwork($ranges, $ipAddress)
  {
    if (!is_array($ranges)) {
      $ranges = array($ranges);
    }

    foreach ($ranges as $range) {
      $pos = strpos($range, '/');

      // Parse CIDR address
      if ($pos !== FALSE) {
        $rangeBinary = Delta_NetworkUtils::convertIPToBinary(substr($range, 0, $pos));
        $ipAddressBinary = Delta_NetworkUtils::convertIPToBinary($ipAddress);

        // First 'N' bits are same as network range check
        $addressPrefix = substr($range, $pos + 1);
        $rangeField = substr($rangeBinary, 0, $addressPrefix);
        $ipAddressField = substr($ipAddressBinary, 0, $addressPrefix);

        if ($rangeField === $ipAddressField) {
          return TRUE;
        }

      // Parse IP address range
      } else {
        $pos = strpos($range, '-');

        if ($pos !== FALSE) {
          $startRangeBinary = Delta_NetworkUtils::convertIPToInteger(substr($range, 0, $pos));
          $endRangeBinary = Delta_NetworkUtils::convertIPToInteger(substr($range, $pos + 1));
          $ipAddressBinary = Delta_NetworkUtils::convertIPToInteger($ipAddress);

          if ($startRangeBinary <= $ipAddressBinary && $ipAddressBinary <= $endRangeBinary) {
            return TRUE;
          }

        } else if (strpos($ipAddress, $range) !== FALSE) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * IP アドレス (IPv4) を 2 進数表記に変換します。
   * <code>
   * // 11000000101010000000000000000001
   * $binary = Delta_NetworkUtils::convertIPToBinary('192.168.0.1');
   * </code>
   *
   * @param string $ipAddress 変換対象の IP アドレス (ドット表記)。
   * @return string 2 進数表記に変換された IP アドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertIPToBinary($ipAddress)
  {
    $parts = explode('.', $ipAddress);
    $buffer = NULL;

    foreach ($parts as $part) {
      $buffer .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
    }

    return $buffer;
  }

  /**
   * IP アドレス (IPv4) を整数値表記に変換します。
   * このメソッドは {@link ip2long()} と異なり、必ず符号なし (unsigned) の整数値を返します。
   * <code>
   * // 3232235521
   * $integer = Delta_NetworkUtils::convertIPToInteger('192.168.0.1');
   * </code>
   *
   * @param string $ipAddress 変換対象の IP アドレス (ドット表記)。
   * @return int 数値表記 (ネットワークバイトオーダー) に変換された IP アドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertIPToInteger($ipAddress)
  {
    $integer = ip2long($ipAddress);

    // 64bit OS では unsigned の整数値を返すため、unsigned に変換しておく
    if ($integer < 0) {
      $integer = $integer + 4294967296;
    }

    return $integer;
  }

  /**
   * 整数値表記の IP アドレスをドット表記の IP アドレス (IPv4) に変換します。
   * このメソッドは {@link long2ip()} 関数のラッパーです。
   * <code>
   * // '192.168.0.1'
   * $ip = Delta_NetworkUtils::convertIntegerToIP(3232235521);
   * </code>
   *
   * @param int $integer 変換対象の IP アドレス (整数表記)。
   * @return string ドット表記の IP アドレスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertIntegerToIP($integer)
  {
    return long2ip($integer);
  }
}
