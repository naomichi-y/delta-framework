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

class Delta_DatabasePostgreSQLCommand extends Delta_DatabaseCommand
{
  /**
   * @see Delta_DatabaseCommand::getTables()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTables()
  {
    $query = 'SELECT tablename '
      .'FROM PG_TABLES '
      .'WHERE tablename NOT LIKE :tablename '
      .'AND schemaname != :schemaname '
      .'ORDER BY tablename ASC';

    $stmt = $this->_connection->createStatement($query);
    $stmt->bindValue(':tablename', 'pg_%');
    $stmt->bindValue(':schemaname', 'information_schema');
    $resultSet = $stmt->executeQuery();

    return $resultSet->readAllByIndex(0);
  }

  /**
   * @see Delta_DatabaseCommand::getTableSize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTableSize($tableName)
  {
    $result = FALSE;

    if ($this->isExistTable($tableName)) {
      $query = 'SELECT pg_table_size(:table_name)';

      $stmt = $this->_connection->createStatement($query);
      $stmt->bindValue(':table_name', $tableName);
      $resultSet = $stmt->executeQuery();
      $result = $resultSet->read()->pg_table_size;
    }

    return $result;
  }

  /**
   * @see Delta_DatabaseCommand::getViews()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getViews()
  {
    $query = 'SELECT * '
      .'FROM pg_views '
      .'WHERE schemaname NOT IN(:schemaname1, :schemaname2)';

    $stmt = $this->_connection->createStatement($query);
    $stmt->bindValue(':schemaname1', 'information_schema');
    $stmt->bindValue(':schemaname2', 'pg_catalog');
    $resultSet = $stmt->executeQuery();

    return $resultSet->readAllByIndex('viewname');
  }

  /**
   * @see Delta_DatabaseCommand::getFeilds()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFields($tableName)
  {
    $stmt = $this->_connection->rawQuery('SELECT CURRENT_DATABASE()');
    $databaseName = $stmt->readField(0);

    $query = 'SELECT column_name '
      .'FROM information_schema.columns '
      .'WHERE table_catalog = :table_catalog '
      .'AND table_name = :table_name '
      .'ORDER BY ordinal_position ASC';

    $stmt = $this->_connection->createStatement($query);
    $stmt->bindValue(':table_catalog', $databaseName);
    $stmt->bindValue(':table_name', $tableName);
    $resultSet = $stmt->executeQuery();

    return $resultSet->readAllByIndex(0);
  }

  /**
   * @see Delta_DatabaseCommand::getNativeDataType()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function getNativeDataType($type, $length = NULL)
  {
    switch ($type) {
      case 'tinyint':
      case 'mediumint':
        $nativeType = 'smallint';
        break;

      default:
        $nativeType = parent::getNativeDataType($type, $length);
        break;
    }

    return $nativeType;
  }

  /**
   * @see Delta_DatabaseCommand::getVersion()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getVersion($versionOnly = FALSE)
  {
    $result = $this->_connection->rawQuery('SELECT version()');
    $version = $result->readField(0);

    if ($versionOnly) {
      $array = explode(' ', $version);
      $version = $array[1];
    }

    return $version;
  }
}
