<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.forward
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * アクションのフォワード情報を管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.forward
 * @since 1.2
 */

class Delta_Forward extends Delta_Object
{
  /**
   * @var string
   */
  private $_moduleName;

  /**
   * @var string
   */
  private $_actionName;

  /**
   * @var Delta_Action
   */
  private $_action;

  /**
   * コンストラクタ。
   *
   * @param string $moduleName モジュール名。
   * @param string $actionName アクション名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($moduleName, $actionName)
  {
    $this->_moduleName = $moduleName;
    $this->_actionName = $actionName;
  }

  /**
   * モジュール名を取得します。
   *
   * @return string モジュール名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getModuleName()
  {
    return $this->_moduleName;
  }

  /**
   * アクション名を取得します。
   *
   * @return string アクション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getActionName()
  {
    return $this->_actionName;
  }

  /**
   * アクションオブジェクトを設定します。
   *
   * @param Delta_Action アクションオブジェクトを設定します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAction(Delta_Action $action)
  {
    $this->_action = $action;
  }

  /**
   * アクションオブジェクトを取得します。
   *
   * @return Delta_Action アクションオブジェクトを取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAction()
  {
    return $this->_action;
  }
}

