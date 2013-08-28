<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.entity
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ドメインモデルのデータを表現するオブジェクトです。
 *
 * <code>
 * $entity = new GreetingEntity();
 * $entity->message = 'Hello World!';
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.entity
 */

abstract class Delta_Entity extends Delta_Object
{
  /**
   * @since string
   */
  protected $_entityName;

  /**
   * コンストラクタ。
   *
   * @param array $fields {@link bindFields()} の項を参照。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $fields = array())
  {
    if (sizeof($fields)) {
      $this->bindFields($fields);
    }
  }

  /**
   * セッターマジックメソッド。
   *
   * @param string $name フィールド名。
   * @param string $value フィールドに割り当てる値。
   * @throws RuntimeException 存在しないフィールドへアクセスした場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __set($name, $value)
  {
    if (property_exists($this, $name)) {
      $this->$name = $value;

    } else {
      $this->throwUndefinedException($name);
    }
  }

  /**
   * ゲッターマジックメソッド。
   *
   * @param string $name フィールド名。
   * @throws RuntimeException 存在しないフィールドを参照された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __get($name)
  {
    if (property_exists($this, $name)) {
      return $this->$name;

    } else {
      $this->throwUndefinedException($name);
    }
  }

  /**
   * @param string $fieldName
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function throwUndefinedException($fieldName)
  {
    $message = sprintf('Field does not exist in entity. [%s::$%s]',
      get_class($this),
      $fieldName);
    throw new RuntimeException($message);
  }

  /**
   * @since 2.0
   */
  public function getEntityName()
  {
    return $this->_entityName;
  }

  /**
   * エンティティのフィールドにデータを割り当てます。
   *
   * @param array $fields エンティティに割り当てるデータ。
   *   array({field_name} => {field_value}) の形式で指定可能。
   * @throws RuntimeException 存在しないフィールドへアクセスした場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bindFields(array $fields)
  {
    foreach ($fields as $name => $value) {
      $this->$name = $value;
    }
  }

  /**
   * エンティティデータを配列に変換します。
   *
   * @return array エンティティデータを含む配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function toArray()
  {
    $class = new ReflectionClass($this);
    $fields = $class->getProperties();
    $array = array();

    foreach ($fields as $field) {
      if ($field->isPublic()) {
        $fieldName = $field->getName();
        $assocName = $fieldName;

        $array[$assocName] = $this->$fieldName;
      }
    }

    return $array;
  }

  /**
   * データフィールド名の一覧を取得します。
   *
   * @return array データフィールド名の一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDataFieldNames()
  {
    $class = new ReflectionClass($this);
    $fields = $class->getProperties();

    $array = array();

    foreach ($fields as $field) {
      if ($field->isPublic()) {
        $array[] = $field->getName();
      }
    }

    return $array;
  }
}
