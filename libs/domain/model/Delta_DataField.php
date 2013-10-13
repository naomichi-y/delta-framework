<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package domain.model
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package domain.model
 * @since 2.0
 */

class Delta_DataField extends Delta_Object
{
  private $_fieldName;
  private $_label;
  private $_fieldValue;
  private $_validators = array();
  private $_sanitizers = array();

  public function __construct($fieldName, $label = NULL)
  {
    $this->_fieldName = $fieldName;
    $this->_label = $label;
  }

  public function getFieldName()
  {
    return $this->_fieldName;
  }

  public function getLabel()
  {
    return $this->_label;
  }
  public function setFieldValue($fieldValue)
  {
    $this->_fieldValue = $fieldValue;
  }

  public function getFieldValue()
  {
    return $this->_fieldValue;
  }

  public function addValidator($validatorId, array $conditions = array())
  {
    $this->_validators[$validatorId] = new Delta_ParameterHolder($conditions);
  }

  public function hasValidator($validatorId)
  {
    return isset($this->_validators[$validatorId]);
  }

  public function getValidator($validatorId)
  {
    $validator = NULL;

    if (isset($this->_validators[$validatorId])) {
      $validator = $this->_validators[$validatorId];
    }

    return $validator;
  }

  public function getValidators()
  {
    return $this->_validators;
  }

  public function addSanitizer($sanitizerId, array $conditions = array())
  {
    $this->_sanitizers[$sanitizerId] = new Delta_ParameterHolder($conditions);
  }

  public function hasSanitizer($sanitizerId)
  {
    return isset($this->_sanitizers[$sanitizerId]);
  }

  public function getSanitizer($sanitizerId)
  {
    $sanitizer = NULL;

    if (isset($this->_sanitizers[$sanitizerId])) {
      $sanitizer = $this->_sanitizers[$sanitizerId];
    }

    return $sanitizer;
  }

  public function getSanitizers()
  {
    return $this->_sanitizers;
  }
}
