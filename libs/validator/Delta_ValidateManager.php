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
 * behavior ファイルに定義された検証ルールに従って、順次検証を実行します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package validator
 */
class Delta_ValidateManager extends Delta_Object
{
  /**
   * @var Delta_ActionForm
   */
  private $_form;

  /**
   * @var Delta_ActionMessages
   */
  private $_messages;

  /**
   * @var Delta_ParameterHolder
   */
  private $_validateConfig;

  /**
   * @var string
   */
  private $_actionName;

  /**
   * コンストラクタ。
   *
   * @param Delta_ParameterHolder $validateConfig array バリデータ (behavior) 設定ファイル。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_ParameterHolder $validateConfig)
  {
    $container = Delta_DIContainerFactory::getContainer();

    $this->_form = $container->getComponent('form');
    $this->_messages = $container->getComponent('messages');

    $this->_validateConfig = $validateConfig;
    $this->_actionName = Delta_ActionStack::getInstance()->getLastEntry()->getActionName();
  }

  /**
   * バリデータのインスタンスを生成します。
   *
   * config/global_behavior.yml の設定例:
   * <code>
   * validate:
   *   validators:
   *     rangeValidator:
   *       class: Delta_RangeValidator
   *       min: '${MIN}'
   *       max: '${MAX}'
   *       matchError: '{%FIELD_NAME%}は{%MIN%}～{%MAX%}の間の値を指定して下さい。'
   * </code>
   *
   * バリデータインスタンスの生成:
   * <code>
   * $variables = array('MIN' => 3, 'MAX' => 10, 'FIELD_NAME' => '数値');
   * $validator = Delta_ValidateManager::createValidator('rangeValidator');
   *
   * // FALSE
   * $validator->validate('number', 12, $variables);
   *
   * // '数値は3〜10の間の値を指定して下さい。'
   * $this->getMessages()->getFieldError('number');
   * </code>
   *
   * @param string $validatorId バリデータ ID。
   * @return Delta_Validator バリデータオブジェクトを返します。
   * @throws InvalidArgumentException バリデータ ID がビヘイビアに未定義の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createValidator($validatorId)
  {
    $search = 'validate.validators.' . $validatorId;
    $config = Delta_Config::getBehavior()->get($search);

    if ($config) {
      $messages = Delta_DIContainerFactory::getContainer()->getComponent('messages');

      $className = $config->get('class');
      $validator = new $className($validatorId, $config, $messages);

      return $validator;
    }

    $message = sprintf('Validator ID does not exits. [%s]', $validatorId);
    throw new InvalidArgumentException($message);
  }

  /**
   * behavior ファイルに定義された検証ルールに従って検証を実行します。
   *
   * @return string エラーが無い場合は TRUE、エラーがあった場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute()
  {
    $result = TRUE;
    $validateConfig = $this->_validateConfig;

    // 検証項目がない場合は処理を終了させる
    if (isset($validateConfig['methods']) || isset($validateConfig['includes']) ||
        isset($validateConfig['names']) || isset($validateConfig['conditions'])) {

      // バリデータのインクルード
      if (isset($validateConfig['includes'])) {
        foreach ($validateConfig['includes'] as $include) {
          $config = Delta_Config::getBehavior($include, TRUE)->get('validate');

          if ($config) {
            $manager = new Delta_ValidateManager($config);
            $manager->execute();
          }
        }
      }

      // 検証メソッドのチェック
      $request = Delta_DIContainerFactory::getContainer()->getComponent('request');

      if (isset($validateConfig['methods']) && !stristr($validateConfig['methods'], $request->getRequestMethod())) {
        if (isset($validateConfig['methodsError'])) {
          $this->_messages->addError($validateConfig['methodsError']);
        } else {
          $this->_messages->addError('Request method is illegal.');
        }

        $result = FALSE;

      } else {
        if (isset($validateConfig['names'])) {
          $this->checkFields('names', $validateConfig['names']);
        }

        if (isset($validateConfig['conditions'])) {
          $this->checkConditions();
        }

        if ($this->_messages->hasError($this->_actionName) || $this->_messages->hasFieldError()) {
          $result = FALSE;
        }
      }
    }

    return $result;
  }

  /**
   * 'validate:names'、'validate:conditions:{condition}:groups:names' 属性のデータ検証を行います。
   * 'names' 属性では、値が入ってるかどうかのチェックの他に、'validators' 子属性に実行するバリデータを記述することで、様々なデータ検証が可能となります。
   * また、同一名のフォーム要素が複数存在する場合は、全ての項目をチェックします。
   *
   * @param string $parentId 親要素 ID。
   * @param array $names 検証対象とするフォーム要素セット。
   * @param bool $groups グルーピングバリデータを使用する場合は TRUE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkFields($parentId, array $names, $groups = FALSE)
  {
    if ($groups) {
      $rows = array();
      $rowId = 0;
      $keys = array_keys($names);

      foreach ($keys as $fieldName) {
        $values = $this->_form->get($fieldName);

        if (is_array($values)) {
          foreach ($values as $fieldKey => $fieldValue) {
            $rows[$rowId][$fieldName]['key'] = $fieldKey;
            $rows[$rowId][$fieldName]['value'] = $fieldValue;
            $rowId++;
          }

        } else {
          $files = Delta_FileUploader::getFileInfo($fieldName);
          $fileId = $rowId;

          if (sizeof($files)) {
            foreach (array_keys($files['name']) as $fieldKey) {
              $array = &$rows[$fileId][$fieldName];
              $array['key'] = $fieldKey;
              $array['value']['name'] = $files['name'][$fieldKey];
              $array['value']['tmp_name'] = $files['tmp_name'][$fieldKey];
              $array['value']['type'] = $files['type'][$fieldKey];
              $array['value']['size'] = $files['size'][$fieldKey];
              $array['value']['error'] = $files['error'][$fieldKey];

              $fileId++;
            }

          } else {
            $rows[$rowId][$fieldName]['key'] = NULL;
            $rows[$rowId][$fieldName]['value'] = NULL;
          }
        }

        $rowId = 0;
      }

      $search = 'conditions.' . $parentId . '.groups.requireRows';
      $requireRows = $this->_validateConfig->getInt($search, 1);
      $dataExistRows = 0;

      foreach ($rows as $rowId => $fieldNames) {
        $emptyRow = TRUE;

        foreach ($fieldNames as $fieldName => $attributes) {
          $fieldValue = $attributes['value'];

          if (is_string($fieldValue) && strlen($fieldValue) || is_array($fieldValue) && $fieldValue['size']) {
            $emptyRow = FALSE;
            break;
          }
        }

        // 空のグループ行は配列の最後に移動
        if ($emptyRow) {
          $row = $rows[$rowId];
          unset($rows[$rowId]);
          $rows[] = $row;

        } else {
          $dataExistRows++;
        }
      }

      reset($rows);
      $i = 0;

      if ($requireRows < $dataExistRows) {
        $j = $dataExistRows;
      } else {
        $j = $requireRows;
      }

      do {
        $current = current($rows);
        $i++;

        foreach ($current as $fieldName => $attributes) {
          $assocName = $fieldName . '.' . $attributes['key'];
          $fieldValue = $attributes['value'];

          $this->buildValidator($parentId, $names, $fieldName, $assocName, $fieldValue);
        }

      } while (next($rows) && $i < $j);

    } else {
      foreach (array_keys($names) as $fieldName) {
        $fieldValue = $this->_form->get($fieldName);

        if ($fieldValue === NULL && isset($_FILES[$fieldName])) {
          $fieldValue = $_FILES[$fieldName];
        }

        $this->buildValidator($parentId, $names, $fieldName, $fieldName, $fieldValue);
      }
    }
  }

  /**
   * @param string $parentId
   * @param array $names
   * @param string $fieldName
   * @param string assocName
   * @param string $fieldValue
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildValidator($parentId, array $names, $fieldName, $assocName, $fieldValue)
  {
    if (isset($names[$fieldName]['required'])) {
      $field = $names[$fieldName];
      $required = $field['required'];

      if ($required) {
        $className = 'Delta_RequiredValidator';

        $holder = new Delta_ParameterHolder();
        $holder->set('class', $className, FALSE);
        $holder->set('required', TRUE, FALSE);

        if (isset($field['requiredError'])) {
          $holder->set('requiredError', $field['requiredError']);
        }

        $validator = new Delta_RequiredValidator(NULL, $holder, $this->_messages);
        $validator->validate($assocName, $fieldValue);
      }
    }

    // names 要素に validators が存在する場合は該当するバリデータを起動
    if (isset($names[$fieldName]['validators'])) {
      $validators = explode(',', $names[$fieldName]['validators']);
      $this->checkValidators($parentId, $fieldName, $assocName, $fieldValue, $validators);
    }
  }

  /**
   * 'validate:names:validators' 属性、'validate:conditions:{condition}:groups:names:validators' 属性において定義されたバリデータを処理します。
   * また、同一名のフォーム要素が複数存在する場合は、全ての項目をチェックします。
   * 既にエラーが発生している項目に関しては、検証は実行されません。
   *
   * @param string $parentId 親要素 ID。
   * @param string $fieldName フォーム要素名。
   * @param string $assocName
   * @param string $fieldValue 検証する値。
   * @param array $validators 実行するバリデータ要素。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkValidators($parentId, $fieldName, $assocName, $fieldValue, array $validators)
  {
    if (sizeof($this->_form->get($fieldName)) == 1) {
      if ($this->_messages->hasFieldError($fieldName)) {
        return;
      }

    } else {
      if ($this->_messages->hasFieldError($fieldName)) {
        return;
      }
    }

    // プレースホルダ変数の取得
    $fieldAttributes = NULL;

    if ($parentId === 'names') {
      $fieldAttributes = $this->_validateConfig['names'][$fieldName];

    } else {
      $current = $this->_validateConfig['conditions'][$parentId];

      if (isset($current['names'][$fieldName])) {
        $fieldAttributes = $current['names'][$fieldName];
      } else if (isset($current['groups']['names'][$fieldName])) {
        $fieldAttributes = $current['groups']['names'][$fieldName];
      } else {
        $fieldAttributes = $current;
      }
    }

    $variables = Delta_ArrayUtils::find($fieldAttributes, 'variables', array());

    // 全ての validators 属性を実行
    foreach ($validators as $validatorId) {
      $validatorId = trim($validatorId);
      $holder = $this->_validateConfig->get('validators')->get($validatorId);

      if ($holder) {
        $className = $holder->getString('class');

        if ($className) {
          $validator = new $className($validatorId, $holder, $this->_messages);

          if (!$validator->validate($assocName, $fieldValue, $variables)) {
            break;
          }

          $holder->clear();

        } else {
          $message = sprintf('Validator class is undefined. [%s]', $validatorId);
          throw new Delta_ParseException($message);
        }

      } else {
        $message = sprintf('Validator rule is undefined. [%s]', $validatorId);
        throw new Delta_ParseException($message);
      }
    }
  }

  /**
   * validators 属性に定義された条件バリデータを処理します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkConditions()
  {
    $conditions = $this->_validateConfig->get('conditions', array());

    foreach ($conditions as $condition => $attributes) {
      if (isset($attributes['test'])) {
        $result = $this->parseStatement($attributes['test']);

        if ($result) {
          if (isset($attributes['names'])) {
            $this->checkFields($condition, $attributes['names']);

          } else if (isset($conditions[$condition]['groups'])) {
            $this->checkFields($condition, $attributes['groups']['names'], TRUE);

          } else if (isset($conditions[$condition]['validators'])) {
            $validators = $conditions[$condition]['validators'];

            if (!$this->_messages->hasFieldError($condition) && $validators) {
              $validators = explode(',', $validators);
              $this->checkValidators($condition, $condition, $condition, NULL, $validators);
            }
          }
        } else {
          $message = NULL;

          if (isset($attributes['testError'])) {
            $testError = $attributes['testError'];
            $this->_messages->addError($testError);
          }
        }
      } else if (isset($attributes['groups'])) {
        $this->checkFields($condition, $attributes['groups']['names'], TRUE);
      }
    }
  }

  /**
   * 'test' 属性のステートメントを解析します。
   *
   * @param string $statement 解析対象のステートメント。
   * @return bool ステートメントの解析結果を bool 値で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseStatement($statement)
  {
    $start = strpos($statement, '`');
    $end = strrpos($statement, '`');
    $test = substr($statement, $start + 1, $end - 1);

    $fields = $this->_form->getFields();
    $element = explode(' ', $test);
    $append = NULL;

    foreach ($element as $statementValue) {
      if (preg_match('/^(!)?(notEmpty:)?([\w\.\[\]]+)$/', $statementValue, $matches)) {
        $fieldValue = Delta_ArrayUtils::find($fields, $matches[3]);

        // 'notEmpty:' check
        if ($matches[2]) {
          $result = 0;

          if (strlen($fieldValue)) {
            $result = 1;
          }

          if ($matches[1]) {
            $result = !$result;
          }

          $append .= $result;

        } else {
          if (strlen($fieldValue) == 0) {
            $append .= '\'\'';
          } else {
            $append .= $matches[1] . '\'' . $fieldValue . '\'';
          }
        }

      } else {
        $append .= $statementValue;
      }
    }

    $code = sprintf('$result = (bool) (%s);', $append);
    eval($code);

    return $result;
  }
}
