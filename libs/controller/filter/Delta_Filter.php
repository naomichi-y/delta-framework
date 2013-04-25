<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * フィルタの動作を定義する抽象クラスです。
 *
 * global_filters.yml の設定例:
 * <code>
 * {フィルタ ID}
 *   # フィルタクラス名。
 *   class:
 *
 *   # フィルタを起動するかどうか。
 *   enable: TRUE
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */

abstract class Delta_Filter extends Delta_DIController
{
  /**
   * @var string
   */
  protected $_filterId;

  /**
   * @var Delta_ParameterHolder
   */
  protected $_holder;

  /**
   * コンストラクタ。
   *
   * @param string $filterId フィルタ ID。
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($filterId, Delta_ParameterHolder $holder)
  {
    $this->_filterId = $filterId;
    $this->_holder = $holder;
  }

  /**
   * フィルタの動作を実装します。
   *
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function doFilter(Delta_FilterChain $chain);
}
