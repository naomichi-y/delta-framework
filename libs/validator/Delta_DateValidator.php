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
 *     # 指定可能なパターンは {@link strftime()} 関数のマニュアルを参照。
 *     format: %Y%m%d
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
 *     formatError: {default_message}
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
 * @todo 2.0 マニュアル更新
 */
class Delta_DateValidator extends Delta_Validator
{
  protected $_validatorId = 'date';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;
    $format = $this->_conditions->getString('format', '%Y%m%d');

    // 日付が年・月・日フィールドに分割されている
    if ($this->_conditions->hasName('yearField') &&
      $this->_conditions->hasName('monthField') &&
      $this->_conditions->hasName('dayField')) {

      $request = Delta_FrontController::getInstance()->getRequest();

      $year = (int) $request->getParameter($this->_conditions->get('yearField'));
      $month = (int) $request->getParameter($this->_conditions->get('monthField'));
      $day = (int) $request->getParameter($this->_conditions->get('dayField'));

      $result = $this->validateDateAllow($year, $month, $day);

    // 日付が 1 つのフィールドにまとめられている
    } else if (function_exists('strptime') && strlen($this->_fieldValue)) {
      $parse = strptime($this->_fieldValue, $format);

      if ($parse !== FALSE && strlen($parse['unparsed']) == 0) {
        $year = $parse['tm_year'] + 1900;
        $month = $parse['tm_mon'] + 1;
        $day = $parse['tm_mday'];

        $result = $this->validateDateAllow($year, $month, $day);
      }
    }

    return $result;
  }

  /**
   * @param int $year
   * @param int $month
   * @param int $day
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function validateDateAllow($year, $month, $day)
  {
    $result = TRUE;

    $allowPast = $this->_conditions->getBoolean('allowPast', TRUE);
    $allowCurrent = $this->_conditions->getBoolean('allowCurrent', TRUE);
    $allowFuture = $this->_conditions->getBoolean('allowFuture', TRUE);

    $currentDate = mktime(0, 0, 0, date('m'), date('d'), date('y'));
    $validateDate = mktime(0, 0, 0, $month, $day, $year);

    // 不正な日付書式でないかチェック
    if (checkdate($month, $day, $year)) {
      // 過去の日付指定を無効とする
      if (!$allowPast) {
        if ($allowCurrent) {
          if ($validateDate < $currentDate) {
            $result = FALSE;
          }

        } else {
          if ($validateDate <= $currentDate) {
            $result = FALSE;
          }
        }
      }

      // 未来の日付指定を無効とする
      if (!$allowFuture) {
        if ($allowCurrent) {
          if ($currentDate < $validateDate) {
            $result = FALSE;
          }

        } else {
          if ($currentDate <= $validateDate) {
            $result = FALSE;
          }
        }
      }

      if (!$result) {
        $this->setError('allowError');
      }

    // 日付の書式が不正
    } else {
      $this->setError('formatError');
      $result = FALSE;
    }

    return $result;
  }
}
