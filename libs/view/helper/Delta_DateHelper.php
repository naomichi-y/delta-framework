<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 日付や時刻を表示するためのヘルパメソッドを提供します。
 * ヘルパが提供するいくつかのメソッドはクライアントから送信された Accept-Language ヘッダを参照し、クライアント環境に適した日付表記の文字列を返します。
 * 日付・時刻表記の詳しい仕様は {@link Delta_DateUtils::date()} メソッドを参照して下さい。
 *
 * このヘルパは、$date という変数名であらかじめビューにインスタンスが割り当てられています。
 * <code>
 * <?php echo $date->{method}; ?>
 * </code>
 * global_helpers.yml の設定例:
 * <code>
 * date:
 *   # ヘルパクラス名。
 *   class: Delta_DateHelper
 *
 *   # 日付の出力フォーマット。
 *   # 指定可能なフォーマットは {@link strftime()} 関数を参照。
 *   dateFormat: 'Y-m-d'
 *
 *   # 時間を含めた日付の出力フォーマット。
 *   # 指定可能なフォーマットは {@link strftime()} 関数を参照。
 *   datetimeFormat: 'Y-m-d H:i'
 *
 *   # {@link timeAgo()} メソッドで使用される属性。
 *   timeAgo:
 *     # 経過時間の接頭辞。'
 *     prefixAgo:
 *
 *     # 経過時間の接尾辞。'24 minutes ago' のような表示形式となる。
 *     suffixAgo: ' ago'
 *
 *     # 残り時間の接頭辞。
 *     prefixFromNow:
 *
 *     # 残り時間の接尾辞。'24 from now' のような表示形式となる。
 *     suffixFromNow: ' from now'
 *
 *     # 未来の日付を許可するかどうか。
 *     # FALSE を指定した場合、{@link timeAgo()} メソッドに未来時間が指定された際は FALSE を返します。
 *     allowFuture: FALSE
 *
 *     # 秒の表示。
 *     seconds: '\1 seconds'
 *
 *     # 分の表示。
 *     minutes: '\1 minutes'
 *
 *     # 時間の表示。
 *     hours: '\1 hours'
 *
 *     # 日数の表示。
 *     days: '\1 days'
 *
 *     # 月の表示。
 *     months: '\1 months'
 *
 *     # 年の表示。
 *     years: '\1 yea years'
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */

