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
 * {@link Delta_AuthorityUser::isLogin() ユーザのログイン状態} をチェックし、ログインが必要なアクションに未ログイン状態でアクセスした場合は指定したアクションへ強制フォワードします。
 *
 * modules/{module_name}/config/filters.yml の設定例:
 * <code>
 * # フィルタクラス名。
 * class: Delta_LoginFilter
 *
 * # 未ログイン時に遷移するアクション名。
 * forward:
 * </code>
 *
 * ログインを必要とするアクションのビヘイビア設定例:
 * <code>
 * login: TRUE
 * </code>
 *
 * @since 1.1
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
class Delta_LoginFilter extends Delta_Filter
{
  /**
   * @throws Delta_SecurityException ログインエラー時にフォワードするアクションが未指定の場合に発生。
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_HttpRequest $request, Delta_HttpResponse $response, Delta_FilterChain $chain)
  {
    $login = Delta_Config::getBehavior()->getBoolean('login');
    $result = FALSE;

    if ($login) {
      if ($request->getSession()->getUser()->isLogin()) {
        $result = TRUE;
      }

    } else {
      $result = TRUE;
    }

    if ($result) {
      $chain->filterChain();

    } else {
      $forward = $this->_holder->get('forward');

      if ($forward !== NULL) {
        $this->getController()->forward($forward);

      } else {
        $message = 'Forward destination of non-login is not specified.';
        throw new Delta_SecurityException($message);
      }
    }
  }
}
