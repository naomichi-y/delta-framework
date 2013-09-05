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
 *     unknownTypeError: {default_message}
 *
 *     # カード番号が 13～19 桁の数値以外で構成されている場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 *
 *     # チェックディジットが不正な場合に通知するエラーメッセージ。
 *     numberError: {default_message}
 *
 *     # カード番号の長さが不正な場合に通知するエラーメッセージ。
 *     lengthError: {default_message}
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
   * @var array
   */
  private static $_cards = array(
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
   * カード番号が正当なものであるかチェックするには、{@link isValidCheckDigit()} メソッドを使用して下さい。</i>
   *
   * @param string $cardNumber チェック対象のカード番号。
   * @return int CREDIT_TYPE_* 定数を返します。カードタイプが不明な場合は -1 を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
   */
  public static function getCardType($cardNumber)
  {
    $issuerType = -1;
    $j = sizeof(self::$_cards);

    for ($i = 0; $i < $j; $i++) {
      $prefixes = explode(',', self::$_cards[$i]['prefixes']);

      foreach ($prefixes as $prefix) {
        if (strpos($cardNumber, $prefix) === 0) {
          $lengths = explode(',', self::$_cards[$i]['length']);

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
  public static function isValidCheckDigit($cardNumber)
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
   * クレジットカードの書式が正当なものであるかチェックします。
   *
   * @param string $cardNumber チェック対象のカード番号。
   * @param int $cardType チェック対象のカードタイプ。CREDIT_TYPE_* 定数を指定。
   * @param int &$validCardType カードタイプを返します。(CREDIT_TYPE_* 定数)
   * @param int &$errorType エラータイプを返します。
   * @param string &$errorMessage エラーメッセージを返します。
   * @return bool クレジットカードの書式が正しいものであるかどうかを返します。発生しうるエラーは下記の通り。
   *   - unknownTypeError:
   *   - formatError: cardType が指定された場合のみ発生。
   *   - numberError:
   *   - lengthError: cardType が指定された場合のみ発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @author www.braemoor.co.uk <webmeister@braemoor.co.uk>
   */
  public static function isValid($cardNumber,
    $cardType = NULL,
    &$validCardType = NULL,
    &$errorType = NULL,
    &$errorMessage = NULL)
  {
    $cardNumber = str_replace('-', '', $cardNumber);
    $cards = self::$_cards;

    $errors = array(
      'unknownTypeError' => 'Unknown card type',
      'formatError' => 'Credit card number has invalid format',
      'numberError' => 'Credit card number is invalid',
      'lengthError' => 'Credit card number is wrong length'
    );

    // Establish card type
    if ($cardType === NULL) {
      $validCardType = self::getCardType($cardNumber);

    } else {
      $j = sizeof($cards);
      $validCardType = -1;

      for ($i = 0; $i < $j; $i++) {
        // See if it is this card (ignoring the case of the string)
        if (strcmp(strtolower($cardType), strtolower($cards[$i]['name'])) == 0) {
          $validCardType = $i;
          break;
        }
      }
    }

    // If card type not found, report an #
    if ($validCardType == -1) {
      $errorType = 'unknownTypeError';
      $errorMessage = $errors[$errorType];

      return FALSE;
    }

    // Check that the number is numeric and of the right sort of length.
    if ($cardType !== NULL && !preg_match('/^[0-9]{13,19}$/', $cardNumber)) {
      $errorType = 'formatError';
      $errorMessage = $errors[$errorType];

      return FALSE;
    }

    // Now check the modulus 10 check digit - if required
    if ($cards[$validCardType]['checkdigit']) {
      if (!self::isValidCheckDigit($cardNumber)) {
        $errorType = 'numberError';
        $errorMessage = $errors[$errorType];

        return FALSE;
      }
    }

    // The following are the card-specific checks we undertake.

    // Load an array with the valid prefixes for this card
    if ($cardType !== NULL) {
      $prefix = explode(',', $cards[$validCardType]['prefixes']);

      // Now see if any of them match what we have in the card number
      $PrefixValid = FALSE;
      $j = sizeof($prefix);

      for ($i = 0; $i < $j; $i++) {
        if (strpos($cardNumber, $prefix[$i]) === 0) {
          $PrefixValid = TRUE;
          break;
        }
      }

      // If it isn't a valid prefix there's no point at looking at the length
      if (!$PrefixValid) {
        $errorType = 'numberError';
        $errorMessage = $errors[$errorType];

        return FALSE;
      }

      // See if the length is valid for this card
      $lengthValid = FALSE;
      $lengths = explode(',', $cards[$validCardType]['length']);
      $l = sizeof($lengths);

      for ($j = 0; $j < $l; $j++) {
        if (strlen($cardNumber) == $lengths[$j]) {
          $lengthValid = TRUE;
          break;
        }
      }

      // See if all is OK by seeing if the length was valid.
      if (!$lengthValid) {
        $errorType = 'lengthError';
        $errorMessage = $errors[$errorType];

        return FALSE;
      }
    }

    // The credit card is in the required format.
    return TRUE;
  }

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate($fieldName, $value, array $variables = array())
  {
    $holder = $this->buildParameterHolder($variables);
    // @todo 2.0
    exit;
    $form = Delta_ActionForm::getInstance();

    $number1 = $form->get($holder->getString('number1'));
    $number2 = $form->get($holder->getString('number2'));
    $number3 = $form->get($holder->getString('number3'));
    $number4 = $form->get($holder->getString('number4'));

    if (strlen($number1 . $number2 . $number3 . $number4)) {
      $cardNumber = sprintf('%s%s%s%s', $number1, $number2, $number3, $number4);

      if (strlen($cardNumber) == 0) {
        return TRUE;
      }

    } else if (strlen($value) == 0) {
      return TRUE;

    } else {
      $cardNumber = $value;
    }

    if (self::isValid($cardNumber, NULL, $validCardType, $errorType, $errorMessage)) {
      $allows = $holder->getArray('allows');

      if (sizeof($allows)) {
        if (in_array(self::$_cards[$validCardType]['name'], $allows)) {
          return TRUE;
        }

        $errorType = 'denyCardError';
        $errorMessage = 'Specified cards are not accepted.';

      } else {
        return TRUE;
      }
    }

    $message = $holder->getString($errorType, $errorMessage);
    $this->sendError($fieldName, $message);

    return FALSE;
  }
}
