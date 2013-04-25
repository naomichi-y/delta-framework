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
 * 正規表現による文字列の変換を行います。
 *
 * ビヘイビアファイルの設定例:
 * <code>
 * convert:
 *   {convert_id}:
 *     names: {field_name,...}
 *     converters:
 *       # コンバータクラス名。
 *       - class: Delta_MaskConverter
 *
 *         # 置換対象の文字列パターン。
 *         # Perl 互換の正規表現が使用可能。
 *         pattern:
 *
 *         # 置換後の文字列。
 *         replace: ''
 * </code>
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 */
class Delta_MaskConverter extends Delta_Converter
{
  /**
   * @var string
   */
  private $_pattern;

  /**
   * @var string
   */
  private $_replace;

  /**
   * @throws Delta_ConfigurationException コンバータの設定に問題がある場合に発生。
   * @see Delta_Converter::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($converterId, Delta_ParameterHolder $holder)
  {
    parent::__construct($converterId, $holder);

    $this->_pattern = $holder->getString('pattern');
    $this->_replace = $holder->getString('replace');

    if (!preg_match('/^\/.+\/$/', $this->_pattern)) {
      $message = sprintf('"pattern" attribute is invalid. [%s]', $converterId);
      throw new Delta_ConfigurationException($message);
    }
  }

  /**
   * @see Delta_Converter::convert()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function convert($string)
  {
    $convert = preg_replace($this->_pattern, $this->_replace, $string);

    return $convert;
  }
}
