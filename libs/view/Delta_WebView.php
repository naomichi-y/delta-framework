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
  public function setForm($bindName, Delta_Form $form)
  {
    $config = Delta_Config::getHelpers()->getArray('form');
    $this->_helpers[$bindName] = new Delta_FormHelper($form, $this, $config);
  }
}
