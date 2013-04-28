<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベーストランザクションを管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 * @since 1.1
 */

class Delta_DatabaseTransactionController extends Delta_Object
{
  /**
   * @var Delta_DatabaseConnection
   */
  private $_connection;

  /**
   * @var bool
   */
  private $_isActiveTransaction = FALSE;

  /**
   * トランザクションコントローラに実行クエリを通知します。
   *
   * @param Delta_DatabaseConnection $connection コネクションオブジェクト。
   * @param string $query 実行クエリ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function notify($connection, $query)
  {
    $compare = substr(ltrim($query), 0, 6);

    if (strcasecmp($compare, 'INSERT') == 0 || strcasecmp($compare, 'UPDATE') == 0 || strcasecmp($compare, 'DELETE') == 0) {
      if (!$this->_isActiveTransaction) {
        $connection->beginTransaction();

        $this->_connection = $connection;
        $this->_isActiveTransaction = TRUE;
      }
    }
  }

  /**
   * @see Delta_DatabaseConnection::isActiveTransaction()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActiveTransaction()
  {
    return $this->_isActiveTransaction;
  }

  /**
   * @see Delta_DatabaseConnection::rollback()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rollback()
  {
    return $this->_connection->rollback();
  }

  /**
   * @see Delta_DatabaseConnection::commit()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function commit()
  {
    return $this->_connection->commit();
  }
}
