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
 * 日付を操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_DateUtils
{
  /**
   * UNIX タイムスタンプや英文形式の文字列を指定した日付フォーマットに変換します。
   * サポートしている英文形式については {@link strtotime()} 関数を参照して下さい。
   *
   * @param string $string 変換対象の文字列。
   * @param string $format 日付の変換フォーマット。{@link strftime()} 関数の変換指定子を参照。
   * @return string 変換後のフォーマット文字列を返します。解析に失敗した場合は FALSE を返します。
   */
  public static function dateFormat($string, $format)
  {
    if (Delta_StringUtils::nullOrEmpty($string)) {
      return FALSE;

    } else if (is_numeric($string)) {
      $timestamp = (int) $string;

    } else {
      $timestamp = strtotime($string);

      if ($timestamp === FALSE) {
        return FALSE;
      }
    }

    return self::formatTime($format, $timestamp);
  }

  /**
   * 現在のロケールの設定に基づいて日付・時刻をフォーマットします。
   * 動作は {@link strftime()} 関数と同じですが、Windows 環境でサポートされていない変換指定子にも対応しています。
   *
   * @param string $format 日付の変換フォーマット。{@link strftime()} 関数の変換指定子を参照。
   * @param int $timestamp UNIX タイムスタンプ。未指定の場合は現在の時刻を適用。
   * @return string 変換後のフォーマット文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function formatTime($format, $timestamp = NULL)
  {
    if ($timestamp === NULL) {
      $timestamp = time();
    }

    if (DIRECTORY_SEPARATOR == "\\") {
      $ufmt = date('w', $timestamp);
    } else {
      $ufmt = strftime('%u', $timestamp);
    }

    if ($ufmt == strftime('%w', $timestamp)) {
      $ufmt = $ufmt + 1;
    }

    if (DIRECTORY_SEPARATOR == "\\") {
      $format = str_replace('%e', sprintf('%2d', date('j', $timestamp)), $format);
      $format = str_replace('%u', $ufmt, $format);
      $format = str_replace('%V', date('W', $timestamp), $format);
      $format = str_replace('%h', strftime('%b', $timestamp), $format);
      $format = str_replace('%C', substr(date('Y', $timestamp), 0, 2), $format);
      $format = str_replace('%g', date('y', $timestamp), $format);
      $format = str_replace('%G', date('Y', $timestamp), $format);
      $format = str_replace('%l', sprintf('%2d', date('h', $timestamp)), $format);
      $format = str_replace('%P', date('a', $timestamp), $format);
      $format = str_replace('%r', strftime('%I:%M:%S %p', $timestamp), $format);
      $format = str_replace('%R', strftime('%H:%M', $timestamp), $format);
      $format = str_replace('%T', strftime('%H:%M:%S', $timestamp), $format);
      $format = str_replace('%D', strftime('%m/%d/%y', $timestamp), $format);
      $format = str_replace('%F', strftime('%Y-%m-%d', $timestamp), $format);
      $format = str_replace('%s', $timestamp, $format);
      $format = str_replace('%n', "\n" , $format);
      $format = str_replace('%t', "\t" , $format);
    }

    return strftime($format, $timestamp);
  }

  /**
   * 一般的なデータベースにおけるタイムスタンプ型 ('Y-m-d H:i:s') の日付フォーマットを返します。
   *
   * @param string $string 変換対象の文字列。サポートする書式は {@link strtotime()} 関数を参照。未指定時は現在の時刻からタイムスタンプを生成します。
   * @return string 変換後のフォーマット文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function timestamp($string = NULL)
  {
    if ($string === NULL) {
      return date('Y-m-d H:i:s');

    } else {
      return self::dateFormat($string, '%F %T');
    }
  }

  /**
   * 指定された日付の差分日数を取得します。
   * <code>
   * // 30
   * $diffDay = Delta_DateUtils::getDiffDay('1970/01/01', '1970/01/31');
   * </code>
   *
   * @param string $date1 比較する日付文字列 1。サポートする書式は {@link strtotime()} 関数を参照。
   * @param string $date2 比較する日付文字列 2。未指定の場合は date1 と現在の日付を比較します。
   * @return int 日付の差分日数を返します。日付を解析できなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getDiffDay($date1, $date2 = NULL)
  {
    if ($date2 === NULL) {
      $date2 = time();
    }

    $date1 = self::unixtime($date1);
    $date2 = self::unixtime($date2);

    if ($date1 === FALSE || $date2 === FALSE) {
      return FALSE;
    }

    $diff = abs($date1 - $date2);
    $days = floor($diff / (24 * 60 * 60));

    return $days;
  }

  /**
   * 対象月の日数を取得します。
   * このメソッドはうるう年が考慮されます。
   *
   * @param int $month 対象とする月。未指定時は現在の月が対象となる。
   * @param int $day 対象とする年。未指定時は現在の年が対象となる。
   * @return int 対象月の日数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function dayInMonth($month = NULL, $year = NULL)
  {
    if ($month === NULL) {
      $month = date('n');
    }

    if ($year === NULL) {
      $year = date('Y');
    }

    return date('t', mktime(0, 0, 0, $month + 1, 0, $year));
  }

  /**
   * 一般的な日付文字列を UNIX タイムスタンプ形式に変換します。
   *
   * @param mixed $string 対象となる日付文字列。
   *   o format が未指定の場合: {@link strtotime()} 関数が理解できるフォーマットを指定。
   *   o format を指定した場合: {@link DateTime::createFromFormat()} メソッドが理解できるフォーマットを指定。
   * @param string $format string の日付フォーマット。指定可能なフォーマットは {@link DateTime::createFromFormat()} を参照。
   * @return int 変換後の UNIX タイムスタンプを返します。変換に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function unixtime($string, $format = NULL)
  {
    $time = NULL;

    if ($string === NULL) {
      $time = time();
    }

    if (is_int($string)) {
      $time = $string;

    } else {
      if ($format === NULL) {
        $array = date_parse($string);
      } else {
        $array = date_parse_from_format($format, $string);
      }

      if ($array) {
        if ($array['error_count']) {
          return FALSE;
        }

        $time = mktime($array['hour'],
          $array['minute'],
          $array['second'],
          $array['month'],
          $array['day'],
          $array['year']);

      } else {
        return FALSE;
      }
    }

    return $time;
  }

  /**
   * 指定した年がうるう年かどうか判定します。
   *
   * @param int $year 対象となる年。
   * @return bool うるう年の場合は TRUE、うるう年以外であれば FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isLeapYear($year)
  {
    $result = FALSE;

    if (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 指定された誕生日を元に現在の年齢を算出します。
   *
   * @param mixed $string {@link dateFormat()} メソッドを参照。
   * @return int 現在の年齢を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function age($string)
  {
    $time = self::unixtime($string);

    if ($time !== FALSE) {
      $birthDate = (int) date('Ymd', $time);
      $currentDate = (int) date('Ymd');

      $age = (int)(($currentDate - $birthDate) / 10000);

      return $age;
    }

    return FALSE;
  }

  /**
   * ロケールに基いて日付と時刻を書式化します。
   * <code>
   * echo $date->date('Y/m/d H:i (D)');
   * </code>
   *
   * @param string $format 日付の書式。利用可能なオプションは {@link date()} 関数を参照。
   *   ロケールが考慮されるオプションは以下の通り。
   *     o M、F: 月
   *     o D、l: 曜日
   *     o a、A: 午前・午後
   *   言語によってロケールデータが提供されない場合は application.yml に定義された 'language' 属性に基いてロケール情報を参照する。
   * @param mixed $time 対象となる日付
   *   '1970/1/1'、'1970/1/1 00:00:00' のような文字列形式 (指定可能な書式は {@link strtotime()} 関数を参照)、または UNIX タイムスタンプ形式 (int 型) で指定可能。
   *   未指定の場合は実行環境の時刻でフォーマットされる。
   * @param string $language 言語の指定。未指定の場合は application.yml に定義されている 'language' を参照。
   * @return string フォーマットされた日付文字列を返します。
   *   日付文字列が解析できなかった場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function date($format, $time = NULL, $language = NULL)
  {
    if ($time === NULL) {
      $time = time();
    }

    $timestamp = self::unixtime($time);
    $result = FALSE;

    if ($timestamp !== FALSE) {
      if ($language === NULL) {
        $language = Delta_Config::getApplication()->getString('language');
      }

      $language = str_replace('-', '_', $language);

      // date() で変換しない記号をエスケープ
      $from = array('M', 'F', 'D', 'l', 'a', 'A');
      $to = array('\M', '\F', '\D', '\l', '\a', '\A');

      $customFormat = str_replace($from, $to, $format);
      $result = date($customFormat, $timestamp);

      // 言語にマッチするロケールを取得
      $path = sprintf('%s/vendors/unicode/locales/%s.php',
        DELTA_ROOT_DIR,
        $language);

      if (is_file($path)) {
        require $path;

      } else {
        // 'Ja_JP' のようなリクエストが送信された場合はファイルを見つけられないため、'Ja' を参照する
        if (($pos = strpos($language, '_')) !== FALSE) {
          $language = substr($language, 0, $pos);
          $path = sprintf('%s/vendors/unicode/locales/%s.php',
            DELTA_ROOT_DIR,
            $language);

          if (is_file($path)) {
            require $path;
          }
        }

        if (!isset($data)) {
          // 該当言語ファイルが見つからない場合、アプリケーションに定義されたデフォルト言語のロケールを参照する
          $language = Delta_Config::getApplication()->getString('language');
          $path = sprintf('%s/vendors/unicode/locales/%s.php',
            DELTA_ROOT_DIR,
            $language);

          if (is_file($path)) {
            require $path;
          }
        }
      }

      // ロケールファイルが見つかった場合
      if (isset($data)) {
        $parse = getdate($timestamp);
        $result = preg_replace_callback('/M|F|D|l|a|A/',
          function($match) use ($data, $parse, $timestamp) {

          switch ($match[0]) {
            // 月 (ショート)
            case 'M':
              $index = $parse['mon'] - 1;

              if (isset($data['month']['abbreviated'])) {
                $result = $data['month']['abbreviated'][$index];
              } else {
                $result = date('M', $timestamp);
              }

              break;

            // 月 (ロング)
            case 'F':
              $index = $parse['mon'] - 1;

              if (isset($data['month']['wide'])) {
                $result = $data['month']['wide'][$index];
              } else {
                $result = date('F', $timestamp);
              }

              break;

            // 曜日 (ショート)
            case 'D':
              $index = $parse['wday'];

              if (isset($data['day']['abbreviated'])) {
                $result = $data['day']['abbreviated'][$index];
              } else {
                $result = date('D', $timestamp);
              }

              break;

            // 曜日 (ロング)
            case 'l':
              $index = $parse['wday'];

              if (isset($data['day']['wide'])) {
                $result = $data['day']['wide'][$index];
              } else {
                $result = date('l', $timestamp);
              }

              break;

            // 午前/午後 (大文字)
            case 'a':
            case 'A':
              if (isset($data['period'])) {
                if ($parse['hours'] <= 11) {
                  $result = $data['period']['wide'][0];
                } else {
                  $result = $data['period']['wide'][1];
                }
              } else {
                if ($parse['hours'] <= 11) {
                  $result = 'AM';
                } else {
                  $result = 'PM';
                }
              }

              if ($match[0] === 'a') {
                $result = strtolower($result);
              } else {
                $result = strtoupper($result);
              }

              break;
          }

          return $result;

        }, $result);

      } else {
        $result = date($format, $timestamp);
      }
    }

    return $result;
  }
}
