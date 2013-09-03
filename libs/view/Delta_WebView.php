<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view
 * @since 2.0
 */

class Delta_WebView extends Delta_View
{
  public function bindForm($formName = NULL)
  {
    static $instance;

    if ($formName === NULL) {
      $formClassName = 'Delta_Form';
    } else {
      $formClassName = $formName . 'Form';
    }

    if ($instance === NULL) {
      $instance = new $formClassName;
    }

    return $instance;
  }
}
