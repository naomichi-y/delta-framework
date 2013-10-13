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
 * クレジットカード番号の正当性を検証します。
 * 検証のアルゴリズムには {@link http://www.braemoor.co.uk/software/creditcard.php Luhn} が使用されます。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_CreditCardValidator
 *
 *     # カード番号フィールド 1。
 *     number1:
 *
 *     # カード番号フィールド 2。
 *     number2:
 *
 *     # カード番号フィールド 3。
 *     number3:
 *
 *     # カード番号フィールド 4。
 *     number4:
 *
 *     # 有効扱いとするカードタイプ (CREDIT_TYPE_* 定数、または定数値) を配列形式で指定。
 *     # 未指定の場合は定義済みの全てのカードが許可される。
 *     allows:
 *
 *     # カードタイプの識別に失敗した場合に通知するエラーメッセージ。
 *     unsupportedError: {default_message}
 *
 *     # カード番号が 13～19 桁の数値以外で構成されている場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 *
 *     # チェックディジットが不正な場合に通知するエラーメッセージ。
 *     checkDigitError: {default_message}
 *
 *     # 'allows' で許可されていないカードタイプが指定された場合に通知するエラーメッセージ。
 *     denyCardError: {default_message}
 * </code>
 *
 * o 'number*' が未指定の場合は、{validator_id} フィールドを用いた検証が実行されます。
 * o 'number{1-4}' は内部で文字列として結合されます。従って、1 つのフィールドにカード番号をまとめて入力することも可能です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
 * @category delta
 * @package validator
 * @link http://en.wikipedia.org/wiki/Credit_card_number Bank card number
 * @todo 2.0
 */
class Delta_CreditCardValidator extends Delta_Validator
{
  /**
   * クレジットカードタイプ定数。(American Express)
   */
  const CREDIT_TYPE_AMERICAN_EXPRESS = 'americanExpress';

  /**
   * クレジットカードタイプ定数。(Diners Club Carte Blanche)
   */
  const CREDIT_TYPE_DINERS_CLUB_CARTE_BLANCHE = 'dinersClubCarteBlanche';

  /**
   * クレジットカードタイプ定数。(Diners Club)
   */
  const CREDIT_TYPE_DINERS_CLUB = 'dinersClub';

  /**
   * クレジットカードタイプ定数。(Discover)
   */
  const CREDIT_TYPE_DISCOVER = 'discover';

  /**
   * クレジットカードタイプ定数。(Diners Club Enroute)
   */
  const CREDIT_TYPE_DINERS_CLUB_ENROUTE = 'dinersClubEnroute';

  /**
   * クレジットカードタイプ定数。(JCB)
   */
  const CREDIT_TYPE_JCB = 'jcb';

  /**
   * クレジットカードタイプ定数。(Maestro)
   */
  const CREDIT_TYPE_MAESTRO = 'maestro';

  /**
   * クレジットカードタイプ定数。(Master Card)
   */
  const CREDIT_TYPE_MASTERCARD = 'masterCard';

  /**
   * クレジットカードタイプ定数。(Solo)
   */
  const CREDIT_TYPE_SOLO = 'solo';

  /**
   * クレジットカードタイプ定数。(Switch)
   */
  const CREDIT_TYPE_SWITCH = 'switch';

  /**
   * クレジットカードタイプ定数。(VISA)
   */
  const CREDIT_TYPE_VISA = 'visa';

  /**
   * クレジットカードタイプ定数。(VISA Electron)
   */
  const CREDIT_TYPE_VISA_ELECTRON = 'visaElectron';

  /**
   * クレジットカードタイプ定数。(Laser Card)
   */
  const CREDIT_TYPE_LASERCARD = 'laserCard';

  /**
   * @var string
   */
  protected $_validatorId = 'creditCard';

  /**
   * @var array
   */
  private static $_creditCards = array(
    array('name' => self::CREDIT_TYPE_AMERICAN_EXPRESS,
          'length' => '15',
          'prefixes' => '34,37',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_DINERS_CLUB_CARTE_BLANCHE,
          'length' => '14',
          'prefixes' => '300,301,302,303,304,305',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_DINERS_CLUB,
          'length' => '14,16',
          'prefixes' => '305,36,38,54,55',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_DINERS_CLUB_ENROUTE,
          'length' => '16',
          'prefixes' => '6011,622,64,65',
          'checkdigit' => TRUE
        ),
    array('name' => self::CREDIT_TYPE_DINERS_CLUB_ENROUTE,
          'length' => '15',
          'prefixes' => '2014,2149',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_JCB,
          'length' => '16',
          'prefixes' => '35',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_MAESTRO,
          'length' => '12,13,14,15,16,18,19',
          'prefixes' => '5018,5020,5038,6304,6759,6761',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_MASTERCARD,
          'length' => '16',
          'prefixes' => '51,52,53,54,55',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_SOLO,
          'length' => '16,18,19',
          'prefixes' => '6334,6767',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_SWITCH,
          'length' => '16,18,19',
          'prefixes' => '4903,4905,4911,4936,564182,633110,6333,6759',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_VISA,
          'length' => '13,16',
          'prefixes' => '4',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_VISA_ELECTRON,
          'length' => '16',
          'prefixes' => '417500,4917,4913,4508,4844',
          'checkdigit' => TRUE
         ),
    array('name' => self::CREDIT_TYPE_LASERCARD,
          'length' => '16,17,18,19',
          'prefixes' => '6304,6706,6771,6709',
          'checkdigit' => TRUE
         )
    );

  /**
   * カードタイプを取得します。
   * <i>このメソッドはカード番号のチェックディジットを計算しません。
   * カード番号が正当なものであるかチェックするには、{@link validateCheckDigit()} メソッドを使用して下さい。</i>
   *
   * @param string $cardNumber チェック対象のカード番号。
   * @return int CREDIT_TYPE_* 定数を返します。カードタイプが不明な場合は -1 を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
   */
  private function getCardType($cardNumber)
  {
    $issuerType = -1;
    $j = sizeof(self::$_creditCards);

    for ($i = 0; $i < $j; $i++) {
      $prefixes = explode(',', self::$_creditCards[$i]['prefixes']);

      foreach ($prefixes as $prefix) {
        if (strpos($cardNumber, $prefix) === 0) {
          $lengths = explode(',', self::$_creditCards[$i]['length']);

          if (in_array(strlen($cardNumber), $lengths)) {
            $issuerType = $i;
            break 2;
          }
        }
      }
    }

    return $issuerType;
  }

  /**
   * カード番号のチェックディジットを計算します。
   *
   * @param string $cardNumber チェック対象のカード番号。
   * @return bool カード番号が正しければ TRUE、間違っていれば FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
   */
  private function validateCheckDigit($cardNumber)
  {
    $checksum = 0; // running checksum total
    $j = 1; // takes value of 1 or 2

    // Process each digit one by one starting at the right
    for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
      // Extract the next digit and multiply by 1 or 2 on alternative digits.
      $calc = $cardNumber[$i] * $j;

      // If the result is in two digits add 1 to the checksum total
      if ($calc > 9) {
        $checksum = $checksum + 1;
        $calc = $calc - 10;
      }

      // Add the units element to the checksum total
      $checksum = $checksum + $calc;

      // Switch the value of j
      if ($j ==1) {
        $j = 2;
      } else {
        $j = 1;
      }
    }

    // All done - if checksum is divisible by 10, it is a valid modulus 10.
    // If not, report an error.
    if ($checksum % 10 != 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
   */
  private function validateCardNumber($cardNumber, &$creditCardType = NULL)
  {
    $result = TRUE;
    $cardNumber = str_replace('-', '', $cardNumber);

    // Establish card type
    $creditCardType = $this->getCardType($cardNumber);

    // If card type not found, report an #
    if ($creditCardType == -1) {
      $this->setError('unsupportedCardError');
      $result = FALSE;

    // Check that the number is numeric and of the right sort of length.
    } else if (!preg_match('/^[0-9]{13,19}$/', $cardNumber)) {
      $this->setError('formatError');
      $result = FALSE;

    // Now check the modulus 10 check digit - if required
    } else if (self::$_creditCards[$creditCardType]['checkdigit'] && !$this->validateCheckDigit($cardNumber)) {
      $this->setError('checkDigitError');
      $result = FALSE;
    }

    return $result;
  }

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;
    $request = Delta_FrontController::getInstance()->getRequest();

    $number1 = $request->getParameter($this->_conditions->getString('number1'));
    $number2 = $request->getParameter($this->_conditions->getString('number2'));
    $number3 = $request->getParameter($this->_conditions->getString('number3'));
    $number4 = $request->getParameter($this->_conditions->getString('number4'));

    $fullNumber = $number1 . $number2 . $number3 . $number4;
    $hasCardValue = FALSE;

    if (strlen($fullNumber)) {
      $hasCardValue = TRUE;

    } else {
      if (strlen($this->_fieldValue)) {
        $hasCardValue = TRUE;
        $fullNumber = $this->_fieldValue;
      }
    }

    if ($hasCardValue) {
      if (!$this->validateCardNumber($fullNumber, $creditCardType)) {
        $result = FALSE;

      } else {
        $allows = $this->_conditions->getArray('allows');

        if ($allows && !in_array(self::$_creditCards[$creditCardType]['name'], $allows)) {
          $this->setError('denyCardError');
          $result = FALSE;
        }
      }
    }

    return $result;
  }
}
