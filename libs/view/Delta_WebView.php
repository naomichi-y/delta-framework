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
  private $_forms = array();

  public function addForm(Delta_Form $form)
  {
    $formName = $form->getFormName();
    $this->_forms[$formName] = $form;
  }

  public function getForm($formName)
  {
    $form = NULL;

    if (isset($this->_forms[$formName])) {
      $form = $this->_forms[$formName];
    }

    return $form;
  }

  public function getForms()
  {
    return $this->_forms;
  }
}
