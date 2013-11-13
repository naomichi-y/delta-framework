<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.dao
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースや O/R マッパと連携した機能を提供する DAO の抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.dao
 */

abstract class Delta_DAO extends Delta_Object
{
  /**
   * 名前空間。
   */
  protected $_dataSourceId = Delta_DatabaseManager::DEFAULT_DATASOURCE_ID;

  /**
   * テーブル名。
   * @var string
   */
  protected $_tableName;

  /**
   * プライマリキー。
   * @var array
   */
  protected $_primaryKeys = array();

  /**
   * database コンポーネントからコネクションオブジェクトを取得します。
   * このメソッドは可変引数を受け取ることができます。
   * 全ての引数は {@link Delta_DatabaseManager::getConnection()} メソッドに渡されます。
   *
   * @param string $dataSource 未指定の場合、{@link getDataSource()} で返されるデータソースが参照される。
   * @see Delta_DatabaseManager::getConnection()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnection($dataSource = NULL)
  {
    $database = Delta_DatabaseManager::getInstance();

    if ($dataSource === NULL) {
      return $database->getConnection($this->getDataSourceId());
    }

    return $database->getConnection($dataSource);
  }

  /**
   * 空のエンティティを生成します。
   *
   * @param array $properties {@link Delta_Entity::bindFields()} の項を参照。
   * @return Delta_Entity {@link Delta_Entity} を実装したエンティティオブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createEntity(array $properties = array())
  {
    $entityName = substr(get_class($this), 0, -3) . 'Entity';
    $entity = new $entityName($properties);

    return $entity;
  }

  /**
   * フォームのフィールド名とマッチするカラム名がエンティティに定義されている場合、フィールド値をエンティティにセットした状態でオブジェクトを生成します。
   *
   * @return Delta_Entity {@link Delta_Entity} を実装したエンティティオブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function formToEntity()
  {
    $form = Delta_ActionForm::getInstance();
    $fields = $form->getFields();
    $entity = $this->createEntity();
    $class = new ReflectionClass($entity);

    foreach ($fields as $name => $value) {
      $name = Delta_StringUtils::convertCamelCase($name);

      // 公開プロパティを持ってる場合、フィールドの値をセットする
      if ($class->hasProperty($name)) {
        $entity->$name = $value;
      }
    }

    return $entity;
  }

  /**
   * DAO が参照するデータソース ID を設定します。
   * 未指定の場合は 'default' (application.yml に定義された 'database.default') のデータベースを参照します。
   *
   * @param string $dataSourceId DAO が参照するデータソース ID。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @since 1.1
   */
  public function setDataSourceId($dataSourceId)
  {
    $this->_dataSourceId = $dataSourceId;
  }

  /**
   * DAO が参照するデータソース ID を取得します。
   *
   * @return string DAO が参照するデータソース ID を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDataSourceId()
  {
    return $this->_dataSourceId;
  }

  /**
   * @see Delta_DatabaseCommand::getTableName()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTableName()
  {
    if ($this->_tableName !== NULL) {
      $tableName = $this->_tableName;
    } else {
      $tableName = Delta_StringUtils::convertSnakeCase(substr(get_class($this), 0, -3));
    }

    return $tableName;
  }

  /**
   * @see Delta_DatabaseCommand::getPrimaryKeys()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPrimaryKeys()
  {
    return $this->_primaryKeys;
  }

  /**
   * データベースから取得したレコード配列を元にエンティティオブジェクトを生成します。
   * <strong>このメソッドは近い将来破棄されます。代替メソッド {@link Delta_RecordObject::toEntity()} を使用して下さい。</strong>
   *
   * @param mixed $array カラム名をキーとしてレコード値を格納した連想配列、または {@link Delta_RecordObject} クラスのインスタンス。
   * @return Delta_Entity {@link Delta_Entity} を実装したエンティティオブジェクトのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @deprecated 将来的に破棄予定。
   */
  public function arrayToEntity($array)
  {
    if (is_array($array)) {
      $entity = $this->createEntity();

      $array = Delta_ArrayUtils::convertKeyNames($array, Delta_ArrayUtils::CONVERT_TYPE_CAMELCAPS);
      $class = new ReflectionClass($entity);

      foreach ($array as $name => $value) {
        if ($class->hasProperty($name)) {
          $entity->$name = $value;
        }
      }

    } else {
      $entityName = Delta_StringUtils::convertPascalCase($this->getTableName());
      $entity = $array->toEntity($entityName);
    }

    return $entity;
  }

