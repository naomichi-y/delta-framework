<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データの変換を行うコンバータの抽象クラスです。
 * 全てのコンバータは Delta_Converter を継承する必要があります。
 *
 * コンバータは変換後のデータを {@link Delta_Form フォーム} オブジェクトに格納します。
 * {@link Delta_HttpRequest リクエスト} データ自体が書き換えられることはありません。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
 * @todo 2.0 ドキュメント更新
 */
abstract class Delta_Sanitizer extends Delta_Object
{
  /**
   * @var string
   */
  protected $_fieldValue;

  /**
   * @var Delta_ParameterHolder
   */
  protected $_conditions;

  public function __construct($fieldValue, Delta_ParameterHolder $conditions)
  {
    $this->_fieldValue = $fieldValue;
    $this->_conditions = $conditions;
  }

  abstract public function sanitize();
}
