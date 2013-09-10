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
 * 郵便番号が日本式のフォーマットとして正しいかどうか検証します。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_ZipCodeValidator
 *
 *     # 対象とする国。(現在は 'jp' のみ対応)
 *     country: jp
 *
 *     # フィールド 1
 *     number1:
 *
 *     # フィールド 2
 *     number2:
 *
 *     # ハイフンを許可するか ('number*' を指定した場合は無効)
 *     hyphnate: TRUE
 *
 *     # 郵便番号の書式が不正な場合に通知するメッセージ。
 *     matchError: {default_message}
 * </code>
 * o 'number*' が未指定の場合は、{validator_id} フィールドを用いた検証が実行されます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @todo 2.0 ドキュメント更新
 */

class Delta_ZipCodeValidator extends Delta_Validator
{
  protected $_validatorId = 'zipCode';

  /**
   * 国別のハイフンを含む郵便番号パターン、ハイフンを含まない郵便番号パターンリスト。
   * @var array
   */
  protected $_patterns = array(
    'jp' => array('/^\d{3}-\d{4}$/', '/^\d{7}$/')
  );

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if ($this->_conditions->hasName('number1') && $this->_conditions->hasName('number2')) {
      $request = Delta_FrontController::getInstance()->getRequest();
      $number1 = $request->getParameter($this->_conditions->getString('number1'));
      $number2 = $request->getParameter($this->_conditions->getString('number2'));

      $fullZipCode = $number1 . '-' . $number2;
      $hyphnate = TRUE;

    } else {
      $hyphnate = $this->_conditions->getBoolean('hyphnate', FALSE);
      $fullZipCode = $this->_fieldValue;
    }

    $country = $this->_conditions->getString('country', 'jp');

    if (!isset($this->_patterns[$country])) {
      $message = sprintf('Country code is undefined. [%s]', $country);
      throw new Delta_ConfigurationException($message);
    }

    if ($hyphnate) {
      $pattern = $this->_patterns[$country][0];
    } else {
      $pattern = $this->_patterns[$country][1];
    }

    if (!preg_match($pattern, $fullZipCode)) {
      $this->setError('matchError');
      $result = FALSE;
    }

    return $result;
  }
}
