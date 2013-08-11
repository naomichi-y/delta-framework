<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * Web アプリケーションの基底クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @since 1.2
 */
abstract class Delta_WebApplication extends Delta_Object
{
  private $_controller;

  public function __construct()
  {
    $this->_controller = Delta_FrontController::getInstance();
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
    return $this->_controller->getRequest();
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
    return $this->_controller->getRequest()->getSession();
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
    return $this->_controller->getRequest()->getSession()->getUser();
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
    return $this->_controller->getResponse();
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
    return $this->_controller->getResponse()->getView();
  }

  /**
   * メッセージオブジェクトを取得します。
   *
   * @return Delta_ActionMessages メッセージオブジェクトを返します。
   * @see Delta_DIContainer::getComponent()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMessages()
  {
    return $this->_controller->getResponse()->getMessages();
  }

  /**
   * フォームオブジェクトを取得します。
   *
   * @return Delta_ActionForm フォームオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getForm()
  {
    return Delta_ActionForm::getInstance();
  }

  /**
   * データベースマネージャを取得します。
   *
   * @return Delta_DatabaseManager データベースマネージャを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDatabase()
  {
    return Delta_DatabaseManager::getInstance();
  }
}
