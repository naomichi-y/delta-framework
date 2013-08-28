<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package form
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package form
 * @since 2.0
 */

class Delta_Form extends Delta_Object
{
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

  const TOKEN_FIELD_NAME = '_tokenId';
  const TOKEN_SESSION_KEY = '_tokenId';

  /**
   * @var Delta_ParameterHolder
   */
  private $_holder;

  /**
   * @var array
   */
  private $_fieldErrors = array();

  public function __construct($bindRequest = TRUE)
  {
    if ($bindRequest) {
      $this->bindRequest();
    } else {
      $this->_holder = new Delta_ParameterHolder();
    }
  }

  /**
   * フィールドに値を設定します。
   *
   * @param string $name 対象フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $value フィールドに設定する値。
   * @param bool $override フォームオブジェクトに同じ値が登録されている場合、値を上書きするかどうか。
   * @return bool 値の設定に成功した場合は TRUE、失敗した (override が FALSE かつ同名の値が設定されている) 場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value, $override = FALSE)
  {
    $result = FALSE;

    if ($override || !$this->hasName($name)) {
      $this->_holder->set($name, $value);
      $result = TRUE;
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
      foreach ($fields as $name => $value) {
        $this->set($name, $value, $override);
      }
    }
  }

  /**
   * 対象フィールドに値が格納されているかチェックします。
   *
   * @param string $name チェックするフィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasName($name)
  {
    return $this->_holder->hasName($name);
  }

  /**
   * フォームオブジェクトに設定されたフィールド数を取得します。
   *
   * @return int フォームオブジェクトに設定されたフィールド数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSize()
  {
    return $this->_holder->count();
  }

  /**
   * フィールドに設定されている値を取得します。
   *
   * @param string $name 対象フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $alternative 値が見つからない (NULL)、または空文字の場合に返す代替値。
   * @return mixed name に対応するフィールド値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $alternative = NULL)
  {
    return $this->_holder->get($name, $alternative);
  }

  /**
   * フォームオブジェクトに設定されているフィールドデータを取得します。
   *
   * @return array フィールド名と値で構成される連想配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFields()
  {
    return $this->_holder->toArray();
  }

  /**
   * フォームオブジェクトに設定されているフィールドデータを削除します。
   *
   * @param string $name 削除対象のフィールド名。
   * @return bool 削除が成功した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function remove($name)
  {
    return $this->_holder->remove($name);
  }

  /**
   * フォームオブジェクトに設定されているフィールドデータを破棄します。
   *
   * @param string $name 破棄対象のフィールド名。未指定の場合は全てのフィールドを破棄します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_holder->clear();
  }

  public function addFieldError($name, $value)
  {
    $this->_fieldErrors[$name] = $value;
  }

  public function hasFieldError($name)
  {
    $result = FALSE;

    if (isset($this->_fieldErrors[$name])) {
      $result = TRUE;
    }

    return $result;
  }

  public function hasErrors()
  {
    $result = FALSE;

    if (sizeof($this->_fieldErrors)) {
      $result = TRUE;
    }

    return $result;
  }

  public function getFieldError($name)
  {
    $fieldError = NULL;

    if (isset($this->_fieldErrors[$name])) {
      $fieldError = $this->_fieldErrors[$name];
    }

    return $fieldError;
  }

  public function getFieldErrors()
  {
    return $this->_fieldErrors;
  }

  public function removeFieldError($name)
  {
    $result = FALSE;

    if (isset($this->_fieldErrors[$name])) {
      unset($this->_fieldErrors[$name]);
      $result = TRUE;
    }

    return $result;
  }

  public function clearFieldErrors()
  {
    $this->_fieldErrors = array();
  }

  public function bindRequest()
  {
    $request = Delta_FrontController::getInstance()->getRequest();

    if ($request->getRequestMethod() == Delta_HttpRequest::HTTP_GET) {
      $data = $request->getQuery();
    } else {
      $data = $request->getPost();
    }

    $this->_holder = new Delta_ParameterHolder($data);

    return $this;
  }

  public function bindEntity(Delta_Entity $entity, $bindName = NULL)
  {
    if ($bindName === NULL) {
      $bindName = $entity->getEntityName();
    }

    $fields = $entity->toArray();

    foreach ($fields as $name => $value) {
      $name = $bindName . '.' . $name;
      $this->_holder->set($name, $value);
    }
  }

  public function getEntity($entityName)
  {
    $array = $this->get($entityName);
    $entity = NULL;

    if (is_array($array)) {
      $entityClassName = Delta_StringUtils::convertPascalCase($entityName) . 'Entity';

      if (class_exists($entityClassName)) {
        $entity = new $entityClassName($array);
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

    $this->set(self::TOKEN_FIELD_NAME, $tokenId);
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

  public function validate()
  {
    $isValid = FALSE;
    $user = Delta_FrontController::getInstance()->getRequest()->getSession()->getUser();

    if ($user->hasAttribute(self::TOKEN_SESSION_KEY)) {
      $tokenState = $this->getTokenState();

      if ($tokenState !== self::TOKEN_VALID) {
        $this->addFieldError(self::TOKEN_FIELD_NAME, $this->getTokenStateErrorMessage($tokenState));
        $isValid = TRUE;
      }
    }

    return $isValid;
  }
}
