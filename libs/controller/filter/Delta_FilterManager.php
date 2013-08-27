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
 * グローバルフィルタ、及びモジュールフィルタを管理し、適切な {@link Delta_FilterChain} 構造を生成します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */

class Delta_FilterManager extends Delta_Object
{
  /**
   * フィルタリスト。
   * @var array
   */
  private $_filters = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $config = Delta_Config::getFilters();

    foreach ($config as $filterName => $attributes) {
      $this->addFilter($filterName, $attributes->toArray());
    }
  }

  /**
   * フィルタマネージャにフィルタを登録します。
   *
   * @param string $filterId フィルタ ID。
   * @param array $attributes フィルタ属性。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addFilter($filterId, array $attributes = array())
  {
    if (Delta_ArrayUtils::find($attributes, 'enable', TRUE)) {
      $this->_filters[$filterId] = $attributes;
    }
  }

  /**
   * 指定したフィルタ名が登録されているかチェックします。
   *
   * @param string $filterName チェックするフィルタ名。
   * @return bool フィルタが登録済みかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasFilter($filterName)
  {
    return isset($this->_filters[$filterName]);
  }

  /**
   * Delta_FilterManager に登録されているグローバルフィルタ、モジュールフィルタから {@link Delta_FilterChain} を生成し、フィルタの処理を実行します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilters()
  {
    $filterChain = new Delta_FilterChain($this->_filters);
    $filterChain->filterChain();
  }
}
