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
  private $_controllerName;

  /**
   * Delta_ActionController
   */
  private $_controller;

  /**
   * @var string
   */
  private $_actionName;

  /**
   * コンストラクタ。
   *
   * @param string $moduleName モジュール名。
   * @param string $controllerName コントローラ名。
   * @param string $actionName アクション名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($moduleName, $controllerName, $actionName)
  {
    $this->_moduleName = $moduleName;
    $this->_controllerName = $controllerName;
    $this->_actionName = $actionName;

    $controllerClassName = $controllerName . 'Controller';
    $this->_controller = new $controllerClassName;
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
   * コントローラ名を取得します。
   *
   * @return string コントローラ名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getControllerName()
  {
    return $this->_controllerName;
  }

  /**
   * @since 2.0
   */
  public function getController()
  {
    return $this->_controller;
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
}

