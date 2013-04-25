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
 * eAccelerator によるキャッシュ管理機能を提供します。
 * この機能を利用するには、eAccelerator インストール時に '--with-eaccelerator-shared-memory' オプションが有効化されている必要があります。
 *
 * @link http://eaccelerator.net/ eAccelerator
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_EAcceleratorCache extends Delta_Cache
{
  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。'foo.bar' のように '.' (ドット) で階層化することが出来ます。
   * @param int $expire キャッシュの有効期限秒。未指定時はキャッシュが削除されるか無効 (キャッシュストレージの再起動など) になるまで値を持続します。
   * @see Delta_Cache::set()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value, $namespace = NULL, $expire = Delta_Cache::EXPIRE_UNLIMITED)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = array($value, $_SERVER['REQUEST_TIME']);

    return eaccelerator_put($key, $array, $expire);
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::get()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $namespace = NULL)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = eaccelerator_get($key);

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
    $array = eaccelerator_get($key);

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
    $name = $this->getCachePath($name, $namespace);

    return eaccelerator_rm($name);
  }

  /**
   * @see Delta_Cache::clear()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    eaccelerator_gc();

    return TRUE;
  }

  /**
   * @param string $namespace キャッシュを格納する名前空間の指定。
   * @see Delta_Cache::getExpire()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExpire($name, $namespace = NULL)
  {
    $key = $this->getCachePath($name, $namespace);
    $array = eaccelerator_get($key);

    if ($array !== FALSE) {
      return $array[1];
    }

    return NULL;
  }
}
