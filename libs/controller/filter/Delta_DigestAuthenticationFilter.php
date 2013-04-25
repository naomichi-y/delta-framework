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
 * アプリケーションに Digest 認証機能を提供する抽象クラスです。
 * アプリケーション上で認証機能を有効にするには、Delta_DigestAuthenticationFilter を実装したクラスを作成する必要があります。
 * <i>Digest 認証は古いブラウザでは正しく動作しない可能性があります。</i>
 *
 * @link http://www.ietf.org/rfc/rfc2617.txt HTTP Authentication: Basic and Digest Access Authentication
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
abstract class Delta_DigestAuthenticationFilter extends Delta_HttpAuthenticationFilter
{
  /**
   * クライアントから送られてきた Digest 認証ヘッダを取得します。
   *
   * @return string Digest 認証ヘッダを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getDigest()
  {
    $request = Delta_DIContainerFactory::getContainer()->getComponent('request');
    $digest = NULL;

    if ($request->hasHeader('PHP_AUTH_DIGEST')) {
      $digest = $request->getEnvironment('PHP_AUTH_DIGEST');

    // Apache 以外のサーバ対策
    } else if ($request->hasHeader('HTTP_AUTHENTICATION')) {
      if (strpos(strtolower($request->getEnvironment('HTTP_AUTHENTICATION')), 'digest') === 0) {
        $digest = substr($request->getEnvironment('HTTP_AUTHORIZATION'), 7);
      }
    }

    return $digest;
  }

  /**
   * ログインプロンプトを表示すると共に、クライアントへ HTTP ステータス 401 (Unauthorized) を返します。
   * この時同時に、Digest 認証に必要となるレルムやランダム文字列も返します。
   *
   * @see Delta_HttpAuthenticationFilter::showLoginPrompt
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function showLoginPrompt(Delta_FilterChain $chain)
  {
    $realm = $this->getRealm();

    mt_srand();

    $value = sprintf('Digest realm="%s", qop="auth", nonce="%s", opaque="%s"',
      $realm,
      $tokenId = md5(uniqid(mt_rand(), TRUE)),
      md5($realm));

    $this->getResponse()->setHeader('WWW-Authenticate', $value);
    $this->authenticateCancel($chain);
  }

  /**
   * クライアントから送られてきた Digest 認証の文字列を解析します。
   *
   * @param string $digest Digest 認証ヘッダ。
   * @return array Digest 認証ヘッダをパラメータごとに分割した連想配列を返します。
   *   - username: 認証ダイアログで入力したユーザ名。
   *   - realm: サーバから受け取ったレルム名。
   *   - nonce: サーバが生成したランダム文字列。
   *   - uri: リクエスト URI。
   *   - response: パスワードを含む Digest 文字列。
   *   - opaque: サーバが生成したセッション値。
   *   - qop: サーバから受け取った qop (quality of protection) のうち、一つだけ選択して返します。
   *   - nc: nonce-count。
   *   - cnonce: クライアントが生成したランダム値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseDigest($digest)
  {
    $parts = array(
      'nonce' => 1,
      'nc' => 1,
      'cnonce' => 1,
      'qop' => 1,
      'username' => 1,
      'uri' => 1,
      'response' => 1
    );
    $array = array();

    preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
      if ($m[2]) {
        $data[$m[1]] = $m[2];
      } else {
        $data[$m[1]] = $m[3];
      }

      unset($parts[$m[1]]);
    }

    if ($parts) {
      return FALSE;
    }

    return $data;
  }

  /**
   * 要求されたユーザのパスワードを取得します。
   *
   * @param string $username ログインユーザ ID。
   * @return string ユーザのログインパスワードを返します。ユーザが存在しない場合は FALSE を返して下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getPassword($username);

  /**
   * Digest 認証を行います。
   *
   * @see Delta_Filter::doFilter()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function doFilter(Delta_FilterChain $chain)
  {
    $digest = $this->getDigest();

    if ($digest === NULL) {
      $this->showLoginPrompt($chain);

    } else {
      $parts = $this->parseDigest($digest);
      $password = $this->getPassword($parts['username']);

      if ($password) {
        $request = $this->getRequest();

        $a1 = md5(sprintf('%s:%s:%s', $parts['username'], $this->getRealm(), $password));
        $a2 = md5(sprintf('%s:%s', $request->getEnvironment('REQUEST_METHOD'), $parts['uri']));

        $valid = md5(sprintf('%s:%s:%s:%s:%s:%s',
          $a1,
          $parts['nonce'],
          $parts['nc'],
          $parts['cnonce'],
          $parts['qop'],
          $a2));

        if (strcmp($parts['response'], $valid) == 0) {
          $this->authenticateSuccess($chain);

        } else {
          $this->authenticateFailure($chain);
        }

      } else {
        $this->showLoginPrompt($chain);
      }
    }
  }
}
