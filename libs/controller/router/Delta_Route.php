<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.router
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ルート情報を管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.router
 * @since 1.2
 */

class Delta_Route extends Delta_Object
{
  /**
   * @var array
   */
  private $_pathHolder = array();

  /**
   * @var Delta_ForwardStack
   */
  private $_forwardStack;

  /**
   * コンストラクタ。
   *
   * @param array $pathHolder ルートを構築するパスホルダ情報。
   *   - route: ルート名
   *   - module: モジュール名
   *   - controller: コントローラ名
   *   - action: アクション名
   *   その他、リクエスト URI に含むパスホルダパラメータも格納する。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($pathHolder = array())
  {
    $this->_pathHolder = $pathHolder;
    $this->_forwardStack = new Delta_ForwardStack();
  }

  /**
   * ルート名を取得します。
   *
   * @return string ルート名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRouteName()
  {
    return $this->_pathHolder['route'];
  }

  /**
   * モジュール名を取得します。
   *
   * @return string ルート名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getModuleName()
  {
    return $this->_pathHolder['module'];
  }

  /**
   * コントローラ名を取得します。
   *
   * @return string コントローラ名を返します。
   * @since 2.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getControllerName()
  {
    return $this->_pathHolder['controller'];
  }

  /**
   * アクション名を取得します。
   *
   * @return string アクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getActionName()
  {
    return $this->_pathHolder['action'];
  }

  /**
   * パスホルダに含まれる全てのパラメータを取得します。
   *
   * @return array パスホルダに含まれる全てのパラメータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPathHolder()
  {
    return $this->_pathHolder;
  }

  /**
   * フォワードスタックオブジェクトを取得します。
   *
   * @return Delta_ForwardStack フォワードスタックオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getForwardStack()
  {
    return $this->_forwardStack;
  }
}
