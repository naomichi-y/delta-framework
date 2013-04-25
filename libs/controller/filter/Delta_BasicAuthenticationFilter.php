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
 * アプリケーションに Basic 認証機能を提供する抽象クラスです。
 * アプリケーション上で認証機能を有効にするには、Delta_BasicAuthentication を実装したクラスを作成する必要があります。
 * <i>Basic 認証はセキュリティの観点から使用が推奨されません。
 * 可能な限り Digest 認証 ({@link Delta_DigestAuthenticationFilter}) を利用して下さい。</i>
 *
 * @link http://www.ietf.org/rfc/rfc2617.txt HTTP Authentication: Basic and Digest Access Authentication
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
abstract class Delta_BasicAuthenticationFilter extends Delta_HttpAuthenticationFilter
{
  /**
   * ログインプロンプトを表示すると共に、クライアントへ HTTP ステータス 401 (Unauthorized) を返します。
   *
   * @see Delta_HttpAuthenticationFilter::showLoginPrompt
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function showLoginPrompt(Delta_FilterChain $chain)
  {
    $this->getResponse()->setHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $this->getRealm()));
    $this->authenticateCancel($chain);
  }

  /**
   * ユーザ認証を行います。
   *
   * @param string $username ログインユーザ ID。
   * @param string $password ログインパスワード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function authenticate($username, $password);

  /**
   * Basic 認証を行います。
   *
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $request = $this->getRequest();

    $username = NULL;
    $password = NULL;

    if ($request->hasHeader('PHP_AUTH_USER')) {
      $username = $request->getEnvironment('PHP_AUTH_USER');
      $password = $request->getEnvironment('PHP_AUTH_PW');

    // Apache 以外のサーバ対策
    } else if ($request->hasHeader('HTTP_AUTHENTICATION')) {
      if (strpos(strtolower($request->getEnvironment('HTTP_AUTHENTICATION')), 'basic') === 0) {
        list($username, $password) = explode(':', base64_decode(substr($request->getEnvironment('HTTP_AUTHORIZATION'), 6)));
      }

    } else {
      $this->showLoginPrompt($chain);
    }

    if ($username !== NULL) {
      if ($this->authenticate($username, $password)) {
        $this->authenticateSuccess($chain);
      } else {
        $this->authenticateFailure($chain);
      }
    }
  }
}