  /**
   * {@link Delta_DatabaseCriteria クライテリア} で利用するスコープを定義します。
   *
   * @param Delta_DatabaseCriteriaScopes $scopes スコープオブジェクト。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function scopes(Delta_DatabaseCriteriaScopes $scopes)
  {}

  /**
   * クライテリアオブジェクトを生成します。
   *
   * @return Delta_DatabaseCriteria クライテリアオブジェクトを返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createCriteria()
  {
    $scopes = new Delta_DatabaseCriteriaScopes();
    $this->scopes($scopes);

    return new Delta_DatabaseCriteria($this->_dataSourceId, $this->_tableName, $this->_primaryKeys, $scopes);
  }

  /**
   * レコードを挿入します。
   *
   * @param Delta_Entity $entity データベースに登録するエンティティ。
   * @param string シーケンスオブジェクト名。詳しくは {@link PDO::lastInsertId()} メソッドを参照。
   * @return int 最後に挿入されたレコードの ID を返します。
   *   詳しくは {@link PDO::lastInsertId()} を参照して下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function insert(Delta_DatabaseEntity $entity, $name = NULL)
  {
    return $this->getConnection()->getCommand()->insert($this->getTableName(), $entity->toArray(), $name);
  }

  /**
   * レコードを更新します。
   * 更新条件には {@link Delta_DAO::getPrimaryKeys() テーブルのプライマリキー} (AND) が使用されます。
   * <code>
   * $usersDAO = Delta_DAOFactory::create('Users');
   * $entity = $usersDAO->createEntity();
   * $entity->userId = 100;
   * $entity->username = 'foo'
   * $entity->lastLoginDate = new Delta_DatabaseExpression('NOW()');
   *
   * // "UPDATE user SET username = 'foo', last_login_date = NOW() WHERE user_id = :user_id"
   * $usersDAO->update($entity);
   * </code>
   *
   * @param Delta_DatabaseEntity $entity 更新対象のエンティティオブジェクト。
   * @return int 作用したレコード数を返します。
   * @throws RuntimeException プライマリキーの値が未指定の場合に発生。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function update(Delta_DatabaseEntity $entity)
  {
    $tableName = $this->getTableName();
    $fields = $entity->toArray();

    $data = array();
    $where = array();

    foreach ($fields as $name => $value) {
      if (in_array($name, $this->_primaryKeys)) {
        if ($value === NULL) {
          $message = sprintf('Primary key value is not specified. [%s::$%s]',
            get_class($this->createEntity()),
            Delta_StringUtils::convertCamelCase($name));
          throw new RuntimeException($message);
        }

        $where[$name] = $value;

      } else if ($value !== NULL) {
        $data[$name] = $value;
      }
    }

    return $this->getConnection()->getCommand()->update($tableName, $data, $where);
  }

  /**
   * レコードを削除します。
   *
   * @param mixed $primaryKeyValue 削除対象とするプライマリキーの値を指定。
   *   プライマリキーが複数フィールドで構成される場合は配列形式で値を指定。
   * @return int 作用したレコード数を返します。
   * @throws RuntimeException プライマリキーの値が未指定の場合に発生。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function delete($primaryKeyValue)
  {
    $valueSize = sizeof($primaryKeyValue);
    $primaryKeySize = sizeof($this->_primaryKeys);
    $hasError = FALSE;

    if ($primaryKeySize == 0) {
      $message = sprintf('Primary key is undefined. [%s::$_primaryKeys]', get_class($this));
      throw new RuntimeException($message);
    }

    $where = array();

    if (is_array($primaryKeyValue)) {
      if ($valueSize != $primaryKeySize) {
        $hasError = TRUE;
      }

      for ($i = 0; $i < $primaryKeySize; $i++) {
        $where[$this->_primaryKeys[$i]] = $primaryKeyValue[$i];
      }

    } else {
      if ($primaryKeySize > 1) {
        $hasError = TRUE;
      }

      $where[$this->_primaryKeys[0]] = $primaryKeyValue;
    }

    if ($hasError) {
      $message = 'Does not match the number of primary key and values.';
      throw new InvalidArgumentException($message);
    }

    return $this->getConnection()->getCommand()->delete($this->getTableName(), $where);
  }

  /**
   * @see Delta_DatabaseCommand::truncate()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function truncate()
  {
    $tableName = $this->getTableName();
    $this->getConnection()->getCommand()->truncate($tableName);
  }
}
