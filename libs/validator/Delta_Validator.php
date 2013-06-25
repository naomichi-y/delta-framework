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
   * @var Delta_ParameterHolder
   */
  protected $_holder;

  /**
   * @var Delta_ActionMessages
   */
  protected $_messages;

  /**
   * コンストラクタ。
   *
   * @param string $validatorId バリデータ ID。
   * @param Delta_ParameterHolder $holder パラメータホルダ。
   * @param Delta_ActionMessages $messages エラーメッセージを追加するメッセージオブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($validatorId, Delta_ParameterHolder $holder, Delta_ActionMessages $messages)
  {
    $this->_validatorId = $validatorId;
    $this->_holder = $holder;
    $this->_messages = $messages;
  }

  /**
   * バリデータ属性を構築します。
   *
   * @param array $variables バリデータに割り当てる変数のリスト。
   * @return Delta_ParameterHolder パラメータホルダオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function buildParameterHolder(array $variables = array())
  {
    $holder = new Delta_ParameterHolder();

    foreach ($this->_holder as $attributeName => $attributeValue) {
      if ($attributeName == 'class') {
        $holder->set($attributeName, $attributeValue);

      } else {
        if (!is_array($attributeValue)) {
          // 文字列の展開
          if (preg_match('/{%\w+%}/', $attributeValue)) {
            foreach ($variables as $variableName => $variableValue) {
              if (!is_array($variableValue)) {
                $replace = '{%' . $variableName . '%}';
                $attributeValue = str_replace($replace, $variableValue, $attributeValue);
              }
            }

          // 変数の展開
          } else if (preg_match('/^\${(\w+)}$/', $attributeValue, $matches)) {
            if (isset($variables[$matches[1]])) {
              $attributeValue = $variables[$matches[1]];
            } else {
              $attributeValue = NULL;
            }
          }
        }

        if ($attributeValue !== NULL) {
          $holder->set($attributeName, $attributeValue);
        }

      } // end if
    } // end foreach

    return $holder;
  }

  /**
   * {@link validate()} メソッドで発生したエラーを {@link Delta_ActionMessages メッセージオブジェクト} に送信します。
   *
   * @param string $fieldName エラーを含むフィールド名。
   * @param string $message エラーメッセージ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function sendError($fieldName, $message)
  {
    $this->_messages->addFieldError($fieldName, $message);
  }

  /**
   * データの検証を行います。
   * フィールドにエラーが含まれる場合、エラーメッセージは {@link Delta_ActionMessages メッセージオブジェクト} に送信されます。
   *
   * @param string $fieldName 検証するフィールド名。
   * @param string $value 検証するフィールドの値。
   * @param array $variables 差し込み変数のリスト。
   * @return bool データの検証に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function validate($fieldName, $value, array $variables = array());
}
