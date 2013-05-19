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
   * データソース名。(既定値は Delta_DatabaseManager::DEFAULT_NAMESPACE)
   * @var string
   */
  private $_dataSource = Delta_DatabaseManager::CONNECT_DEFAULT_NAMESPACE;

  /**
   * 名前空間。
   */
  protected $_namespace = 'default';

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
   * @param $dataSource 未指定の場合、{@link getNamespace()} で取得した名前空間のデータベースが参照される。
   * @see Delta_DatabaseManager::getConnection()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnection($dataSource = NULL)
  {
    $database = Delta_DIContainerFactory::getContainer()->getComponent('database');

    if ($dataSource === NULL) {
      return $database->getConnection($this->getNamespace());
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
    $form = Delta_DIContainerFactory::getContainer()->getComponent('form');
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
   * DAO が接続するデータベースの名前空間を設定します。
   *
   * @param string $namespace データベースの名前空間。デフォルトの接続先は 'default' となります。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   * @since 1.1
   */
  public function setNamespace($namespace)
  {
    $this->_namespace = $namespace;
  }

  /**
   * DAO が接続するデータベースの名前空間を取得します。
   *
   * @return string データベースの名前空間を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNamespace()
  {
    return $this->_namespace;
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
   * @deprecated 1.16.0 で破棄予定
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
   * レコードを登録します。
   *
   * @param Delta_Entity $entity データベースに登録するエンティティ。
   * @return int 最後に挿入された行の ID を返します。
   *   詳しくは {@link PDO::lastInsertId()} のマニュアルを参照して下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function insert(Delta_DatabaseEntity $entity)
  {
    $tableName = $this->getTableName();
    $data = $entity->toArray();

    return $this->getConnection()->getCommand()->insert($tableName, $data);
  }

  /**
   * 全レコード数を取得します。
   *
   * @return int 全レコード数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCount()
  {
    return $this->getConnection()->getCommand()->getRecordCount($this->getTableName());
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
