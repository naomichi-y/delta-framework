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
 * キャッシュストレージによるデータの読み書きを実装するためのメソッドを定義します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

abstract class Delta_Cache extends Delta_Object
{
  /**
   * キャッシュ有効期限定数。(無期限)
   */
  const EXPIRE_UNLIMITED = 0;

  /**
   * キャッシュパスタイプ定数。(ハッシュ化なし)
   */
  const CACHE_PATH_TYPE_PLANE = 1;

  /**
   * キャッシュパスタイプ定数。(MD5 ハッシュ化)
   */
  const CACHE_PATH_TYPE_MD5 = 2;

  /**
   * デフォルトの名前空間。(既定値は 'default')
   * @var string
   */
  private $_namespace = 'default';

  /**
   * 名前空間のデリミタ。(既定値は '.')
   * @var string
   */
  private $_namespaceDelimiter = '.';

  /**
   * キャッシュパスの生成タイプ。
   * @var int
   */
  private $_cachePathType = self::CACHE_PATH_TYPE_MD5;

  /**
   * キャッシュ領域にデータを格納します。
   *
   * @param string $name データを表すキャッシュ名。同じ名前で set() が実行されると、古いデータは新しい値で上書きされます。
   * @param mixed $value 格納するデータ。データのシリアライズ化は自動的に行われます。
   * @return bool 書き込みに成功したかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function set($name, $value);

  /**
   * キャッシュ領域からデータを取得します。
   *
   * @param string $name 取得するデータのキャッシュ名。
   * @return mixed キャッシュ名に対応するデータを返します。データのデシリアライズ化は自動的に行われます。
   *   データが存在しない、あるいは有効期限切れの場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function get($name);

  /**
   * 指定したキャッシュ名がキャッシュ格納に設定されているかチェックします。
   *
   * @param string $name チェック対象のキャッシュ名。
   * @return bool キャッシュ名に対応するデータが格納されている場合は TRUE、データが存在しない、あるいは有効期限切れの場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function hasCached($name);

  /**
   * キャッシュ領域からデータを削除します。
   *
   * @param string $name 削除対象のキャッシュ名。
   * @return bool 削除に成功したかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function delete($name);

  /**
   * キャッシュ領域に格納されているユーザキャッシュに対しガベージコレクションを実行します。
   * 格納されている全てのデータはキャッシュストレージから削除されます。
   *
   * @return bool 削除に成功したかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function clear();

  /**
   * 指定したキャッシュ名の有効期限を取得します。
   *
   * @param string $name チェック対象のキャッシュ名。
   * @return int キャッシュの有効期限を UNIX 秒で取得します。キャッシュが存在しないか無効時は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getExpire($name);

  /**
   * キャッシュ領域におけるデフォルトの名前空間を設定します。
   *
   * @param string $namespace 名前空間。'foo.bar.baz' のように '.' (ドット) で階層を指定することが出来ます。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setNamespace($namespace)
  {
    $this->_namespace = $namespace;
  }

  /**
   * キャッシュ領域におけるデフォルトの名前空間を取得します。
   *
   * @return string デフォルトの名前空間を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNamespace()
  {
    return $this->_namespace;
  }

  /**
   * 名前空間のデリミタを設定します。
   * このメソッドは、値の設定や取得を行う先にコールする必要があります。
   * (デフォルトのデリミタは '.')
   *
   * @param string $namespaceDelimiter 名前空間に使用するデリミタ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setNamespaceDelimiter($namespaceDelimiter)
  {
    $this->_namespaceDelimiter = $namespaceDelimiter;
  }

  /**
   * 名前空間のデリミタを取得します。
   *
   * @return string 名前空間のデリミタを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNamespaceDelimiter()
  {
    return $this->_namespaceDelimiter;
  }

  /**
   * キャッシュ名と名前空間からキャッシュを格納するパスを取得します。
   *
   * @param string $name キャッシュ名。
   * @param string $namespace 名前空間。
   * @return string キャッシュを格納するパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCachePath($name, $namespace = NULL)
  {
    if ($namespace === NULL) {
      $namespace = $this->_namespace;
    }

    $cachePath = $namespace . $this->getNamespaceDelimiter() . $name;

    return $cachePath;
  }

  /**
   * @todo 1.6 (reserve method)
   */
  //abstract public function getLastModified();

  /**
   * キャッシュパスの生成方法を設定します。
   * このメソッドは、値の設定や取得を行う先にコールする必要があります。
   * (デフォルトでは {@link CACHE_PATH_TYPE_MD5} によるパス生成が行われます)
   *
   * @param int $convertHash CACHE_PATH_TYPE_* 定数を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setCachePathType($cachePathType)
  {
    $this->_cachePathType = $cachePathType;
  }

  /**
   * キャッシュパスの生成方法を取得します。
   *
   * @return int CACHE_PATH_TYPE_* 定数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCachePathType()
  {
    return $this->_cachePathType;
  }
}
