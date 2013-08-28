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

class Delta_ForwardStack extends Delta_Object
{
  private $_forwardStack = array();

  public function add(Delta_Forward $forward)
  {
    if (sizeof($this->_forwardStack) > 8) {
      $buffer = NULL;
      $i = 0;

      foreach ($this->_forwardStack as $forward) {
        if ($i < 4) {
          $buffer .= sprintf('%sController::%sAction(), ', $forward->getControllerName(), $forward->getActionName());
        }

        $i++;
      }

      $buffer = rtrim($buffer, ', ');

      $message = sprintf('Forward too many. [%s...]', $buffer);
      throw new Delta_Exception($message);
    }

    $this->_forwardStack[] = $forward;
  }

  public function getSize()
  {
    return sizeof($this->_forwardStack);
  }

  public function getPrevious()
  {
    $result = FALSE;
    $size = $this->getSize();

    if ($size > 2) {
      $result = $this->_forwardStack[$size - 2];
    }

    return $result;
  }

  public function getLast()
  {
    $size = sizeof($this->_forwardStack);

    if ($size == 0) {
      throw new RuntimeException('Forward stack is empty.');
    }

    return $this->_forwardStack[$size - 1];
  }

  public function getStack()
  {
    return $this->_forwardStack;
  }
}

