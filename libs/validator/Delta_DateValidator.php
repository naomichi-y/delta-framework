<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * フォームから送信された日付フォーマットの正当性を検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_DateValidator
 *
 *     # 日付フォーマットのチェックパターン。
 *     # 指定可能なパターンは strftime() 関数のマニュアルを参照。
 *     # 'format' が未指定 (または Windows 環境) の場合は、'%m[-/]%d[-/]%Y' (または '%Y[-/]%m[-/]%d') 形式で検証が行われます。
 *     format:
 *
 *     # 年の検証フィールド。
 *     yearField:
 *
 *     # 月の検証フィールド。
 *     monthField:
 *
 *     # 日の検証フィールド。
 *     dayField:
 *
 *     # 日付フォーマットが不正な場合に通知するエラーメッセージ。
 *     matchError: {default_message}
 *
 *     # 過去の日付を許可する場合は TRUE を指定。
 *     allowPast: TRUE
 *
 *     # 今日の日付を許可する場合は TRUE を指定。
 *     allowCurrent: TRUE
 *
 *     # 未来の日付を許可する場合は TRUE を指定。
 *     allowFuture: TRUE
 *
 *     # 許可されない日付が指定された場合に通知するエラーメッセージ。
 *     allowError: {default_message}
 * </code>
 * ※'yearField'、'monthField'、'dayField' が未指定の場合は、バリデータ参照元のフィールド名で検証が行われます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_DateValidator extends Delta_Validator
{
  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);

    $parseError = FALSE;
    $format = $holder->getString('format');

    // 日付フォーマットのチェック
    if (isset($format)) {
      if (function_exists('strptime')) {
        if (strlen($value) == 0) {
          return TRUE;
        }

        $date = strptime($value, $format);

        if ($date !== FALSE && strlen($date['unparsed']) == 0) {
          $year = $date['tm_year'] + 1900;
          $month = $date['tm_mon'] + 1;
          $day = $date['tm_mday'];

          return $this->validateDateAllow($fieldName, $year, $month, $day, $holder);

        } else {
          $parseError = TRUE;
        }

      } else {
        return TRUE;
      }

    } else {
      // Get date field values
      $form = Delta_DIContainerFactory::getContainer()->getComponent('form');

      if ($form->hasName($holder->getString('yearField'))) {
        $year = $form->get($holder->getString('yearField'));
        $month = $form->get($holder->getString('monthField'));
        $day = $form->get($holder->getString('dayField'));

        if (strlen($year) == 0 && strlen($month) == 0 && strlen($day) == 0) {
          return TRUE;
        }

      } else {
        if (strlen($value) == 0) {
          return TRUE;
        }

        $result = preg_split('/-|\//', $value);

        if (sizeof($result) == 3) {
          if (strlen($result[0]) == 2) {
            $year = $result[2];
            $month = $result[0];
            $day = $result[1];

          } else {
            $year = $result[0];
            $month = $result[1];
            $day = $result[2];
          }

        } else {
          $year = $month = $day = NULL;
        }
      }
    }

    if (!$parseError && strlen($year) && strlen($month) && strlen($day)) {
      $date = $year . $month . $day;

      if (preg_match('/^[\d]+$/', $date) && checkdate($month, $day, $year)) {
        return $this->validateDateAllow($fieldName, $year, $month, $day, $holder);
      }
    }

    $message = $holder->getString('matchError');

    if ($message === NULL) {
      $message = sprintf('Date format is illegal. [%s]', $fieldName);
    }

    $this->sendError($fieldName, $message);

    return FALSE;
  }

  /**
   * 検証する日付が許可されている範囲 (過去・未来) 内にあるかチェックします。
   *
   * @param string 検証するフィールド名。
   * @param int $year 検証対象の年。
   * @param int $month 検証対象の月。
   * @param int $day 検証対象の日。
   * @param Delta_ParameterHolder パラメータホルダ。
   * @return bool 日付が許可されている範囲内かどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function validateDateAllow($fieldName, $year, $month, $day, Delta_ParameterHolder $holder)
  {
    $allowPast = $holder->getBoolean('allowPast', TRUE);
    $allowCurrent = $holder->getBoolean('allowCurrent', TRUE);
    $allowFuture = $holder->getBoolean('allowFuture', TRUE);

    $allowError = FALSE;

    $currentDate = mktime(0, 0, 0, date('m'), date('d'), date('y'));
    $checkDate = mktime(0, 0, 0, $month, $day, $year);

    // $year は 2038 年まで
    if ($checkDate === FALSE) {
      $allowError = TRUE;
    }

    if (!$allowPast) {
      if ($allowCurrent) {
        if ($checkDate < $currentDate) {
          $allowError = TRUE;
        }
      } else {
        if ($checkDate <= $currentDate) {
          $allowError = TRUE;
        }
      }
    }

    if (!$allowFuture && !$allowError) {
      if ($allowCurrent) {
        if ($currentDate < $checkDate) {
          $allowError = TRUE;
        }
      } else {
        if ($currentDate <= $checkDate) {
          $allowError = TRUE;
        }
      }
    }

    if ($allowError) {
      $message = $holder->getString('allowError');

      if ($message === NULL) {
        $message = sprintf('This value is invalid date. [%s]', $fieldName);
      }

      $this->sendError($fieldName, $message);

      return FALSE;
    }

    return TRUE;
  }
}
