<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer.i18n
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * マルチバイト文字列中の全角・半角を変換します。
 * このクラスは日本語環境でのみ使用可能です。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_MultibyteKanaConverter
 *
 *         # 変換タイプの指定。
 *         #   - full: 半角英数字、半角スペース、半角カタカナを全角文字に変換。
 *         #   - half: 全角英数字、全角スペースを半角文字に変換。(全角カタカナは半角カタカナに変換しない)
 *         #   - katakana: 全角ひらがなを全角カタカナに変換。
 *         #   - kana: 全角カタカナ、半角カタカナを全角ひらがなに変換。
 *         type:
 *
 *         # カナ変換オプション。{@link mb_convert_kana()} の $option に渡す引数。
 *         custom: {string}
 * </code>
 *
 * ※'type'、'custom' のいずれかの指定が必須です。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer.i18n
 * @todo 2.0 ドキュメント更新
 */
class Delta_MultibyteKanaSanitizer extends Delta_Sanitizer
{
  /**
   * @see Delta_Converter::sanitize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sanitize()
  {
    $type = $this->_conditions->getString('type');
    $custom = $this->_conditions->getString('custom');

    $option = NULL;

    switch ($type) {
      case 'full':
        $option = 'ASK';
        break;

      case 'half':
        $option = 'as';
        break;

      case 'katakana':
        $option = 'C';
        break;

      case 'kana':
        $option = 'cH';
        break;

      default:
        $option = $custom;
    }

    if ($option) {
      $internalEncoding = Delta_Config::getApplication()->get('charset.default');
      $fieldValue = mb_convert_kana($this->_fieldValue, $option, $internalEncoding);
    }

    return $fieldValue;
  }
}
