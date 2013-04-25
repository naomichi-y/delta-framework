<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.command
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.command
 */

class Delta_DatabaseMySQLCommand extends Delta_DatabaseCommand
{
  /**
   * @see Delta_DatabaseCommand::getTables()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTables()
  {
    $query = 'SHOW FULL TABLES WHERE TABLE_TYPE = :table_type';

    $stmt = $this->_connection->createStatement($query);
    $stmt->bindValue(':table_type', 'BASE TABLE');
    $resultSet = $stmt->executeQuery();
    $tables = $resultSet->readAllByIndex(0);

    return $tables;
  }

  /**
   * @see Delta_DatabaseCommand::getTableSize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTableSize($tableName)
  {
    $result = FALSE;

    if ($this->isExistTable($tableName)) {
      $query = 'SHOW TABLE STATUS LIKE :table_name';

      $stmt = $this->_connection->createStatement($query);
      $stmt->bindValue(':table_name', $tableName);
      $resultSet = $stmt->executeQuery();
      $result = $resultSet->read()->Data_length;
    }

    return $result;
  }

  /**
   * @see Delta_DatabaseCommand::getTables()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getViews()
  {
    $query = 'SHOW FULL TABLES WHERE TABLE_TYPE = :table_type';

    $stmt = $this->_connection->createStatement($query);
    $stmt->bindValue(':table_type', 'VIEW');
    $resultSet = $stmt->executeQuery();
    $tables = $resultSet->readAllByIndex(0);

    return $tables;
  }

  /**
   * @see Delta_DatabaseCommand::getFields()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFields($tableName)
  {
    $query = 'SHOW COLUMNS FROM ' . $tableName;
    $resultSet = $this->_connection->rawQuery($query);

    $fields = array();

    while ($record = $resultSet->read()) {
      $fields[] = $record->getByIndex(0);
    }

    return $fields;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNativeDataType($type, $length = NULL)
  {
    $nativeType = NULL;

    switch ($type) {
      case 'timestamp':
        $nativeType = 'datetime';
        break;

      default:
        $nativeType = parent::getNativeDataType($type, $length);
        break;
    }

    if ($length) {
      $nativeType = sprintf('%s(%s)', $type, $length);
    }

    return $nativeType;
  }

  /**
   * マスタのバイナリログに関するステータス情報を取得します。
   *
   * @return array ステータス情報を配列形式で返します。値が取得できない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMasterStatus()
  {
    $stmt = $this->_connection->createStatement('SHOW MASTER STATUS');
    $stmt->setFetchMode(Delta_DatabaseStatement::FETCH_TYPE_CLASS);
    $data = $stmt->executeQuery()->read()->toArray();

    return $data;
  }

  /**
   * スレーブのバイナリログに関するステータス情報を取得します。
   *
   * @return array ステータス情報を配列形式で返します。値が取得できない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSlaveStatus()
  {
    $result = FALSE;

    $stmt = $this->_connection->createStatement('SHOW SLAVE STATUS');
    $stmt->setFetchMode(Delta_DatabaseStatement::FETCH_TYPE_CLASS);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->read()) {
      $result = $record->toArray();
    }

    return $result;
  }

  /**
   * @see Delta_DatabaseCommand::getVersion()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getVersion($numberOnly = FALSE)
  {
    $resultSet = $this->_connection->rawQuery('SELECT version()');
    $version = $resultSet->readField(0);

    if ($numberOnly) {
      $version = substr($version, 0, strpos($version, '-'));
    }

    return $version;
  }
}
