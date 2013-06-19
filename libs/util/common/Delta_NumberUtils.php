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
 * 数値を操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_NumberUtils
{
  /**
   * ファイルサイズ定数。(Byte)
   */
  const DATA_SIZE_BYTE = 'Byte';

  /**
   * ファイルサイズ定数。(KByte)
   */
  const DATA_SIZE_KBYTE = 'KB';

  /**
   * ファイルサイズ定数。(MByte)
   */
  const DATA_SIZE_MBYTE = 'MB';

  /**
   * ファイルサイズ定数。(GByte)
   */
  const DATA_SIZE_GBYTE = 'GB';

  /**
   * ファイルサイズ定数。(TByte)
   */
  const DATA_SIZE_TBYTE = 'TB';

  /**
   * 指定した桁数で数値を切り上げます。
   *
   * @param float $number 対象となる数値。
   * @param int $precision 丸める桁数。
   * @return float 値を丸めた結果を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function roundUp($number, $precision = 2)
  {
    $p = pow(10, $precision);

    return ceil(($number) * $p) / $p;
  }

  /**
   * 指定した桁数で数値を切り捨てます。
   *
   * @param float $number 対象となる数値。
   * @param int $precision 丸める桁数。
   * @return float 値を丸めた結果を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function roundDown($number, $precision = 2)
  {
    $p = pow(10, $precision);

    return floor(($number) * $p) / $p;
  }

  /**
   * 浮動小数点型の乱数値を生成します。
   * <code>
   * // (e.g.) 3.7567219556734
   * $float = Delta_NumberUtils::buildRandomFloat(0, 10);
   * </code>
   *
   * @param float $min 返される値の最小値。
   * @param float $max 返される値の最大値。
   * @return float min から max までの間のランダムな小数値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildRandomFloat($min, $max) {
     return ($min + lcg_value() * abs($max - $min));
  }

  /**
   * 2 の冪乗の和を構成する数値を配列形式で取得します。
   * 例えば数値の 7 は 2 の 1 乗、2 乗、4 乗の和であるため array(1, 2, 4) を返します。
   * <i>この関数は現在のところ、負数には対応していません。</i>
   *
   * @param int $number 2 の冪乗の和。
   * @return int number を構成する数値を配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function splitPower($number)
  {
    $array = array();
    $i = 0;

    do {
      $pow = pow(2, $i);
      $i++;

      if ($pow & $number) {
        $array[] = $pow;
      }

    } while ($pow < $number);

    return $array;
  }

  /**
   * ファイルサイズを読みやすい形式に変換します。
   * <code>
   * // '1MB'
   * $size = Delta_NumberUtils::formatBytes(1048576, Delta_NumberUtils::DATA_SIZE_MBYTE);
   *
   * // '1024KB'
   * $size = Delta_NumberUtils::formatBytes(1048576);
   * </code>
   *
   * @param int $size ファイルサイズ。単位は Byte。
   * @param string $unit 変換後の単位。Delta_NumberUtils::DATA_SIZE_* 定数を指定。未指定の場合は適切な単位を追加します。
   * @param int $precision 小数点以下の丸める桁数。
   * @return string 単位変換後のファイルサイズを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function formatBytes($fileSize, $unit = NULL, $precision = 2)
  {
    switch ($unit) {
      case self::DATA_SIZE_KBYTE:
        $fileSize = $fileSize / 1024;
        break;

      case self::DATA_SIZE_MBYTE:
        $fileSize = $fileSize / pow(1024, 2);
        break;

      case self::DATA_SIZE_GBYTE:
        $fileSize = $fileSize / pow(1024, 3);
        break;

      case self::DATA_SIZE_TBYTE:
        $fileSize = $fileSize / pow(1024, 4);
        break;

      case NULL:
        $units = array(self::DATA_SIZE_BYTE,
          self::DATA_SIZE_KBYTE,
          self::DATA_SIZE_MBYTE,
          self::DATA_SIZE_GBYTE,
          self::DATA_SIZE_TBYTE);
        $i = 0;
        $j = sizeof($units);

        while ($fileSize > 1024) {
          $i++;
          $fileSize = $fileSize / 1024;

          if ($i + 1== $j) {
            $i = $j - 1;
            break;
          }
        }

        $unit = $units[$i];
        break;

      default:
    }

    $number = self::roundDown($fileSize, $precision);

    if (($pos = strpos($number, '.')) !== FALSE) {
      $integer = number_format(substr($number, 0, $pos));
      $decimal = substr($number, $pos + 1);

      $string = $integer . '.' . $decimal;

    } else {
      $string = number_format($number);
    }

    $string = $string . $unit;

    return $string;
  }

  /**
   * '100MB'、'1.2GB' といったデータサイズをバイト表記形式に変換します。
   * <code>
   * // 104857600
   * $value = Delta_NumberUtils::realBytes('100MB');
   *
   * // 1288490188.8
   * $value = Delta_NumberUtils::realBytes('1.2GB');
   * </code>
   *
   * @param string $fileSize ファイルサイズの指定。'Bytes'、'KB'、'Mbyte'、'GB' といった表記形式をサポート。
   * @return int データサイズをバイト表記形式で返します。
   *   1 キロバイトは 1024 バイトで換算されます。fileSize が解析できなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function realBytes($fileSize)
  {
    $realSize = FALSE;

    if (preg_match('/^([0-9,\.]+)([a-zA-Z\s]*)$/', $fileSize, $matches)) {
      $numberPart = (float) str_replace(',', '', $matches[1]);
      $unitPart = strtolower(trim($matches[2]));

      if (in_array($unitPart, array('k', 'kb', 'kbyte', 'kbytes'))) {
        $realSize = $numberPart * 1024;

      } else if (in_array($unitPart, array('m', 'mb', 'mbyte', 'mbytes'))) {
        $realSize = $numberPart * pow(1024, 2);

      } else if (in_array($unitPart, array('g', 'gb', 'gbyte', 'gbytes'))) {
        $realSize = $numberPart * pow(1024, 3);

      } else if (in_array($unitPart, array('t', 'tb', 'tbyte', 'tbytes'))) {
        $realSize = $numberPart * pow(1024, 4);

      } else {
        $realSize = $numberPart;
      }
    }

    return $realSize;
  }

  /**
   * 指定した確率で TRUE を取得します。
   * <code>
   * // 30% の確率で TRUE を返す
   * Delta_NumberUtils::lot(30);
   * </code>
   *
   * @param mixed $rate 0〜100 の確率。整数、または浮動小数点数を指定可能。
   * @return bool 確率にヒットした場合は TRUE、ヒットしなかった場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function lot($rate)
  {
    $result = FALSE;

    if (is_int($rate)) {
      $value = mt_rand(1, 100);

    } else {
      // 0.000...〜101.000...
      $value = self::buildRandomFloat(0, 101);

      $precision = strlen(substr($rate, strpos($rate, '.') + 1));
      $value = (float) self::roundDown($value, $precision);

      if ($value > 100) {
        $value = 100;
      }
    }

    if ($value <= $rate) {
      $result = TRUE;
    }

    return $result;
  }
}
