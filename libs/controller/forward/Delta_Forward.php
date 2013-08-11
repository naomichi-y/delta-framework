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
 *
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller.forward
 */

class Delta_Forward extends Delta_Object
{
  private $_moduleName;
  private $_actionName;
  private $_action;

  public function __construct($moduleName, $actionName)
  {
    $this->_moduleName = $moduleName;
    $this->_actionName = $actionName;
  }

  public function getModuleName()
  {
    return $this->_moduleName;
  }

  public function getActionName()
  {
    return $this->_actionName;
  }

  public function setAction(Delta_Action $action)
  {
    $this->_action = $action;
  }

  public function getAction()
  {
    return $this->_action;
  }
}

