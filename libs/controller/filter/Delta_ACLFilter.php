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
 * {@link Delta_AuthorityUser::getRoles() ユーザロール} がビヘイビアに定義されたロールを満たしていない (1 つ以上のロールにマッチしない) 場合に {@link Delta_SecurityException} をスローします。
 *
 * @since 1.1
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
class Delta_ACLFilter extends Delta_Filter
{
  /**
   * @throws Delta_SecurityException ユーザロールが不足している場合に発生。
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $user = Delta_FrontController::getInstance()->getRequest()->getSession()->getUser();

    if ($user->isCurrentActionAuthenticated(Delta_AuthorityUser::REQUIRED_ONE_ROLE)) {
      $chain->filterChain();

    } else {
      // @todo 2.0 getBehavior()は廃止
      $roles = Delta_Config::getBehavior()->get('roles')->toArray();
      $message = sprintf('User roll is not enough. [%s]', implode(',', $roles));

      throw new Delta_SecurityException($message);
    }
  }
}
