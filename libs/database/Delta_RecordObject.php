<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースから取得した結果セットに含まれるレコードを表します。
 * レコードは {@link Delta_DatabaseResultSet::read()} オブジェクト等から取得可能です。
 * Delta_RecordObject は ArrayAccess インタフェースを実装しているため、配列形式でフィールドにアクセスすることもできます。
 *
 * <code>
 * $conn = $this->getDatabase()->getConnection();
 * $query = 'SELECT manager_id, manager_name FROM managers';
 * $stmt = $conn->createStatement($query);
 * $resultSet = $stmt->executeQuery();
 *
 * $recordObject = $resultSet->read();
 * $managerId = $recordObject->manager_id;
 *
 * // 配列形式で値を取得
 * $managerId = $recordObject['manager_id'];
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 */

class Delta_RecordObject extends Delta_Object implements ArrayAccess
{
  /**
   * フィールド名配列。
   * @var array
   */
  private $_fieldNames = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {}

  /**
   * レコードオブジェクトにフィールドデータを設定します。
   *
   * @param string $name フィールド名。
   * @param string $value フィールドが持つ値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __set($name, $value)
  {
    $this->_fieldNames[] = $name;
    $this->$name = $value;
  }

  /**
   * フィールドの値を取得します。
   *
   * @param string $name 取得対象のフィールド名。
   *   アンダースコア形式 ($record->field_name)、または camelCaps ($record->fieldName) で取得することができます。
   * @return string フィールドが持つ値を返します。
   * @throws RuntimeException 存在しないフィールドにアクセスした場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __get($name)
  {
    $data = FALSE;

    // アンダースコア形式によるアクセス
    if (property_exists($this, $name)) {
      $data = $this->$name;

    } else {
      $message = sprintf('Field that does not exist. [%s::$%s]', get_class($this), $name);
      throw new RuntimeException($message);
    }

    return $data;
  }

  /**
   * @see ArrayAccess::offsetExists()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetExists($name)
  {
    $result = FALSE;

    if (property_exists($this, $name)) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * @see ArrayAccess::offsetGet()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetGet($name)
  {
    $result = NULL;

    if (property_exists($this, $name)) {
      $result = $this->$name;
    }

    return $result;
  }

  /**
   * @see ArrayAccess::offsetSet()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetSet($name, $value)
  {
    $this->$name = $value;
  }

  /**
   * @see ArrayAccess::offsetUnset()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function offsetUnset($name)
  {
    unset($this->$name);
  }

  /**
   * レコードが持つ全てのフィールド名を配列形式で取得します。
   *
   * @return array レコードが持つ全てのフィールド名を配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNames()
  {
    return $this->_fieldNames;
  }

  /**
   * レコードが持つ全てのフィールド値を配列形式で取得します。
   *
   * @return array レコードが持つ全てのフィールド値を配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getValues()
  {
    $values = array();

    foreach ($this->getNames() as $name) {
      $values[] = $this->$name;
    }

    return $values;
  }

  /**
   * 指定したフィールドインデックスに対応する値を取得します。
   *
   * @param int $index 0 から始まるフィールドインデックス値。
   * @return string フィールドインデックスに対応する値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getByIndex($index)
  {
    $value = FALSE;

    if (isset($this->_fieldNames[$index])) {
      $fieldName = $this->_fieldNames[$index];
      $value = $this->$fieldName;
    }

    return $value;
  }

  /**
   * 指定したフィールド名に対応する値を取得します。
   *
   * @param string $name フィールド名。
   * @return string フィールド名に対応する値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getByName($name)
  {
    $value = FALSE;

    if (property_exists($this, $name)) {
      $value = $this->$name;
    }

    return $value;
  }

  /**
   * レコードオブジェクトが持つ全てのフィールドデータを配列形式に変換します。
   *
   * @return array レコードオブジェクトが持つ全てのフィールドデータを配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function toArray()
  {
    $array = array();

    foreach ($this->getNames() as $name) {
      $array[$name] = $this->$name;
    }

    return $array;
  }

  /**
   * レコードオブジェクトが持つフィールドデータを元にエンティティクラスを生成します。
   *
   * @param string $entityName 変換するエンティティ名。'MembersEntity' クラスに変換する場合は 'Entity' を指定。
   * @return Delta_Entity フィールドデータを保持するエンティティオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function toEntity($entityName)
  {
    $entityClassName = $entityName . 'Entity';

    $entity = new $entityClassName;
    $bindings = $this->toArray();
    $entity->bindFields($bindings);

    return $entity;
  }
}
