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
 * データベースを操作するための DAO オブジェクトを生成します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.dao
 */

class Delta_DAOFactory extends Delta_Object
{
  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * DAO オブジェクトを生成します。
   *
   * @param string $name DAO の名前、またはクラス名。
   * @return Delta_DAO {@link Delta_DAO} を実装した DAO のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function create($name)
  {
    static $instance = array();

    if (strcmp(substr($name, -3), 'DAO') != 0) {
      $name .= 'DAO';
    }

    if (empty($instance[$name])) {
      $instance[$name] = new $name;
    }

    return $instance[$name];
  }
}
