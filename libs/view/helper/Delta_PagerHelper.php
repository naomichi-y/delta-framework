<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_Pager} クラスで生成したレコードセットをテンプレート上で表示するためのヘルパメソッドを提供します。
 * このヘルパは、{@link Delta_Pager::assignView()} メソッドがコールされた時点で、テンプレート変数 $pager にページャのインスタンスが割り当てられます。
 *
 * <code>
 * <?php echo $pager->{method}; ?>
 * </code>
 *
 * global_helpers.yml の設定例:
 * <code>
 * pager:
 *   # ヘルパクラス名。
 *   class: Delta_HTMLHelper
 *
 *   # 昇順ソートのラベル。ソートのボタンで使用される。
 *   ascendingLabel: asc
 *
 *   # 子順ソートのラベル。ソートのボタンで使用される。
 *   descendingLabel: desc
 *
 *   # 前ページへのラベル。
 *   previousLabel: prev
 *
 *   # 次ページへのラベル。
 *   nextLabel: next
 *
 *   # {@link getLinkList() リンクリスト} に含める (現在位置から見た) 前後のページ数。
 *   linkRange:
 *
 *   # {@link getLinkList() リンクリスト} 内で現在のページ番号を表すラベル。
 *   #   - \1: 表示するラベル。(ページ番号)
 *   linkListCurrentLabel: '\1'
 *
 *   # {@link getLinkList() リンクリスト} 内のページ番号を表すラベル。
 *   #   - \1: 表示するラベル。('\1' にはリンクタグが含まれる)
 *   linkListAnchorLabel: '\1'
 *
 *   # {@link getNavigationLabel() ページナビゲーション} 用のラベル。
 *   #   - \1: ページ内でのレコードセット開始位置
 *   #   - \2: ページ内でのレコードセット終了位置
 *   #   - \3: 総レコード件数
 *   #   - \4: 前ページへのリンク ('pager.previousLabel')
 *   #   - \5: 次ページへのリンク ('pager.nextLabel')
 *   #   - \6: {@link getLinkList() リンクリスト}
 *   navigationLabel: '\1-\2 / \3 \4 \5<br />\6'
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_PagerHelper extends Delta_Helper
{
  /**
   * @var array
   */
  protected static $_defaultValues = array(
    'ascendingLabel' => 'asc',
    'descendingLabel' => 'desc',
    'linkListCurrentLabel' => "\\1",
    'linkListAnchorLabel' => "\\1",
    'navigationLabel' => "\\1-\\2 / \\3 \\4 \\5<br />\\6",
    'previousLabel' => 'prev',
    'nextLabel' => 'next',
    'linkRange' => 5,
  );

  /**
   * {@link Delta_HTMLHelper} オブジェクト。
   * @var Delta_HTMLHelper
   */
  private $_html;

  /**
   * レコードセット。
   * @var array()
   */
  private $_recordSet;

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $view, array $config = array())
  {
    parent::__construct($view, $config);

    $this->_html = $view->getHelperManager()->getHelper('html');
    $this->_recordSet = $config['_recordSet'];
  }

  /**
   * 現在のページで表示可能なレコードが存在するかチェックします。
   *
   * @return bool 表示可能なレコードが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasRecord()
  {
    $result = FALSE;

    if (sizeof($this->_config['_recordSet'])) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 次のレコードを取得します。
   *
   * @param bool $escape レコードデータを HTML エスケープした状態で返す場合は TRUE を指定。
   * @return array 次のレコードを返します。レコードが存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function next($escape = TRUE)
  {
    $result = FALSE;
    $current = current($this->_recordSet);
    next($this->_recordSet);

    if ($current) {
      if ($escape) {
        foreach ($current as $key => $value) {
          $current[$key] = Delta_StringUtils::escape($value);
        }
      }

      $result = $current;
    }

    return $result;
  }

  /**
   * 最終ページの番号を取得します。
   *
   * @return int 最終ページの番号を返します。最終ページが取得できない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLastPage()
  {
    return $this->_config['_lastPage'];
  }

  /**
   * 現在開いているページで表示可能なレコード件数を取得します。
   *
   * @return int 現在開いているページで表示可能なレコード件数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCurrentPageRecordSize()
  {
    return sizeof($this->_config['_recordSet']);
  }

  /**
   * レコードの総件数を取得します。
   *
   * @return int レコードの総件数を取得します。レコード件数が取得できない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getMaxRow()
  {
    return $this->_config['_maxRow'];
  }

  /**
   * ページ内でのレコードセット取得開始位置を取得します。
   * 例えば 1 ページに 10 件表示する場合、2 ページ目でメソッドを実行すると結果は 11 が返されます。
   *
   * @return int レコードセットの取得開始位置を返します。表示するべきレコードセットが存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getStartPosition()
  {
    return $this->_config['_startPosition'];
  }

  /**
   * ページ内でのレコードセット取得終了位置を取得します。
   * 例えば 1 ページに 10 件表示する場合、2 ページ目でメソッドを実行すると結果は 20 が返されます。
   *
   * @return int レコードセットの取得終了位置を返します。表示するべきレコードセットが存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEndPosition()
  {
    return $this->_config['_endPosition'];
  }

  /**
   * 次のページが存在するかチェックします。
   *
   * @return bool 次のページが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasNextPage()
  {
    return $this->_config['_nextPage'];
  }

  /**
   * 前のページが存在するかチェックします。
   *
   * @return bool 前のページが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasPreviousPage()
  {
    return $this->_config['_previousPage'];
  }

  /**
   * 次のページへ移動するためのリンクパスを取得します。
   *
   * @return string 次のページへ移動するためのリンクパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNextPagePath()
  {
    // 不正なページ指定が行われた場合は 2 ページ目へのリンクパスを生成
    // 2 ページ目自体存在しない場合は FALSE を返す
    return $this->link($this->_config['_nextPage']);
  }

  /**
   * 前のページへ移動するためのリンクパスを取得します。
   *
   * @return string 前のページへ移動するためのリンクパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPreviousPagePath()
  {
    $previousPage = $this->_config['_previousPage'];

    if ($previousPage === FALSE) {
      $previousPage = 1;
    }

    return $this->link($previousPage);
  }

  /**
   * 現在開いているページ番号を取得します。
   *
   * @return int 現在開いているページの番号を返します。
   *   ページ指定がクエリに含まれていない場合や、ページ番号の指定が不正な場合は 1 を返します。
   *   また、その他何らかの理由で取得に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCurrentPage() {
    return $this->_config['_currentPage'];
  }

  /**
   * リンクリストの開始ページ番号を取得します。
   * リンクリストに表示するページ数はヘルパ属性 'pager.linkRange' で設定可能です。
   *
   * @return int リンクリストの開始ページ番号を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLinkStartRange()
  {
    $linkRange = $this->_config->getInt('linkRange');
    $currentPage = $this->_config['_currentPage'];

    if ($currentPage > $linkRange) {
      $range = $currentPage - $linkRange;
    } else {
      $range = 1;
    }

    return $range;
  }

  /**
   * リンクリストの最終ページ番号を取得します。
   * リンクリストに表示するページ数はヘルパ属性 'pager.linkRange' で設定可能です。
   *
   * @return int リンクリストの最終ページ番号を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLinkEndRange()
  {
    $linkRange = $this->_config->getInt('linkRange');

    if ($linkRange) {
      $offset = $this->_config['_currentPage'] + $linkRange;
      $lastPage = $this->_config['_lastPage'];

      if ($offset > $lastPage) {
        return $lastPage;
      }

      return $offset;

    } else {
      return $this->_config['_lastPage'];
    }
  }

  /**
   * ページ遷移を補助するリンクリストのタグを取得します。
   * 例えば 6 ページ目を表示している場合、前後 2 ページに移動するためのリンク (4、5、7、8) を生成します。
   * getLinkList() メソッドが生成されるタグは次のようになります。
   * <code>
   * <span class="page_link"><a href="/pager.do/page/4">4</a></span>
   * <span class="page_link"><a href="/pager.do/page/5">5</a></span>
   * <span class="page_current">6</span>
   * <span class="page_link"><a href="/pager.do/page/7">7</a></span>
   * <span class="page_link"><a href="/pager.do/page/8">8</a></span>
   * </code>
   * リンクリストに表示するページ数はヘルパ属性 'pager.linkRange' で設定可能です。
   * また、各リンクの出力形式は 'linkListCurrentLabel', 'linkListAnchorLabel' 属性で変更することができます。
   *
   * @return string リンクリストのタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLinkList()
  {
    $current = $this->getCurrentPage();
    $start = $this->getLinkStartRange();
    $end = $this->getLinkEndRange();

    $buffer = NULL;

    $linkListCurrentLabel = $this->_config->getString('linkListCurrentLabel');
    $linkListAnchorLabel = $this->_config->getString('linkListAnchorLabel');

    for ($i = $start; $i <= $end; $i++) {
      if ($i == $current) {
        $buffer .= str_replace('\1', $i, $linkListCurrentLabel);

      } else {
        $buffer .= preg_replace('/\\\1/',
          $this->_html->link($i, $this->link($i)),
          $linkListAnchorLabel);
      }

      $buffer .= "\n";
    }

    return $buffer;
  }

  /**
   * 指定したページ番号を元にリンクパスを生成します。
   *
   * @param int $page ページ番号。
   * @param mixed $queryData パスに追加するクエリパラメータ。
   *   array('{query_name}' => '{query_value}') 形式で指定可能。
   * @return string ページ番号を元に生成したリンクパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function link($page, $queryData = array())
  {
    $pagerKey = $this->_config->getString('_pagerKey');

    $data = array();
    $data[$pagerKey] = $page;
    $data += $this->_config['_queries'];
    $data += self::constructParameters($queryData);

    $ascendingKey = $this->_config->getString('_ascendingKey');
    $descendingKey = $this->_config->getString('_descendingKey');

    if (!isset($queryData[$ascendingKey]) && !isset($queryData[$descendingKey])) {
      $orders = $this->_config['_orders'];

      if (sizeof($orders)) {
        $key = key($orders);

        if (($type = $orders[$key]) == Delta_Pager::SORT_ASCENDING) {
          $value = $ascendingKey;
        } else {
          $value = $descendingKey;
        }

        $key = $this->_config['_cipher']->encrypt($key);
        $data[$value] = $key;
      }
    }

    $path = array('action' => $this->_config['_actionName']);
    $isAbsolutePath = $this->_html->isAbsolutePath();

    return $this->buildRequestPath($path, $data, $isAbsolutePath);
  }

  /**
   * name が指定した順序 sortType でソートされているかチェックします。
   *
   * @param string $name 対象の名前。
   * @param string $sortType ソートタイプ。Delta_Pager::SORT_* 定数を指定。
   * @return bool name が sortType で指定した順序でソートされている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasSort($name, $sortType)
  {
    foreach ($this->_config['_orders'] as $key => $value) {
      if ($name == $key && $sortType == $value) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * レコードセットを name キーで昇順に並び替えるためのリクエストパスを取得します。
   *
   * @param string $name ソート対象の名前。
   * @return string 昇順ソートのためのリクエストパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSortAscendingPath($name)
  {
    $name = $this->_config['_cipher']->encrypt($name);
    $parameters = array($this->_config->getString('_ascendingKey') => $name);

    return $this->link($this->_config['_currentPage'], $parameters);
  }

  /**
   * レコードセットを name キーで降順に並び替えるためのリクエストパスを取得します。
   *
   * @param string $name ソート対象の名前。
   * @return string 降順ソートのためのリクエストパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSortDescendingPath($name)
  {
    $name = $this->_config['_cipher']->encrypt($name);
    $parameters = array($this->_config->getString('_descendingKey') => $name);

    return $this->link($this->_config['_currentPage'], $parameters);
  }

  /**
   * ソート用のラベルタグを取得します。
   * ラベルのフォーマットはヘルパ属性 'pager.ascendingLabel'、'pager.descendingLabel' で設定可能です。
   *
   * @param string $title ラベル名。
   * @param string $name ソート対象の名前。
   * @return string ソート用のラベルタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSortLabel($label, $name)
  {
    $orders = $this->_config['_orders'];
    $buffer = NULL;

    if (isset($orders[$name])) {
      if ($orders[$name] == Delta_Pager::SORT_ASCENDING) {
        $buffer = sprintf("%s <a href=\"%s\">%s</a>",
          $label,
          $this->getSortDescendingPath($name),
          $this->_config->getString('ascendingLabel'));

      } else {
        $buffer = sprintf("%s <a href=\"%s\">%s</a>",
          $label,
          $this->getSortAscendingPath($name),
          $this->_config->getString('descendingLabel'));
      }

    } else {
      $buffer = sprintf("%s <a href=\"%s\">%s</a> <a href=\"%s\">%s</a>",
        $label,
        $this->getSortAscendingPath($name),
        $this->_config->getString('ascendingLabel'),
        $this->getSortDescendingPath($name),
        $this->_config->getString('descendingLabel'));
    }

    return $buffer;
  }

  /**
   * ページャ用のナビゲーションタグを取得します。
   * ナビゲーションラベルには、総レコード件数の表示や前後ページへのリンク、リンクリスト等が含まれます。
   * ラベルのフォーマットはヘルパ属性 'pager.navigationLabel' で設定することができます。
   *
   * @return string ページャ用のナビゲーションラベルタグを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNavigationLabel()
  {
    $navigationLabel = $this->_config->getString('navigationLabel');
    $html = $this->_view->getHelperManager()->getHelper('html');

    $from = array('\1', '\2', '\3', '\4', '\5', '\6');

    $to = array();
    $to[] = $this->getStartPosition();
    $to[] = $this->getEndPosition();
    $to[] = $this->getMaxRow();

    if ($this->hasPreviousPage()) {
      $label = $this->_config->getString('previousLabel');
      $to[] = $html->link($label, $this->getPreviousPagePath());

    } else {
      $to[] = NULL;
    }

    if ($this->hasNextPage()) {
      $label = $this->_config->getString('nextLabel');
      $to[] = $html->link($label, $this->getNextPagePath());

    } else {
      $to[] = NULL;
    }

    $to[] = $this->getLinkList();

    return str_replace($from, $to, $navigationLabel);
  }

  /**
   * {@link Delta_Pager::addQueryData()} メソッドで追加したクエリデータを元に hidden フィールドを生成します。
   *
   * @return string 構築した hidden フィールドのリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createHiddenFields()
  {
    $form = $this->_view->getHelperManager()->getHelper('form');
    $buffer = $form->parameterToInputHiddens($this->_config['_queries']);

    return $buffer;
  }
}
