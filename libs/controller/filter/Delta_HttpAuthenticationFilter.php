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
 * HTTP 認証を行うための抽象クラスです。
 * HTTP 認証は Apache モジュールとして実行した時のみ有効です。CGI 版では利用できません。
 *
 * @link http://www.ietf.org/rfc/rfc2617.txt HTTP Authentication: Basic and Digest Access Authentication
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.filter
 */
abstract class Delta_HttpAuthenticationFilter extends Delta_Filter
{
  /**
   * レルムを取得します。
   *
   * @return string レルムを返します。デフォルトではモジュール名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRealm()
  {
    return Delta_Router::getInstance()->getEntryModuleName();
  }

  /**
   * ログインプロンプトを表示します。
   *
   * @param Delta_FilterChain $chain フィルタチェインのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function showLoginPrompt(Delta_FilterChain $chain);

  /**
   * 認証成功時に実行する処理を実装します。
   * メソッドがオーバーライドされていない場合は、次のフィルタが実行されます。
   *
   * @param Delta_FilterChain $chain フィルタチェインのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function authenticateSuccess(Delta_FilterChain $chain)
  {
    $chain->filterChain();
  }

  /**
   * 認証失敗時に実行する処理を実装します。
   * メソッドがオーバーライドされていない場合は、{@link showLoginPrompt()} メソッドが実行されます。
   *
   * @param Delta_FilterChain $chain フィルタチェインのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function authenticateFailure(Delta_FilterChain $chain)
  {
    $this->showLoginPrompt($chain);
  }

  /**
   * 認証失敗時の処理を実装します。
   * メソッドがオーバーライドされていない場合、HTTP ステータスコード 401 (Unauthorized) を出力して処理を停止します。
   *
   * @param Delta_FilterChain $chain フィルタチェインのインスタンス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function authenticateCancel(Delta_FilterChain $chain)
  {
    $this->getResponse()->sendError(401);
    die();
  }
}
