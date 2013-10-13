<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 対象文字列に機種依存文字 (Windows-31J) が含まれていないか検証します。
 * 対象となる文字は、ヒューチャーフォンのキャリア (docomo、AU、SoftBank) が定義した絵文字コードを含みます。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * validate:
 *   {validator_id}:
 *     # バリデータクラス名。
 *     class: Delta_VendorCharacterValidator
 *
 *     # 機種依存文字が含まれる場合に通知するエラーメッセージ。
 *     formatError: {default_message}
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator.i18n
 * @todo 2.0 ドキュメント更新
 */

class Delta_VendorCharacterValidator extends Delta_Validator
{
  /**
   * @var string
   */
  protected $_validatorId = 'vendorCharacter';

  /**
   * @see Delta_Validator::validate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function validate()
  {
    $result = TRUE;

    if (strlen($this->_fieldValue)) {
      $detectEncoding = mb_detect_encoding($this->_fieldValue);

      if ($detectEncoding !== 'UTF-8') {
        $fieldValue = mb_convert_encoding($this->_fieldValue, 'UTF-8', $detectEncoding);
      } else {
        $fieldValue = $this->_fieldValue;
      }

      // 私用領域 (Private Use) が使われてる場合は依存文字が含まれてるとみなす
      if (preg_match('/\p{Co}/u', $fieldValue)) {
        $this->setError('formatError');
        $result = FALSE;

      } else {
        // Windows-31J の文字を検知する
        $sjisValue = mb_convert_encoding($fieldValue, 'Shift_JIS', 'UTF-8');
        $revertValue = mb_convert_encoding($sjisValue, 'UTF-8', 'Shift_JIS');

        if ($fieldValue !== $revertValue) {
          $this->setError('formatError');
          $result = FALSE;
        }
      }
    }

    return $result;
  }
}
