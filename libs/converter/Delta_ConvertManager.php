<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * フォームから送信された内容に対し、ビヘイビアで設定したコンバートルールを適用するマネージャ機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package converter
 */
class Delta_ConvertManager extends Delta_Object
{
  /**
   * コンバート属性配列。
   * (global_behavior.yml、アクションビヘイビアの 'convert' 属性)
   * @var array
   */
  private $_convertConfig;

  /**
   * コンストラクタ。
   *
   * @param Delta_ParameterHolder $convertConfig コンバータ属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_ParameterHolder $convertConfig)
  {
    $this->_convertConfig = $convertConfig;
  }

  /**
   * ビヘイビアに定義されているコンバータを適用します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute()
  {
    $convertConfig = $this->_convertConfig;
    $form = Delta_DIContainerFactory::getContainer()->getComponent('form');

    foreach ($convertConfig as $converterId => $rules) {
      $fieldNames = NULL;

      if (isset($rules['names'])) {
        if ($rules['names'] === '@all') {
          $form = Delta_DIContainerFactory::getContainer()->getComponent('form');
          $fieldNames = array_keys($form->getFields());
        } else {
          $fieldNames = explode(',', $rules['names']);
        }
      }

      foreach ($rules['converters'] as $index => $attributes) {
        $holder = new Delta_ParameterHolder($attributes);
        $className = $holder->getString('class');

        if (Delta_StringUtils::nullOrEmpty($className)) {
          $message = sprintf('"class" attribute is undefined. [%s]', $converterId);
          throw new Delta_ConfigurationException($message);
        }

        $converter = new $className($converterId, $holder);

        if ($fieldNames === NULL) {
          $converter->convert(NULL);

        } else {
          foreach ($fieldNames as $fieldName) {
            $fieldName = trim($fieldName);
            $fieldValue = $form->get($fieldName);

            if (is_array($fieldValue)) {
              $this->groupFieldToConvert($converter, $fieldValue, $fieldName);

            } else {
              $postValue = $converter->convert($fieldValue);
              $form->set($fieldName, $postValue);
            }
          }
        }
      }
    }
  }

  /**
   * @param Delta_Converter $converter
   * @param string $fieldValue
   * @param string $postFieldName
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function groupFieldToConvert(Delta_Converter $converter, $fieldValue, $postFieldName = NULL)
  {
    $form = Delta_DIContainerFactory::getContainer()->getComponent('form');

    foreach ($fieldValue as $name => $value) {
      $name = $postFieldName . '.' . $name;

      if (is_array($value)) {
        $this->groupFieldToConvert($converter, $value, $name);

      } else {
        $postValue = $converter->convert($value);
        $form->set($name, $postValue);
      }
    }
  }
}
