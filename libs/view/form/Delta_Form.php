<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.form
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.form
 * @since 2.0
 */

class Delta_Form extends Delta_Object
{
  /**
   * @since 2.0
   */
  const METHOD_GET = 'get';

  /**
   * @since 2.0
   */
  const METHOD_POST = 'post';

  /**
   * トランザクショントークンが正常な状態を表す定数。
   */
  const TOKEN_VALID = 1;

  /**
   * トランザクショントークンが異常な状態 (二重コミット、不正なアクセス等) を表す定数。
   */
  const TOKEN_INVALID = -1;

  /**
   * トランザクショントークンが存在しない状態を表す定数。
   */
  const TOKEN_WRONG = -2;

  /**
   * @since 2.0
   */
  const TOKEN_FIELD_NAME = '_tokenId';

  /**
   * @since 2.0
   */
  const TOKEN_SESSION_KEY = '_tokenId';

  /**
   * @var string
   */
  private $_method = self::METHOD_POST;

  /**
   * @var Delta_DataFieldBuilder
   */
  private $_builder;

  /**
   * @var array
   */
  private $_errors = array();

  public function __construct()
  {
    $this->_builder = new Delta_DataFieldBuilder();

    $this->build($this->_builder);
    $this->bindRequest();
  }

  public function setMethod($method)
  {
    $this->_method = $method;
  }

  public function getMethod()
  {
    return $this->_method;
  }

  public function getDataFieldBuilder()
  {
    return $this->_builder;
  }

  /**
   * フィールドに値を設定します。
   *
   * @param string $fieldName 対象フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $fieldValue フィールドに設定する値。
   * @param bool $override フォームオブジェクトに同じ値が登録されている場合、値を上書きするかどうか。
   * @return bool 値の設定に成功した場合は TRUE、失敗した (override が FALSE かつ同名の値が設定されている) 場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($fieldName, $fieldValue, $override = FALSE)
  {
    $result = FALSE;

    if ($override || !$this->hasName($fieldName)) {
      $dataField = $this->_builder->get($fieldName);

      if ($dataField) {
        $dataField->setValue($fieldValue);
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * フィールド名と値で構成される連想配列をフォームフィールドとして設定します。
   *
   * @param array $fields フィールド名と値で構成される連想配列データ。
   * @param bool $override フォームオブジェクトに同じ値が登録されている場合、値を上書きするかどうか。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFields(array $fields, $override = TRUE)
  {
    if (is_array($fields)) {
      foreach ($fields as $fieldName => $fieldValue) {
        $this->set($fieldName, $fieldValue, $override);
      }
    }
  }

  /**
   * 対象フィールドに値が格納されているかチェックします。
   *
   * @param string $fieldName チェックするフィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasName($fieldName)
  {
    if ($this->_builder->hasName($fieldName)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * フィールドに設定されている値を取得します。
   *
   * @param string $fieldName 対象フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $alternative 値が見つからない (NULL)、または空文字の場合に返す代替値。
   * @return mixed name に対応するフィールド値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($fieldName, $alternative = NULL)
  {
    $value = NULL;
    $dataField = $this->_builder->get($fieldName);

    if ($dataField) {
      $value = $dataField->getValue();

      if (Delta_StringUtils::nullOrEmpty($value)) {
        $value = $alternative;
      }

    } else {
      $value = $alternative;
    }

    return $value;
  }

  /**
   * フォームオブジェクトに設定されているフィールドデータを取得します。
   *
   * @return array フィールド名と値で構成される連想配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFields()
  {
    return $this->_builder->getFields();
  }

  /**
   * フォームオブジェクトに設定されているフィールドデータを削除します。
   *
   * @param string $fieldName 削除対象のフィールド名。
   * @return bool 削除が成功した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function remove($fieldName)
  {
    $result = FALSE;
    $dataField = $this->_builder->get($fieldName);

    if ($dataField) {
      $dataField->setValue(NULL);
      $result = TRUE;
    }

    return $result;
  }

  /**
   */
  public function clear()
  {
    foreach ($this->_builder->getFields() as $fieldName => $dataField) {
      $dataField->setValue(NULL);
    }

    $this->_errors = array();
  }

  public function addError($fieldName, $fieldValue)
  {
    $this->_errors[$fieldName] = $fieldValue;
  }

  public function hasError($fieldName)
  {
    $result = FALSE;

    if (isset($this->_errors[$fieldName])) {
      $result = TRUE;
    }

    return $result;
  }

  public function hasErrors()
  {
    $result = FALSE;

    if (sizeof($this->_errors)) {
      $result = TRUE;
    }

    return $result;
  }

  public function getError($fieldName)
  {
    $fieldError = NULL;

    if (isset($this->_errors[$fieldName])) {
      $fieldError = $this->_errors[$fieldName];
    }

    return $fieldError;
  }

  public function getErrors()
  {
    return $this->_errors;
  }

  public function removeError($fieldName)
  {
    $result = FALSE;

    if (isset($this->_errors[$fieldName])) {
      unset($this->_errors[$fieldName]);
      $result = TRUE;
    }

    return $result;
  }

  public function clearErrors()
  {
    $this->_errors = array();
  }

