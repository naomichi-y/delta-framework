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
 * 何も処理しないキャッシュクラスです。
 * このクラスは、開発環境でキャッシュの動作を無効にしたい場合に有効でしょう。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_NullCache extends Delta_Cache
{
  /**
   * @return bool TRUE を返します。
   * @see Delta_Cache::set()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function set($name, $value)
  {
    return TRUE;
  }

  /**
   * @return mixed NULL を返します。
   * @see Delta_Cache::get()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name)
  {
    return NULL;
  }

  /**
   * @return bool FALSE を返します。
   * @see Delta_Cache::hasCached()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasCached($name)
  {
    return FALSE;
  }

  /**
   * @return bool TRUE を返します。
   * @see Delta_Cache::delete()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function delete($name)
  {
    return TRUE;
  }

  /**
   * @return bool TRUE を返します。
   * @see Delta_Cache::clear()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    return TRUE;
  }

  /**
   * @return int NULL を返します。
   * @see Delta_Cache::getExpire()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExpire($name)
  {
    return NULL;
  }
}