class Delta_DateHelper extends Delta_Helper
{
  /**
   * 指定した日付から経過した時間を分かりやすい文字列表記に変換します。
   * <code>
   * // '30 minutes ago'
   * $date->timeAgo(time() - 1800);
   *
   * // '1 hours ago'
   * $date->timeAgo(time() - 3600);
   * </code>
   *
   * @param mixied $time {@link Delta_DateUtils::date()} メソッドを参照。
   * @return string 変換後の文字列を返します。出力フォーマットはヘルパ属性 'timeAgo' を参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function timeAgo($time)
  {
    $result = FALSE;
    $timestamp = Delta_DateUtils::unixtime($time);

    if ($timestamp !== FALSE) {
      $current = time();
      $allowFuture = $this->_config->getBoolean('timeAgo.allowFuture');

      // 過去が指定された場合
      if ($current >= $timestamp) {
        $diff = $current - $timestamp;
        $future = FALSE;

        $prefix = $this->_config->getString('timeAgo.prefixAgo', '');
        $suffix = $this->_config->getString('timeAgo.suffixAgo', ' ago');

      // 未来が指定された場合
      } else if ($allowFuture) {
        $diff = $timestamp - $current;
        $future = TRUE;

        $prefix = $this->_config->getString('timeAgo.prefixFromNow', '');
        $suffix = $this->_config->getString('timeAgo.suffixFromNow', ' from now');

      } else {
        return FALSE;
      }

      $ago = NULL;
      $value = NULL;

      $minute = 60;
      $hour = $minute * 60;
      $day = $hour * 24;
      $month = $day * 30;
      $year = $month * 12;

      // 60 秒未満
      if ($diff < $minute) {
        $ago = $diff;
        $value = $this->_config->getString('timeAgo.seconds', '\1 seconds');

      // 60 分未満
      } else if ($diff < $hour) {
        $ago = $diff / $minute;
        $value = $this->_config->getString('timeAgo.minutes', '\1 minutes');

      // 24 時間未満
      } else if ($diff < $day) {
        $ago = $diff / $hour;
        $value = $this->_config->getString('timeAgo.hours', '\1 hours');

      // 約 1 ヵ月未満
      } else if ($diff < $month) {
        $ago = $diff / $day;
        $value = $this->_config->getString('timeAgo.days', '\1 days');

      // 約 1 年未満
      } else if ($diff < $year) {
        $ago = $diff / $month;
        $value = $this->_config->getString('timeAgo.months', '\1 months');

      // 約 1 年以上前
      } else {
        $ago = $diff / $year;
        $value = $this->_config->getString('timeAgo.years', '\1 years');
      }

      $result = sprintf('%s%s%s',
        $prefix,
        str_replace('\1', (int) $ago, $value),
        $suffix);
    }

    return $result;
  }

  /**
   * 日付文字列を書式に基いて変換します。
   * <code>
   * // 例えば '2013/04/10 (水)' を返す
   * $date->format('Y/m/d (D)');
   * </code>
   *
   * @param string $format {@link strtotime()} 関数が解釈可能な書式。
   * @param mixied $time {@link Delta_DateUtils::date()} メソッドを参照。
   * @return string 書式に準拠した日付文字列を返します。format が解析できなかった場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function format($format, $time = NULL)
  {
    if ($time === NULL) {
      $timestamp = time();
    } else {
      $timestamp = Delta_DateUtils::unixtime($time);
    }

    $result = FALSE;

    if ($timestamp) {
      $request = Delta_FrontController::getInstance()->getRequest();
      $language = $request->getAcceptLanguage(TRUE);
      $result = Delta_DateUtils::date($format, $timestamp, $language);
    }

    return $result;
  }

  /**
   * 日付文字列 time をヘルパ属性 'dateFormat' に定義された形式で書式化します。
   * <code>
   * {config/global_helpers.yml}
   * date:
   *   class: Delte_DateHelper
   *   dateFormat: 'Y-m-d (l)'
   * </code>
   * <code>
   * // 例えば '2013-04-09 (Tue)' を返す
   * $date->dateFormat();
   * </code>
   *
   * @param mixied $time {@link Delta_DateUtils::date()} メソッドを参照。
   * @return string フォーマットされた日付文字列を返します。
   *   日付文字列が解析できなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dateFormat($time = NULL)
  {
    if ($time === NULL) {
      $timestamp = time();
    } else {
      $timestamp = Delta_DateUtils::unixtime($time);
    }

    $result = FALSE;

    if ($timestamp !== FALSE) {
      $format = $this->_config->getString('dateFormat', 'Y-m-d');

      $request = Delta_FrontController::getInstance()->getRequest();
      $language = $request->getAcceptLanguage(TRUE);
      $result = Delta_DateUtils::date($format, $timestamp, $language);
    }

    return $result;
  }

  /**
   * 日付文字列 time をヘルパ属性 'dateFormat' に定義された形式で書式化します。
   * <code>
   * {config/global_helpers.yml}
   * date:
   *   class: Delte_DateHelper
   *   datetimeFormat: 'Y-m-d H:i'
   * </code>
   * <code>
   * // 例えば '2013-04-09 22:04' を返す
   * $date->datetimeFormat();
   * </code>
   *
   * @param mixied $time {@link Delta_DateUtils::date()} メソッドを参照。
   * @return string フォーマットされた日付・時刻文字列を返します。
   *   日付文字列が解析できなかった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function datetimeFormat($time = NULL)
  {
    if ($time === NULL) {
      $timestamp = time();
    } else {
      $timestamp = Delta_DateUtils::unixtime($time);
    }

    $result = FALSE;

    if ($timestamp !== FALSE) {
      $format = $this->_config->getString('datetimeFormat', 'Y-m-d H:i');

      $request = Delta_FrontController::getInstance()->getRequest();
      $language = $request->getAcceptLanguage(TRUE);
      $result = Delta_DateUtils::date($format, $timestamp, $language);
    }

    return $result;
  }
}
