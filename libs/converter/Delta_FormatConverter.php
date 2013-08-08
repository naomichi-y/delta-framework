<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 複数のフィールドを結合し、指定したフォーマットに文字列変換を行います。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_FormatConverter
 *
 *         # 変換する文字列の形式。使用可能なフォーマットは PHP の {@link sprintf()} 関数を参照。
 *         # フォーマットが不正な場合は {@link Delta_ParseException} が発生。
 *         format:
 *
 *         # 変換元となるフィールド名を配列表記で指定。
 *         arguments:
 *
 *         # フォーマット変換した値をセットするフィールド名。
 *         dest:
 * </code>
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 */
class Delta_FormatConverter extends Delta_Converter
{
  /**
   * @throws Delta_ParseException 'format' 属性の書式解析が失敗した場合に発生。
   * @see Delta_Converter::convert()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convert($string)
  {
    $format = $this->_holder->getString('format');
    $arguments = $this->_holder->getArray('arguments');
    $dest = $this->_holder->getString('dest');

    $form = Delta_ActionForm::getInstance();

    if (is_array($arguments)) {
      $fields = $form->getFields();
      $fieldValues[] = $format;

      foreach ($arguments as $fieldName) {
        $fieldValues[] = Delta_ArrayUtils::find($fields, $fieldName);
      }

      try {
        $result = call_user_func_array('sprintf', $fieldValues);
        $form->set($dest, $result);

      } catch (ErrorException $e) {
        $message = $e->getMessage();
        $message = substr($message, strpos($message, ':') + 2);
        $message = sprintf('%s [%s]', $message, $format);

        throw new Delta_ParseException($message);
      }
    }
  }
}
