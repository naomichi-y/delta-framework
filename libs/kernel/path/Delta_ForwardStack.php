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

class Delta_ForwardStack extends Delta_Object
{
  private $_forwardStack = array();

  public function add(Delta_Forward $forward)
  {
    if (sizeof($this->_forwardStack) > 8) {
      $message = 'Forward too many.';
      throw new OverflowException($message);
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

