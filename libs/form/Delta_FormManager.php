<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package form
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package form
 * @since 2.0
 */

class Delta_FormManager extends Delta_Object
{
  public static function getInstance($formName)
  {
    static $instances = array();

    $formClassName = $formName . 'Form';

    if (!isset($instances[$formClassName])) {
      $instance = new $formClassName;
      $instances[$formName] = $instance;
    }

    return $instances[$formName];
  }
}
