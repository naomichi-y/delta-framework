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
 * データを検証するためのメソッドを定義する抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
abstract class Delta_Validator extends Delta_Object
{
  /**
   * @var string
   */
  protected $_validatorId;

  /**
   * @var string
   */
  protected $_fieldName;

  /**
   * @var string
   */
  protected $_fieldValue;

  /**
   * @var array
   */
  protected $_conditions = array();

  /**
   * @var string
   */
  protected $_error;

  /**
   * コンストラクタ。
   *
   * @param string $fieldName 検証するフィールド名。
   * @param string $fieldValue フィールドが持つ値。
   * @param Delta_ParameterHolder $conditions 検証条件。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($fieldName, $fieldValue, Delta_ParameterHolder $conditions)
  {
    $this->_fieldName = $fieldName;
    $this->_fieldValue = $fieldValue;
    $this->_conditions = $conditions;
  }

  /**
   * @since 2.0
   */
  abstract public function validate();

  /**
   * @since 2.0
   */
  protected function buildError(Delta_ParameterHolder $conditions)
  {
    $config = Delta_Config::getBehavior()->toArray();
    $error = NULL;

    if (isset($config['validators'][$this->_validatorId]['error'])) {
      $error = $config['validators'][$this->_validatorId]['error'];

      $error = preg_replace_callback('/{\$[\w_]+}/',
        function($matches) use ($error, $conditions) {
          $key = substr($matches[0], 2, -1);

          if (isset($conditions[$key])) {
            $value = $conditions[$key];
          } else {
            $value = $matches[0];
          }

          return $value;
        },
        $error
      );

    } else {
      // @todo 2.0
    }

    return $error;
  }

  /**
   * @since 2.0
   */
  public function getError()
  {
    return $this->_error;
  }
}
