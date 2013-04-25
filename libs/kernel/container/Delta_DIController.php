<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * フレームワークが提供する標準 DI コンポーネントにアクセスします。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.container
 */
abstract class Delta_DIController extends Delta_Object
{
  /**
   * コントローラ (controller) コンポーネントを取得します。
   *
   * @return Delta_FrontController Delta_FrontController を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getController()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('controller');
  }

  /**
   * リクエスト (request) コンポーネントを取得します。
   *
   * @return Delta_HttpRequest Delta_HttpRequest を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRequest()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('request');
  }

  /**
   * レスポンス (response) コンポーネントを取得します。
   *
   * @return Delta_HttpRequest Delta_HttpResponse を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getResponse()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('response');
  }

  /**
   * セッション (session) コンポーネントを取得します。
   *
   * @return Delta_HttpSession Delta_HttpSession を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSession()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('session');
  }

  /**
   * メッセージ (messages) コンポーネントを取得します。
   *
   * @return Delta_ActionMessages Delta_ActionMessages を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMessages()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('messages');
  }

  /**
   * フォーム (form) コンポーネントを取得します。
   *
   * @return Delta_ActionForm Delta_ActionForm を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getForm()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('form');
  }

  /**
   * ユーザ (user) コンポーネントを取得します。
   *
   * @return Delta_AuthorityUser Delta_AuthorityUser を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUser()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('user');
  }

  /**
   * データベース (database) コンポーネントを取得します。
   *
   * @return Delta_DatabaseManager Delta_DatabaseManager を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDatabase()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('database');
  }

  /**
   * ビュー (view) コンポーネントを取得します。
   *
   * @return Delta_View Delta_View を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getView()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('view');
  }

  /**
   * コンソール (console) コンポーネントを取得します。
   *
   * @return Delta_Console Delta_Console を実装したオブジェクトのインスタンスを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConsole()
  {
    return Delta_DIContainerFactory::getContainer()->getComponent('console');
  }
}