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
 * フィルタの順序集合を保持し、global_filters.yml、filters.yml に定義された順序でフィルタを実行します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
class Delta_FilterChain extends Delta_Object
{
  /**
   * @var array
   */
  private $_filters;

  /**
   * コンストラクタ。
   *
   * @param array $filters 実行するフィルタのリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $filters)
  {
    $this->_filters = $filters;
  }

  /**
   * Delta_FilterManager に登録されているフィルタを順次実行します。
   * Delta_FilterChain は、プリフィルタ実行後、対象となるアクションを実行し、最後にポストフィルタを実行します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function filterChain()
  {
    $execute = FALSE;
    $filterId = key($this->_filters);

    if ($filterId === NULL) {
      return;
    }

    $attributes = $this->_filters[$filterId];
    next($this->_filters);

    $forward = Delta_ArrayUtils::find($attributes, 'forward', FALSE);
    $route = Delta_FrontController::getInstance()->getRequest()->getRoute();
    $forwardStack = $route->getForwardStack();

    if (!$forward && $forwardStack->getSize() > 1) {
      end($this->_filters);
      $filterId = key($this->_filters);
      $attributes = $this->_filters[$filterId];

      next($this->_filters);
    }

    if (isset($attributes['packages'])) {
      $packageName = $forwardStack->getLast()->getPackageName();
      $execute = Delta_Action::isValidPackage($packageName, $attributes['packages']);

    } else {
      $execute = TRUE;
    }

    if ($execute) {
      $holder = new Delta_ParameterHolder($attributes, TRUE);
      $className = $attributes['class'];

      $filter = new $className($filterId, $holder);
      $filter->doFilter($this);

    } else {
      $this->filterChain();
    }
  }
}
