<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package sanitizer
 * @since 2.0
 */

class Delta_SanitizerInvoker extends Delta_Object
{
  private $_sanitizerConfig;

  public function __construct()
  {
    $this->_sanitizerConfig = Delta_Config::getBehavior()->get('sanitizer');

    if (!$this->_sanitizerConfig) {
      // @todo 2.0
    }
  }

  public function invoke(Delta_DataField $dataField)
  {
    foreach ($dataField->getSanitizers() as $sanitizerId => $attributes) {
      $sanitizerConfig = $this->_sanitizerConfig->get($sanitizerId);

      if ($sanitizerConfig) {
        $sanitizerClassName = $sanitizerConfig->get('class');

        if (!$sanitizerClassName) {
          $message = sprintf('Sanitizer class is undefined. [%s.class]', $sanitizerId);
          throw new Delta_ConfigurationException($message);
        }

        $sanitizer = new $sanitizerClassName($dataField->getFieldValue(), $attributes);
        $dataField->setFieldValue($sanitizer->sanitize());

      } else {
        $message = sprintf('Sanitizer is undefined. [%s]', $sanitizerId);
        throw new Delta_ConfigurationException($message);
      }
    }
  }
}
