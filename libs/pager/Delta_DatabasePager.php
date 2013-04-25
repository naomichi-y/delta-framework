<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package pager
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * データベースから取得したレコードセットをページ分割して表示するためのユーティリティです。
 *
 * ページャの使用例:
 * <code>
 * public function execute()
 * {
 *   $conn = $this->getDatabase()->getConnection();
 *   $sql = 'SELECT member_id, nickname, register_date FROM members';
 *
 *   $pager = new Delta_DatabasePager();
 *
 *   // ページ辺りの表示件数を 10 件に指定
 *   $pager->fetchStatement($conn, $sql, 10);
 *
 *   // 'nickname' キーで昇順ソート
 *   $pager->addSort('nickname', Delta_Pager::SORT_ASCENDING);
 *
 *   // レコードセットをページャヘルパに割り当てる
 *   $pager->assignView();
 *
 *   return Delta_View::SUCCESS;
 * }
 * </code>
 * <i>テンプレート上では {@link Delta_DatabasePager ページャヘルパ} を用いてレコードセットを表示することができます。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package pager
 */

class Delta_DatabasePager extends Delta_Pager
{
  /**
   * オプティマイザヒント定数。(MySQL)
   * 総件数取得時に SQL_CALC_FOUND_ROWS を利用してデータ取得クエリを最適化します。
   */
  const HINT_MYSQL_FOUND_ROWS = 1;

  /**
   * オプティマイザヒント定数。
   * 総件数取得時の SELECT クエリを指定します。HINT_MYSQL_FOUND_ROWS と同時の指定はできません。
   */
  const HINT_SELECT_COUNT_SQL = 2;

  /**
   * オプティマイズオプションリスト。
   * @var array
   */
  protected $_optimizerHints = array();