  public function bindRequest()
  {
    $request = Delta_FrontController::getInstance()->getRequest();
    $fields = $this->_builder->getFields();

    if ($request->getMethod() == Delta_HttpRequest::HTTP_GET) {
      foreach ($fields as $fieldName => $dataField) {
        $dataField->setValue($request->getQuery($fieldName));
      }

    } else {
      foreach ($fields as $fieldName => $dataField) {
        $dataField->setValue($request->getPost($fieldName));
      }
    }

    return $this;
  }

  /**
  public function bindEntity(Delta_Entity $entity, $override = FALSE)
  {
    $bindName = $entity->getEntityName();

    $builder = new Delta_DataFieldBuilder($bindName);
    $entity->build($builder);

    foreach ($entity->toArray() as $fieldName => $fieldValue) {
      // エンティティが持つ全てのフィールドをフォームにセット
      $fieldName = $bindName . '.' . $fieldName;
      $this->_fields->set($fieldName, $fieldValue, $override);

      foreach ($builder->getFields() as $field) {
        $this->_builder->addField($field);
      }
    }
  }
   */

  public function getEntity($entityName)
  {
    $array = $this->get(lcfirst($entityName));
    $entity = NULL;

    if (is_array($array)) {
      $entityClassName = Delta_StringUtils::convertPascalCase($entityName) . 'Entity';
      $existsClass = FALSE;

      try {
        Delta_ClassLoader::loadByName($entityClassName);
        $existsClass = TRUE;
      } catch (Exception $e) {}

      if ($existsClass) {
        $entity = new $entityClassName();

        foreach ($array as $fieldName => $fieldValue) {
          if (property_exists($entity, $fieldName)) {
            $entity->$fieldName = $fieldValue;
          }
        }
      }
    }

    return $entity;
  }

  /**
   * フォームにおいて二重送信を防止するためのトランザクショントークン ID を発行します。
   * 発行されたトークン ID は {@link Delta_FormHelper::close()} メソッドをコールすることにより、自動的に hidden タグとしてフォーム内に埋め込まれます。
   *
   * @param string $tokenId 任意のトークン ID。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function saveToken($tokenId = NULL)
  {
    if ($tokenId === NULL) {
      $secretKey = Delta_Config::getApplication()->getString('secretKey');
      $seed = $secretKey . microtime(TRUE);
      $tokenId = md5($seed);
    }

    $user = Delta_FrontController::getInstance()->getRequest()->getSession()->getUser();
    $user->setAttribute(self::TOKEN_SESSION_KEY, $tokenId);

    $this->set(self::TOKEN_FIELD_NAME, $tokenId, TRUE);
  }

  /**
   * トランザクショントークンの状態を取得します。
   *
   * @param bool $tokenState TRUE を指定した場合、状態を取得した後にトークンを破棄します。
   * @return int Delta_Form::TOKEN_* 定数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTokenState($resetToken = FALSE)
  {
    $request = Delta_FrontController::getInstance()->getRequest();
    $storeTokenId = $request->getSession()->getUser()->getAttribute(self::TOKEN_SESSION_KEY);
    $formTokenId = $request->getParameter(self::TOKEN_FIELD_NAME);

    if ($formTokenId === NULL && $storeTokenId === NULL) {
      $tokenState = self::TOKEN_WRONG;

    } else if (strcmp($formTokenId, $storeTokenId) == 0) {
      $tokenState = self::TOKEN_VALID;

    } else {
      $tokenState = self::TOKEN_INVALID;
    }

    if ($resetToken) {
      $this->resetToken();
    }

    return $tokenState;
  }

  /**
   * ユーザが所有しているトークン ID を破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resetToken()
  {
    $request = Delta_FrontController::getInstance()->getRequest();
    $request->getSession()->getUser()->removeAttribute(self::TOKEN_SESSION_KEY);
  }

  public function getTokenStateErrorMessage($tokenState)
  {
    return 'Token is invalid.';
  }

  public function build(Delta_DataFieldBuilder $builder)
  {
  }

  public function validate($checkToken = FALSE)
  {
    $result = TRUE;

    if ($checkToken) {
      $tokenState = $this->getTokenState(TRUE);

      if ($tokenState !== self::TOKEN_VALID) {
        $this->addError(self::TOKEN_FIELD_NAME, $this->getTokenStateErrorMessage($tokenState));
        $result = FALSE;
      }
    }

    $invoker = function(Delta_DataField $dataField) use (&$result) {
      $validatorInvoker = new Delta_ValidatorInvoker();
      $validatorInvoker->invoke($dataField);

      if ($validatorInvoker->hasErrors()) {
        foreach ($validatorInvoker->getErrors() as $fieldError) {
          $this->addError($dataField->getFieldName(), $fieldError);
        }

        $result = FALSE;
      }
    };

    if ($result) {
      // 入力フィールドの検証
      foreach ($this->_builder->getFields() as $fieldName => $dataField) {
        if ($fieldName !== self::TOKEN_FIELD_NAME) {
          /**
          // この行を消す場合はEntity::validate()周りも変更?
            $isEntity = FALSE;

          if (is_array($attributes)) {
            $entity = $this->getEntity($fieldName);

            if ($entity && !$entity->validate()) {
              $isEntity = TRUE;

              foreach ($entity->getErrors() as $fieldName => $fieldError) {
                $fieldName = $entity->getEntityName() . '.' . $fieldName;
                $this->addError($fieldName, $fieldError);
              }

              $result = FALSE;
            }
          }
          */

          $invoker($dataField);

        } // end if
      } // end foreach
    }

    return $result;
  }
}
