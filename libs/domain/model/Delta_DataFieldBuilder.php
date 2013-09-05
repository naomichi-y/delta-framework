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

class Delta_DataFieldBuilder extends Delta_Object
{
  private $_baseName;
  private $_fields = array();

  public function __construct($baseName = NULL)
  {
    $this->_baseName = $baseName;
  }

  public function createDataField($fieldName, $label)
  {
    if ($this->_baseName === NULL) {
      $fieldName = $fieldName;
    } else {
      $fieldName = $this->_baseName . '.' . $fieldName;
    }

    return new Delta_DataField($fieldName, $label);
  }

  public function add($fieldName, $label)
  {
    if ($this->_baseName === NULL) {
      $fieldName = $fieldName;
    } else {
      $fieldName = $this->_baseName . '.' . $fieldName;
    }

    $field = new Delta_DataField($fieldName, $label);
    $this->_fields[$fieldName] = $field;
  }

  public function addField(Delta_DataField $dataField)
  {
    $this->_fields[$dataField->getFieldName()] = $dataField;
  }

  public function get($fieldName)
  {
    $result = NULL;

    if (isset($this->_fields[$fieldName])) {
      $result = $this->_fields[$fieldName];
    }

    return $result;
  }

  public function getFields()
  {
    return $this->_fields;
  }
}
