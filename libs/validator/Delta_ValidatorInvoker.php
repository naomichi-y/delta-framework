<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 * @since 2.0
 */

class Delta_ValidatorInvoker extends Delta_Object
{
  private $_validatorsConfig;
  private $_errors;

  public function __construct()
  {
    $this->_validatorsConfig = Delta_Config::getBehavior()->get('validators');

    if (!$this->_validatorsConfig) {
      // @todo 2.0
    }
  }

  public function invoke(Delta_DataField $dataField)
  {
    $result = TRUE;

    foreach ($dataField->getValidators() as $validatorId => $attributes) {
      $validatorConfig = $this->_validatorsConfig->get($validatorId);

      if ($validatorConfig) {
        $validatorClassName = $validatorConfig->get('class');

        if (!$validatorClassName) {
          $message = sprintf('Validator class is undefined. [%s.class]', $validatorId);
          throw new Delta_ConfigurationException($message);
        }

        if (!$attributes->hasName('label')) {
          $attributes->set('label', $dataField->getLabel());
        }

        $fieldName = $dataField->getFieldName();
        $validator = new $validatorClassName($fieldName, $dataField->getFieldValue(), $attributes);

        if (!isset($this->_errors[$fieldName]) && !$validator->validate()) {
          $this->_errors[$fieldName] = $validator->getError();
          $result = FALSE;
        }

      } else {
        $message = sprintf('Validator is undefined. [%s]', $validatorId);
        throw new Delta_ConfigurationException($message);
      }
    }

    return $result;
  }

  public function hasErrors()
  {
    if (sizeof($this->_errors)) {
      return TRUE;
    }

    return FALSE;
  }

  public function getErrors()
  {
    return $this->_errors;
  }
}