  /**
   * コンストラクタ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function __construct()
  {
    $this->setOptimizerHint(self::HINT_SELECT_COUNT_SQL, 'SELECT COUNT(*)');

    parent::__construct();
  }

  /**
   * クエリを発行する際にオプティマイザへヒントを与えます。
   * データ構成や環境によってはパフォーマンスが向上する可能性があります。
   * このメソッドは {@link fetch()} または {@link fetchStatement()} メソッドを実行するよりも先にコールする必要があります。
   *
   * @param int $optimizerHint オプティマイザへ与えるヒント。HINT_* 定数を指定可能。
   * @param int $hintValue 定数に対応した値。
   *   - {@link HINT_MYSQL_FOUND_ROWS}: 指定不可。
   *   - {@link HINT_SELECT_COUNT_SQL}: 全件カウント時に使用する SELECT ステートメント。既定値は 'SELECT COUNT(*)'。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setOptimizerHint($optimizerHint, $hintValue = NULL)
  {
    if ($optimizerHint == self::HINT_MYSQL_FOUND_ROWS) {
      $this->_optimizerHints[self::HINT_MYSQL_FOUND_ROWS] = TRUE;

    } else if ($optimizerHint == self::HINT_SELECT_COUNT_SQL) {
      $this->_optimizerHints[self::HINT_SELECT_COUNT_SQL] = $hintValue;
    }
  }

  /**
   * column キーによるソートを追加します。
   * リクエストパラメータに含まれるソート指定は、{@link addSort()} の指定よりも優先されます。
   * このメソッドは {@link fetch()} または {@link fetchStatement()} メソッドを実行するよりも先にコールする必要があります。
   *
   * @param string $column ソート対象のカラム名。
   * @param string $type ソート方法。Delta_Pager::SORT_* 定数を指定。
   * @param int $priority ソートの優先順位。未指定の場合は最も低い優先順位になります。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addSort($column, $type = Delta_Pager::SORT_ASCENDING, $priority = NULL)
  {
    if (!isset($this->_requestSort[$column])) {
      foreach ($this->_orders as $orderPriority => $orders) {
        foreach ($orders as $orderColumn => $orderType) {
          if (strcasecmp($column, $orderColumn) === 0) {
            Delta_ArrayUtils::removeShift($this->_orders, $orderPriority);
            break;
          }
        }
      }

      $order = array($column => $type);

      if ($priority !== NULL) {
        Delta_ArrayUtils::insert($this->_orders, $order, $priority);
      } else {
        $this->_orders[] = $order;
      }
    }
  }

  /**
   * プリペアードステートメントを発行してデータセットを取得します。
   * 取得したデータは {@link getRecordSet()} メソッドで取得可能です。
   *
   * @param Delta_DatabaseStatement $statement ステートメントオブジェクト。
   * @param int $pageInRecord 1 ページ辺りに表示するレコード件数。{@link Delta_Pager::OFFSET_NONE} 指定時は全件を取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function fetch(Delta_DatabaseStatement $statement, $pageInRecord = 10)
  {
    $this->fetchStatement(
      $statement->getConnection(),
      $statement->getExpandBindingQuery(),
      $pageInRecord
    );
  }

  /**
   * SQL ステートメントを発行してデータセットを取得します。
   * 取得したデータは {@link getRecordSet()} メソッドで取得可能です。
   *
   * @param Delta_DatabaseConnection $connection コネクションオブジェクト。
   * @param string $query データ取得ステートメント。プリペアード型を使用する場合は {@link fetch()} メソッドを使用して下さい。
   * @param int $pageInRecord 1 ページ辺りに表示するレコード件数。{@link Delta_Pager::OFFSET_NONE} 指定時は全件を取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function fetchStatement(Delta_DatabaseConnection $connection, $query, $pageInRecord = 10)
  {
    $this->bindPageInRecord($pageInRecord);

    if (isset($this->_optimizerHints[self::HINT_MYSQL_FOUND_ROWS])) {
      $recordSet = $this->fetchStatementWithMySQL($connection, $query);

    } else {
      $maxRow = $this->getRecordCount($connection, $query);
      $this->bindPosition($maxRow);
      $recordSet = $this->buildRecordSet($connection, $query);
    }

    $this->setRecordSet($recordSet);
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function fetchStatementWithMySQL(Delta_DatabaseConnection $connection, $query)
  {
    $query = 'SELECT SQL_CALC_FOUND_ROWS '
             .substr($query, stripos($query, 'SELECT') + 6);

    $recordSet = $this->buildRecordSet($connection, $query);
    $maxRow = $connection->rawQuery('SELECT FOUND_ROWS()')->readField(0);

    if ($this->_adjustCount === TRUE && $this->_pageInRecord) {
      $adjustMaxRow = $this->_pageInRecord * $this->_pageLimit;

      if ($adjustMaxRow < $maxRow) {
        $maxRow = $adjustMaxRow;
      }
    }

    if ($this->_pageInRecord !== self::OFFSET_NONE) {
      // 不正なページ指定時
      $maxPage = Delta_NumberUtils::roundUp($maxRow / $this->_pageInRecord, 0);

      if ($maxPage < $this->_currentPage) {
        $this->_startPosition = 1;
        $recordSet = $this->buildRecordSet($connection, $query);
      }
    }

    $this->bindPosition($maxRow);

    return $recordSet;
  }

  /**
   * 全レコード件数を取得します。
   *
   * @param Delta_DatabaseConnection $connection コネクションオブジェクト。
   * @param string $query レコード件数取得ステートメント。
   * @return int 全レコード件数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getRecordCount(Delta_DatabaseConnection $connection, $query)
  {
    $query = sprintf('%s FROM (%s',
      $this->_optimizerHints[self::HINT_SELECT_COUNT_SQL],
      $query);

    if ($this->_adjustCount === TRUE && $this->_pageInRecord) {
      $limit = $this->_pageInRecord * $this->_pageLimit;
      $query .= ' LIMIT ' . $limit . ' OFFSET 0';
    }

    $query .= ') __count';

    return $connection->rawQuery($query)->readField(0);
  }

  /**
   * 現在のページに表示するレコードセットを生成します。
   *
   * @param Delta_DatabaseConnection $connection コネクションオブジェクト。
   * @param string $query 実行クエリ。
   * @return array 生成したレコードセットを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildRecordSet(Delta_DatabaseConnection $connection, $query)
  {
    if (!$this->_startPosition) {
      return array();
    }

    $sortQuery = NULL;
    $pageInRecord = $this->_pageInRecord;

    $pos = strrpos($query, ')');

    if ($pos !== FALSE) {
      $pos++;
      $baseQuery = substr($query, $pos);

    } else {
      $pos = 0;
      $baseQuery = $query;
    }

    if (preg_match('/(ORDER\s+BY\s+)(.+)/i', $baseQuery, $matches)) {
      $query = substr($query, 0, $pos) . substr($baseQuery, 0, strpos($baseQuery, $matches[1]));
      $orders = explode(',', $matches[2]);
      $i = 0;

      foreach ($orders as $order) {
        $split = explode(' ', ltrim($order), 2);

        if (sizeof($split) == 1) {
          $type = Delta_Pager::SORT_ASCENDING;
        } else {
          $type = trim($split[1]);
        }

        $this->addSort($split[0], $type, $i);
        $i++;
      }
    }

    if (sizeof($this->_orders)) {
      foreach ($this->_orders as $order) {
        foreach ($order as $column => $type) {
          $sortQuery .= $column . ' ' . strtoupper($type) . ', ';
        }
      }

      $sortQuery = ' ORDER BY ' . rtrim($sortQuery, ', ');
    }

    $offset = $this->_startPosition - 1;

    if ($pageInRecord == parent::OFFSET_NONE) {
      $pagerQuery = $query . $sortQuery;

    } else {
      $limitStatement = ' LIMIT ' . $pageInRecord . ' OFFSET ' . $offset;
      $pagerQuery = $query . $sortQuery . $limitStatement;
    }

    try {
      $resultSet = $connection->rawQuery($pagerQuery);

    } catch (PDOException $e) {
      // ソートに失敗した場合
      $pagerQuery = $query . $limitStatement;
      $resultSet = $connection->rawQuery($pagerQuery);
    }

    return $resultSet->readAll();
  }
}
