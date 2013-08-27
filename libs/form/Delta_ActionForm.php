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
 * リクエストデータを元に生成されるフォームオブジェクトです。
 * フォームオブジェクトはフォームから送信された情報を取得したり、フィールドに値を割り当てることができます。
 * 実際にフォームを生成する際は、フォームデータを格納した {@link Delta_FormHelper} を利用して下さい。
 *
 * このクラスは 'form' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_WebApplication::getForm()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package form
 */

class Delta_ActionForm extends Delta_Object
{
  /**
   * {@link Delta_ParameterHolder} オブジェクト。
   * @var Delta_ParameterHolder
   */
  private $_holder;

  /**
   * コンストラクタ。
   * リクエストデータをフォームオブジェクトに設定します。
   * オブジェクトに設定されるパラメータはリクエストメソッドの形式により異なります。
   *   - GET リクエスト時: GET パラメータが設定される
   *   - POST リクエスト時: POST パラメータが設定される
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {
    $request = Delta_FrontController::getInstance()->getRequest();

    if ($request->getRequestMethod() == Delta_HttpRequest::HTTP_GET) {
      $array = $request->getQuery();
    } else {
      $array = $request->getPost();
    }

    $this->_holder = new Delta_ParameterHolder($array);
  }

  /**
   * フォームのインスタンスオブジェクトを取得します。
   *
   * @return Delta_ActionForm フォームのインスタンスオブジェクトを返します。
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_ActionForm();
    }

    return $instance;
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
