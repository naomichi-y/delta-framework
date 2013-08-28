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
   * @since 2.0
   */
  public function getFormName()
  {
    return Delta_StringUtils::convertCamelCase(substr(get_class($this), 0, -4));
  }

  public function addFieldError($name, $value)
  {
    $this->_fieldErrors[$name] = $value;
  }

  public function hasFieldError()
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
    $user->setAttribute('tokenId', $tokenId);

    $this->set('tokenId', $tokenId);
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
   * フィールドに値を設定します。
   *
   * @param string $name 対象フィールド名。'.' (ピリオド) を含む名前は連想配列名として扱われる。
   * @param mixed $value フィールドに設定する値。
   * @param bool $override フォームオブジェクトに同じ値が登録されている場合、値を上書きするかどうか。
   * @return bool 値の設定に成功した場合は TRUE、失敗した (override が FALSE かつ同名の値が設定されている) 場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value, $override = TRUE)
  {
    $result = FALSE;

    if ($override || !$this->hasName($name)) {
      $this->_holder->set($name, $value);
      $result = TRUE;
    }

    return $result;
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
}
