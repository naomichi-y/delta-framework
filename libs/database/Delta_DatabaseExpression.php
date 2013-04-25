<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースの式を {@link Delta_Entity} のプロパティにセットする際に使用します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 */

class Delta_DatabaseExpression extends Delta_Object
{
  /**
   * データベースが理解可能な式や値。
   */
  private $_expression;

  /**
   * コンストラクタ。
   *
   * @param string $expressin データベースが理解可能な式や値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($expression)
  {
    $this->_expression = $expression;
  }

  /**
   * NULL 値を表現します。
   *
   * @return Delta_DatabaseExpression NULL 値を表すインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function null()
  {
    return new Delta_DatabaseExpression('NULL');
  }

  /**
   * 式を文字列形式で取得します。
   *
   * @return string 式を文字列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __toString()
  {
    return $this->_expression;
  }
}
