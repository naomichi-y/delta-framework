<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ANSI カラーを提供するクラスです。
 *
 * @link http://ascii-table.com/ansi-escape-sequences.php
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package console
 * @since 1.1
 */

class Delta_ANSIGraphic extends Delta_Object
{
  /**
   * 文字色定数。(黒)
   */
  const FOREGROUND_BLACK = 1;

  /**
   * 文字色定数。(赤)
   */
  const FOREGROUND_RED = 2;

  /**
   * 文字色定数。(緑)
   */
  const FOREGROUND_GREEN = 4;

  /**
   * 文字色定数。(黄)
   */
  const FOREGROUND_YELLOW = 8;

  /**
   * 文字色定数。(青)
   */
  const FOREGROUND_BLUE = 16;

  /**
   * 文字色定数。(マゼンダ)
   */
  const FOREGROUND_MAGENTA = 32;

  /**
   * 文字色定数。(シアン)
   */
  const FOREGROUND_CYAN = 64;

  /**
   * 文字色定数。(白)
   */
  const FOREGROUND_WHITE = 128;

  /**
   * 背景色定数。(黒)
   */
  const BACKGROUND_BLACK = 256;

  /**
   * 背景色定数。(赤)
   */
  const BACKGROUND_RED = 512;

  /**
   * 背景色定数。(緑)
   */
  const BACKGROUND_GREEN = 1024;

  /**
   * 背景色定数。(黄)
   */
  const BACKGROUND_YELLOW = 2048;

  /**
   * 背景色定数。(青)
   */
  const BACKGROUND_BLUE = 4096;

  /**
   * 背景色定数。(マゼンダ)
   */
  const BACKGROUND_MAGENTA = 8192;

  /**
   * 背景色定数。(シアン)
   */
  const BACKGROUND_CYAN = 16384;

  /**
   * 背景色定数。(白)
   */
  const BACKGROUND_WHITE = 32768;

  /**
   * 背景色定数。(太字)
   */
  const ATTRIBUTE_BOLD = 65536;

  /**
   * 背景色定数。(下線)
   */
  const ATTRIBUTE_UNDERSCORE = 131072;

  /**
   * 背景色定数。(点滅)
   */
  const ATTRIBUTE_BLINK = 262144;

  /**
   * 背景色定数。(反転)
   */
  const ATTRIBUTE_REVERSE = 524288;

  /**
   * @var array
   */
  private static $_foregroundColors = array(
    self::FOREGROUND_BLACK => '30',
    self::FOREGROUND_RED => '31',
    self::FOREGROUND_GREEN => '32',
    self::FOREGROUND_YELLOW => '33',
    self::FOREGROUND_BLUE => '34',
    self::FOREGROUND_MAGENTA => '35',
    self::FOREGROUND_CYAN => '36',
    self::FOREGROUND_WHITE => '37'
  );

  /**
   * @var array
   */
  private static $_backgroundColors = array(
    self::BACKGROUND_BLACK => '40',
    self::BACKGROUND_RED => '41',
    self::BACKGROUND_GREEN => '42',
    self::BACKGROUND_YELLOW => '43',
    self::BACKGROUND_BLUE => '44',
    self::BACKGROUND_MAGENTA => '45',
    self::BACKGROUND_CYAN => '46',
    self::BACKGROUND_WHITE => '47'
  );

  /**
   * @var array
   */
  private static $_attributes = array(
    self::ATTRIBUTE_BOLD => '1',
    self::ATTRIBUTE_UNDERSCORE => '4',
    self::ATTRIBUTE_BLINK => '5',
    self::ATTRIBUTE_REVERSE => '7'
  );

  /**
   * 文字列に ANSI カラーを割り当てます。
   * <code>
   * // 文字列 'Hello world!' に赤文字、下線の書式を設定
   * // '\e[31;4mHello world!\e[m' を返す
   * $string = Delta_ANSIGraphic::build('Hello world!', Delta_ANSIGraphic::FOREGROUND_RED|Delta_ANSIGraphic::ATTRIBUTE_UNDERSCORE);
   * </code>
   * このメソッドは Windows に対応していないため、Windows 環境下ではオリジナルの文字列を返します。
   *
   * @param string $string 対象となる文字列。
   * @param int $graphicMode 文字列に設定するカラー定数。
   *   {@link Delta_ANSIGraphic} クラスが提供する FOREGROUND_*、BACKGROUND_*、ATTRIBUTE_* 定数の組み合わせが指定可能。
   * @return string ANSI カラーを割り当てた文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function build($string, $graphicMode)
  {
    if(strncmp(PHP_OS, 'WIN', 3) != 0) {
      $tag = NULL;

      foreach (self::$_foregroundColors as $name => $value) {
        if ($graphicMode & $name) {
          $tag = $value;
          break;
        }
      }

      foreach (self::$_backgroundColors as $name => $value) {
        if ($graphicMode & $name) {
          $tag .= ';' . $value;
          break;
        }
      }

      foreach (self::$_attributes as $name => $value) {
        if ($graphicMode & $name) {
          $tag .= ';' . $value;
        }
      }

      $string = sprintf("\e[%sm%s\e[m", $tag, $string);
    }

    return $string;
  }
}
