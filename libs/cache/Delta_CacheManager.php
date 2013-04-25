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
 * ファイルやメモリを使ったキャッシュ管理機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_CacheManager extends Delta_Object
{
  /**
   * NULL キャッシュ定数。(キャッシュしない)
   */
  const CACHE_TYPE_NULL = 'null';

  /**
   * ファイルキャッシュ定数。
   */
  const CACHE_TYPE_FILE = 'file';

  /**
   * APC キャッシュ定数。
   */
  const CACHE_TYPE_APC = 'apc';

  /**
   * XCache キャッシュ定数。
   */
  const CACHE_TYPE_XCACHE = 'xcache';

  /**
   * EAccelerator キャッシュ定数。
   */
  const CACHE_TYPE_EACCELERATOR = 'eaccelerator';

  /**
   * memcache キャッシュ定数。
   */
  const CACHE_TYPE_MEMCACHE = 'memcach';

  /**
   * データベースキャッシュ定数。
   */
  const CACHE_TYPE_DATABASE = 'database';

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * 指定したキャッシュタイプのインスタンスを取得します。
   *
   * @param string $type キャッシュタイプ定数 CACHE_TYPE_* の指定。
   * @param array $options キャッシュストレージオプション。
   * @return Delta_Cache Delta_Cache を実装したキャッシュオブジェクトのインスタンスを返します。
   * @throws InvalidArgumentException type に渡された値が不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance($type, array $options = array())
  {
    static $instance = array();

    if (empty($instance[$type])) {
      switch ($type) {
        case self::CACHE_TYPE_FILE:
          $className = 'Delta_FileCache';
          break;

        case self::CACHE_TYPE_APC:
          $className = 'Delta_APCCache';
          break;

        case self::CACHE_TYPE_XCACHE:
          $className = 'Delta_XCacheCache';
          break;

        case self::CACHE_TYPE_EACCELERATOR:
          $className = 'Delta_EAcceleratorCache';
          break;

        case self::CACHE_TYPE_MEMCACHE:
          $className = 'Delta_MemcacheCache';
          break;

        case self::CACHE_TYPE_DATABASE:
          $className = 'Delta_DatabaseCache';
          break;

        case self::CACHE_TYPE_NULL:
          $className = 'Delta_NullCache';
          break;

        default:
          throw new InvalidArgumentException('Cache type is illegal.');
      }

      $classPath = DELTA_LIBS_DIR . '/cache/' . $className . '.php';

      Delta_ClassLoader::loadByPath($classPath, $className);
      $instance[$type] = new $className($options);
    }

    return $instance[$type];
  }
}
