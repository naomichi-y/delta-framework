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
 * データの変換を行うコンバータの抽象クラスです。
 * 全てのコンバータは Delta_Converter を継承する必要があります。
 *
 * コンバータは変換後のデータを {@link Delta_Form フォーム} オブジェクトに格納します。
 * {@link Delta_HttpRequest リクエスト} データ自体が書き換えられることはありません。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 */
abstract class Delta_Converter extends Delta_Object
{
  /**
   * @var string
   */
  protected $_converterId;

  /**
   * @var Delta_ParameterHolder
   */
  protected $_holder;

  /**
   * コンストラクタ。
   *
   * @param string $converterId コンバータ ID。
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($converterId, Delta_ParameterHolder $holder)
  {
    $this->_converterId = $converterId;
    $this->_holder = $holder;
  }

  /**
   * コンバート処理を行います。
   *
   * @param string $string コンバート対象となる文字列。
   * @return string コンバート後の値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function convert($string);
}
