<?php
/**
 * @package libs.filter
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */

class RoleAuthenticationFilter extends Delta_Filter
{
  public function doFilter(Delta_FilterChain $chain)
  {
    // 認証済みであればアクションを実行
    if ($this->getUser()->isCurrentActionAuthenticated()) {
      $chain->filterChain();

    } else {
      $this->getMessages()->addError('ログインを行って下さい。');
      $this->getController()->forward('LoginForm');
    }
  }
}
