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
  private $_viewBasePath;

  public function setForm($bindName, Delta_Form $form)
  {
    $config = Delta_Config::getHelpers()->getArray('form');
    $this->_helpers[$bindName] = new Delta_FormHelper($form, $this, $config);
  }

  public function setViewBasePath($viewBasePath)
  {
    $this->_viewBasePath = $viewBasePath;
  }

  public function getViewBasePath()
  {
    return $this->_viewBasePath;
  }

  public function execute()
  {
    $extension = Delta_Config::getApplication()->getString('view.extension');
    $absolutePath = Delta_AppPathManager::buildAbsolutePath($this->_viewBasePath, $this->_viewPath, $extension);

    if ($absolutePath) {
      if (is_file($absolutePath)) {
        $this->_renderer->renderFile($absolutePath);

      } else {
        $message = sprintf('View path is not found. [%s]', $absolutePath);
        throw new Delta_ParseException($message);
      }

    } else {
      $this->_renderer->render($this->_source);
    }
  }
}
