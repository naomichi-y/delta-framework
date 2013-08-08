<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースによるキャッシュ管理機能を提供します。
 * この機能を有効にするには、あらかじめ "delta install-database-cache" コマンドを実行し、データベースにキャッシュテーブルを作成しておく必要があります。
 *
 * キャッシュテーブル作成スクリプト:
 * <code>
 * {DELTA_ROOT_DIR}/skeleton/{database}/cache.sql
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_DatabaseCache extends Delta_Cache
{
  /**
   * @var Delta_DatabaseConnection
   */
  private $_connection;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $cacheConfig = Delta_Config::getApplication()->get('cache.database');

    if ($cacheConfig) {
      $dataSourceId = $cacheConfig->get('dataSource', Delta_DatabaseManager::DEFAULT_DATASOURCE_ID);
    } else {
      $dataSourceId = Delta_DatabaseManager::DEFAULT_DATASOURCE_ID;
    }

    $this->_connection = Delta_DatabaseManager::getInstance()->getConnection($dataSourceId);
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。'foo.bar' のように '.' (ドット) で階層化することが出来ます。
   * @param int $expire キャッシュの有効期限秒。未指定時はキャッシュが削除されるまで値を持続します。
   * @see Delta_Cache::set()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value, $namespace = NULL, $expire = Delta_Cache::EXPIRE_UNLIMITED)
  {
    if ($this->hasCached($name, $namespace)) {
      $this->delete($name, $namespace);
    }

    if ($namespace === NULL) {
      $namespace = $this->getNamespace();
    }

    if ($expire !== Delta_Cache::EXPIRE_UNLIMITED) {
      // 有効期限はデータベース時刻を使用する (ロードバランサ環境下で時刻のズレが発生する可能性があるため)
      $current = $this->_connection->getCommand()->expression('NOW()');

      $datetime = new DateTime($current);
      $datetime->add(new DateInterval(sprintf('PT%sS', $expire)));
      $expireDate = $datetime->format('Y-m-d H:i:s');

    } else {
      $expireDate = NULL;
    }

    $sql = 'INSERT INTO delta_caches(cache_name, cache_value, namespace, expire_date) '
          .'VALUES(:cache_name, :cache_value, :namespace, :expire_date)';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindValue(':cache_name', $name);
    $stmt->bindValue(':cache_value', serialize($value));
    $stmt->bindValue(':namespace', $namespace);
    $stmt->bindValue(':expire_date', $expireDate);
    $stmt->execute();

    return TRUE;
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::get()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $namespace = NULL)
  {
    if ($namespace === NULL) {
      $namespace = $this->getNamespace();
    }

    $sql = 'SELECT cache_value, expire_date, NOW() AS current '
          .'FROM delta_caches '
          .'WHERE cache_name = :cache_name '
          .'AND namespace = :namespace';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindValue(':cache_name', $name);
    $stmt->bindValue(':namespace', $namespace);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->read()) {
      if ($record->expire_date === NULL || Delta_DateUtils::unixtime($record->expire_date >= Delta_DateUtils::unixtime($record->current))) {
        return unserialize($record->cache_value);

      } else {
        $this->delete($name, $namespace);
      }
    }

    return NULL;
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::hasCached()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasCached($name, $namespace = NULL)
  {
    if ($namespace === NULL) {
      $namespace = $this->getNamespace();
    }

    $sql = 'SELECT cache_name, expire_date, NOW() AS current '
          .'FROM delta_caches '
          .'WHERE cache_name = :cache_name '
          .'AND namespace = :namespace';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindValue(':cache_name', $name);
    $stmt->bindValue(':namespace', $namespace);
    $resultSet = $stmt->executeQuery();

    if ($record = $resultSet->read()) {
      if (Delta_DateUtils::unixtime($record->expire_date) >= Delta_DateUtils::unixtime($record->current)) {
        return TRUE;

      } else {
        $this->delete($name, $namespace);
      }
    }

    return FALSE;
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::delete()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function delete($name, $namespace = NULL)
  {
    if ($namespace === NULL) {
      $namespace = $this->getNamespace();
    }

    $sql = 'DELETE FROM delta_caches '
          .'WHERE cache_name = :cache_name '
          .'AND namespace = :namespace';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindValue(':cache_name', $name);
    $stmt->bindValue(':namespace', $namespace);
    $affectedCount = $stmt->execute();

    if ($affectedCount) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @see Delta_Cache::clear()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_connection->rawQuery('TRUNCATE TABLE delta_caches');

    return TRUE;
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::getExpire()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExpire($name, $namespace = NULL)
  {
    if ($namespace === NULL) {
      $namespace = $this->getNamespace();
    }

    $sql = 'SELECT expire_date '
          .'FROM delta_caches '
          .'WHERE cache_name = :cache_name '
          .'AND namespace = :namespace '
          .'AND expire_date >= NOW()';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindValue(':cache_name', $name);
    $stmt->bindValue(':namespace', $namespace);
    $resultSet = $stmt->executeQuery();

    if ($expire = $resultSet->readField(0)) {
      return Delta_DateUtils::unixtime($expire);
    }

    return NULL;
  }
}
