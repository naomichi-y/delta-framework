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
 * memcached によるキャッシュ管理機能を提供します。
 * この機能を利用するには、実行環境において memcached、PECL memcache パッケージがインストールされている必要があります。
 *
 * application.yml の設定例:
 * <code>
 * # キャッシュストレージ属性。
 * cache:
 *   # memcache 属性。
 *   memcache:
 *     # 接続先ホスト名。
 *     host: localhost
 *
 *     # 接続先ポート番号。
 *     port: 11211
 *
 *     # サーバへ接続する際のタイムアウト秒。
 *     timeout: 30
 * </code>
 *
 * @link http://www.danga.com/memcached/ memcached
 * @link http://pecl.php.net/package/memcache/ PECL memcache
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_MemcacheCache extends Delta_Cache
{
  /**
   * Memcache オブジェクト。
   * @var Memcache
   */
  private $_memcache;

  /**
   * データを圧縮して格納する場合は MEMCACHE_COMPRESSED を指定。
   * @var int
   */
  private $_compressed = 0;

  /**
   * コンストラクタ。
   *
   * @param array $options memcached サーバへの接続情報。
   *   - $options['host']: 接続先ホスト名。
   *   - $options['port']: 接続先ポート番号。
   *   - $options['timeout']: サーバへ接続する際のタイムアウト秒。
   * @throws Delta_ConnectException memcached サーバへの接続に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(array $options = array())
  {
    $host = 'localhost';
    $port = 11211;
    $timeout = 30;

    if (sizeof($options)) {
      $options = new Delta_ParameterHolder($options, TRUE);
    } else {
      $options = Delta_Config::getApplication()->get('cache.memcache');
    }

    if ($options) {
      $host = $options->getString('host', $host);
      $port = $options->getInt('port', $port);
      $timeout = $options->getInt('timeout', $timeout);
    }

    try {
      $this->_memcache = new Memcache();
      $this->_memcache->pconnect($host, $port, $timeout);

    } catch (ErrorException $e) {
      throw new Delta_ConnectException($e->getMessage());
    }
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。'foo.bar' のように '.' (ドット) で階層化することが出来ます。
   * @param int $expire キャッシュの有効期限秒。未指定時はキャッシュが削除されるか無効 (キャッシュストレージの再起動など) になるまで値を持続します。
   * @see Delta_Cache::set()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value, $namespace = NULL, $expire = Delta_Cache::EXPIRE_UNLIMITED)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = serialize(array($value, $_SERVER['REQUEST_TIME']));

    return $this->_memcache->set($key, $array, 0, $expire);
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::get()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $namespace = NULL)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = unserialize($this->_memcache->get($key));

    if ($array !== FALSE) {
      return $array[0];
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
    $key = $this->getCachePath($name, $namespace);
    $array = $this->_memcache->get($key);

    if ($array !== FALSE) {
      return TRUE;
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
    $key = $this->getCachePath($name, $namespace);

    return $this->_memcache->delete($key);
  }

  /**
   * @see Delta_Cache::clear()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    return $this->_memcache->flush();
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::getExpire()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExpire($name, $namespace = NULL)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = $this->_memcache->get($key);

    if ($array !== FALSE) {
      return $array[1];
    }

    return NULL;
  }

  /**
   * Memcache オブジェクトのインスタンスを取得します。
   *
   * @return Memcache Memcache のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getStorage()
  {
    return $this->_memcache;
  }

  /**
   * データを圧縮して格納します。(zlib を使用します)
   *
   * @param bool $compressed データを圧縮して格納するかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCompressed($compressed)
  {
    if ($compressed) {
      $this->_compressed = MEMCACHE_COMPRESSED;
    } else {
      $this->_compressed = 0;
    }
  }

  /**
   * オブジェクトの破棄を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    if (is_object($this->_memcache)) {
      $this->_memcache->close();
    }
  }
}
