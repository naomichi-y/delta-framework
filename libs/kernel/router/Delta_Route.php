<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 *
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 */

class Delta_Route extends Delta_Object
{
  private $_routeName;
  private $_bindings = array();
  private $_moduleName;
  private $_actionName;
  private $_forwardStack;

  public function __construct($routeName, $bindings = array())
  {
    $this->_routeName = $routeName;
    $this->_moduleName = $bindings['module'];
    $this->_actionName = $bindings['action'];
    $this->_bindings = $bindings;
    $this->_forwardStack = new Delta_ForwardStack();
  }

  public function getRouteName()
  {
    return $this->_routeName;
  }

  public function getModuleName()
  {
    return $this->_moduleName;
  }

  public function getActionName()
  {
    return $this->_actionName;
  }

  public function getBindings()
  {
    return $this->_bindings;
  }

  public function getForwardStack()
  {
    return $this->_forwardStack;
  }
}
