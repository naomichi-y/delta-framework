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
 * 配列で構成されるレコードセットをページ分割して表示するためのユーティリティです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package pager
 */

class Delta_ArrayPager extends Delta_Pager
{
  /**
   * 配列から構成されるレコードセットをページャに割り当てます。
   *
   * @param array $recordSet ページャに割り当てるレコードセット。
   * @param int $pageInRecord 1 ページ辺りに表示するレコード件数。{@link Delta_Pager::OFFSET_NONE} 指定時は全件を取得します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function fetch(array $recordSet, $pageInRecord = 10)
  {
    if (sizeof($this->_orders)) {
      usort($recordSet, array($this, 'sort'));
    }

    $this->bindPageInRecord($pageInRecord);
    $this->bindPosition(sizeof($recordSet));

    if ($pageInRecord != Delta_Pager::OFFSET_NONE) {
      $recordSet = array_slice($recordSet, $this->_startPosition - 1, $pageInRecord);
    }

    if ($this->_adjustCount === TRUE && $pageInRecord) {
      $adjustMaxRow = $pageInRecord * $this->_pageLimit;

      if ($adjustMaxRow < $this->_maxRow) {
        $this->_maxRow = $adjustMaxRow;
      }
    }

    $this->setRecordSet($recordSet);
  }

  /**
   * 自然順アルゴリズムによるデータセットのソートを行います。
   *
   * @param array $field1 対象データセット 1。
   * @param array $field1 対象データセット 2。
   * @return bool field1 が field2 より小さい場合に -1、field1 が field2 より大きい場合に 1、等しい場合は0 を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function sort(array $field1, array $field2)
  {
    $key = key($this->_orders[0]);
    $type = $this->_orders[0][$key];

    if ($type == self::SORT_ASCENDING) {
      $result = strnatcasecmp($field1[$key], $field2[$key]);
    } else {
      $result = strnatcasecmp($field2[$key], $field1[$key]);
    }

    return $result;
  }

  /**
   * key によるソートを設定します。
   * リクエストパラメータに含まれるソート指定は、{@link addSort()} の指定よりも優先されます。
   * このメソッドは {@link fetch()} メソッドを実行するよりも先にコールする必要があります。
   *
   * @param string $column ソート対象のカラム名。
   * @param string $type ソート方法。Delta_Pager::SORT_* 定数を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSort($key, $type = self::SORT_ASCENDING)
  {
    if (sizeof($this->_requestSort) == 0) {
      $this->_orders = array();
      $this->_orders[] = array($key => $type);
    }
  }
}
