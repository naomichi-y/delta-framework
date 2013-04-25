<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.decorator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 変数のデータを HTML エスケープする機能を提供します。
 * このクラスは、テンプレートに変数を渡す際に利用する {@link Delta_Renderer::setAttribute()} メソッドや {@link Delta_StringUtils::escape()} といった関数から内部的にコールされて利用されます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.decorator
 */

abstract class Delta_HTMLEscapeDecorator extends Delta_Object
{
  /**
   * @var mixed
   */
  protected $_data;

  /**
   * HTML エスケープされていない生のデータを返します。
   *
   * @return array HTML エスケープされていない生のデータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRaw()
  {
    return $this->_data;
  }
}
