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
 * レコードセットをページ分割して表示するための抽象クラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package pager
 */

abstract class Delta_Pager extends Delta_Object
{
  /**
   * 全レコード取得定数。
   */
  const OFFSET_NONE = 0;

  /**
   * 昇順定数。
   */
  const SORT_ASCENDING = 'asc';

  /**
   * 降順定数。
   */
  const SORT_DESCENDING = 'desc';

  /**
   * {@link Delta_Pager} オブジェクト。
   * @var Delta_Pager
   */
  static protected $_instance = NULL;

  /**
   * リクエストオブジェクト。
   * @var Delta_HttpRequest
   */
  protected $_request;

  /**
   * {@link Delta_BlowfishCipher} オブジェクト。
   * @var Delta_BlowfishCipher
   */
  protected $_cipher;

  /**
   * GET パラメータで使用するページ ID。
   * @var string
   */
  protected $_pagerKey = 'page';

  /**
   * 昇順ソートのリクエストパラメータキー。
   * @var string
   */
  protected $_ascendingKey = 'asc';

  /**
   * 降順ソートのリクエストパラメータキー。
   * @var string
   */
  protected $_descendingKey = 'desc';

  /**
   * リンクに使用されるアクション名。
   * @var string
   */
  protected $_actionName;

  /**
   * リクエストパラメータリスト。
   * @var array
   */
  protected $_queries = array();

  /**
   * レコードセット。
   * @var array
   */
  protected $_recordSet = array();

  /**
   * 全件数。
   * @var int
   */
  protected $_maxRow = 0;

  /**
   * ページ辺りの最大表示件数。
   * @var int
   */
  protected $_pageInRecord;

  /**
   * 最大ページ件数。
   * @var int
   */
  protected $_pageLimit = 0;

  /**
   * 最大ページ件数を制限する際、$_maxRow を制限件数に合わせるかどうか。
   * @var bool
   */
  protected $_adjustCount = FALSE;

  /**
   * 表示開始位置。
   * @var bool
   */
  protected $_startPosition = FALSE;

  /**
   * 表示終了位置。
   * @var bool
   */
  protected $_endPosition = FALSE;

  /**
   * 前ページのインデックス。
   * @var bool
   */
  protected $_previousPage = FALSE;

  /**
   * 現在のページのインデックス。
   * @var bool
   */
  protected $_currentPage = FALSE;

  /**
   * 次ページのインデックス。
   * @var bool
   */
  protected $_nextPage = FALSE;

  /**
   * 最終ページのインデックス。
   * @var bool
   */
  protected $_lastPage = FALSE;

  /**
   * GET パラメータに含まれるソートパラメータ。
   * @var array
   */
  protected $_requestSort = array();

  /**
   * ソートリスト。
   * array(array('対象キー' => Delta_Pager::SORT_ASCENDING|Delta_Pager::SORT_DESCENDING), ...);
   * @var array
   */
  protected $_orders = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function __construct()
  {
    $this->_request = Delta_FrontController::getInstance()->getRequest();
    $this->_actionName = $this->_request->getRoute()->getForwardStack()->getLast()->getActionName();

    $this->_cipher = new Delta_BlowfishCipher();
    $this->_cipher->setInitializationVector('pager');

    // リクエストパラメータに昇順指定が含まれる場合
    $key = $this->_request->getParameter($this->_ascendingKey);

    if ($key !== NULL) {
      $this->setRequestSort($key, self::SORT_ASCENDING);

    } else {
      // リクエストパラメータに降順指定が含まれる場合
      $key = $this->_request->getParameter($this->_descendingKey);

      if ($key !== NULL) {
        $this->setRequestSort($key, self::SORT_DESCENDING);
      }
    }
  }

  /**
   * ページ ID を識別するリクエストパラメータキーを設定します。
   *
   * @param string $pageKey ページ ID を識別するリクエストパラメータキー。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPagerKey($pagerKey)
  {
    $this->_pagerKey = $pagerKey;
  }

  /**
   * 昇順ソートを識別するリクエストパラメータキーを設定します。
   *
   * @param string $ascendingKey 昇順ソートを識別するリクエストパラメータキー。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAscendingKey($ascendingKey)
  {
    $this->_ascendingKey = $ascendingKey;
  }

  /**
   * 降順ソートを識別するリクエストパラメータキーを設定します。
   *
   * @param string $ascendingKey 降順ソートを識別するリクエストパラメータキー。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setDescendingKey($descendingKey)
  {
    $this->_descendingKey = $descendingKey;
  }

  /**
   * Delta_Pager を実装したクラスのインスタンスを生成します。
   *
   * @return Delta_Pager Delta_Pager を実装したクラスのインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    $className = get_called_class();

    if (self::$_instance === NULL) {
      self::$_instance = new $className;
    }

    return self::$_instance;
  }

  /**
   * OFFSET_NONE 有効時に設定される値
   *   - currentPage
   *   - startPosition
   *   - lastPage
   *   - nextPage
   *   - endPage
   *
   * OFFSET_NONE 無効時に設定される値
   *   - currentPage
   *   - startPosition
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function bindPageInRecord($pageInRecord)
  {
    $this->_pageInRecord = $pageInRecord;

    if ($pageInRecord == self::OFFSET_NONE) {
      $this->_currentPage = 1;
      $this->_startPosition = 1;
      $this->_lastPage = 1;

      $this->_nextPage = FALSE;
      $this->_previousPage = FALSE;

    } else {
      $currentPage = $this->_request->getParameter($this->_pagerKey);

      if (!is_numeric($currentPage) || $currentPage < 1) {
        $currentPage = 1;
      } else {
        $currentPage = $currentPage;
      }

      $this->_currentPage = $currentPage;
      $this->_startPosition = ($currentPage - 1) * $pageInRecord + 1;
    }
  }

  /**
   * 表示する最大ページ数に制限を設定します。
   * このメソッドは、{@link fetch()} メソッドを実行するよりも前にコールする必要があります。
   *
   * @param int $pageLimit 制限ページ数。
   * @param bool $adjustCount 総レコード数の表示を制限ページ数に合わせる場合は TRUE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setPageLimit($pageLimit, $adjustCount = FALSE)
  {
    $this->_pageLimit = $pageLimit;
    $this->_adjustCount = $adjustCount;
  }

  /**
   * 現在のページに割り当てるレコードセットを設定します。
   *
   * @param array $recordSet 割り当てるレコードセット。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setRecordSet(array $recordSet)
  {
    $this->_recordSet = $recordSet;
  }

  /**
   * 現在のページに割り当てられているレコードセットを取得します。
   *
   * @return array レコードセットを返します。{@link fetch()} メソッドがコールされていない場合は空の配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRecordSet()
  {
    return $this->_recordSet;
  }

  /**
   * {@link OFFSET_NONE} 有効時に設定される値
   *   - endPosition
   *
   * {@link OFFSET_NONE} 無効時に設定される値
   *   - endPosition
   *   - lastPage
   *   - nextPage
   *   - previousPage
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function bindPosition($maxRow)
  {
    $this->_maxRow = $maxRow;

    if (!$maxRow) {
      return;
    }

    $pageInRecord = $this->_pageInRecord;

    if ($pageInRecord == self::OFFSET_NONE) {
      $this->_endPosition = $maxRow;

    } else {
      // データ取得完了位置の取得
      $this->_endPosition = $this->_startPosition + $pageInRecord - 1;

      if ($maxRow < $this->_endPosition) {
        $this->_endPosition = $maxRow;
      }

      // 最終ページの取得
      $this->_lastPage = Delta_NumberUtils::roundUp($maxRow / $pageInRecord, 0);
      $pageLimit = $this->_pageLimit;

      if ($pageLimit && $this->_lastPage > $pageLimit) {
        $this->_lastPage = $pageLimit;
      }

      // 不正なページパラメータ対策
      if ($maxRow < $this->_startPosition || ($pageLimit && $pageLimit < $this->_currentPage)) {
        $this->_currentPage = 1;
        $this->_startPosition = 1;
        $this->_endPosition = $pageInRecord;
      }

      // 次のページが存在するか
      if ($this->_currentPage < $this->_lastPage) {
        $this->_nextPage = $this->_currentPage + 1;
      }

      // 表示中のページが 1 ページ目以降であれば previous ボタンを有効化
      if ($this->_currentPage > 1) {
        $this->_previousPage = $this->_currentPage - 1;
      }
    }
  }

  /**
   * ナビゲーションリンク (「次ページ」や「前ページ」へのリンク) にクエリパラメータを追加します。
   *
   * @param string $name 追加するパラメータ名。
   * @param mixed $value パラメータの値。文字列、または配列での指定が可能。
   *   未指定時は name パラメータにマッチするフォームの値を自動的に格納します。
   *   値が NULL、または空文字の場合はパラメータを追加しません。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addQueryData($name, $value = NULL)
  {
    if ($value === NULL) {
      $value = $this->_request->getParameter($name);
    }

    $this->_queries[$name] = $value;
  }

  /**
   * ナビゲーションリンクのクエリパラメータに、フォームから取得した全ての値を追加します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setQueryDataFromForm()
  {
    // @todo 2.0
    exit;
    $fields = Delta_ActionForm::getInstance()->getFields();

    unset($fields[$this->_pagerKey]);

    if (sizeof($this->_requestSort)) {
      list($key, $type) = each($this->_requestSort);

      if ($type == self::SORT_ASCENDING) {
        $key = $this->_ascendingKey;
      } else {
        $key = $this->_descendingKey;
      }

      unset($fields[$key]);
    }

    foreach ($fields as $name => $value) {
      $this->addQueryData($name, $value);
    }
  }

  /**
   * ナビゲーションリンク用の基底アクションを設定します。
   * 基底アクションが未設定の場合は、最後に実行されたアクションがパスに用いられます。
   *
   * @param string $actionName リンクパスに用いる基底アクション名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setActionName($actionName)
  {
    $this->_actionName = $actionName;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function setRequestSort($encryptKey, $type)
  {
    $key = $this->_cipher->decrypt($encryptKey);

    $this->_requestSort[$key] = $type;
    $this->_orders[] = array($key => $type);
  }

  /**
   * ページ分割されたレコードデータをビューから参照できるようページャヘルパを割り当てます。
   * このメソッドは、ページャの最後の処理 (通常は {@link fetch()} メソッドより後) としてコールする必要があります。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function assignView()
  {
    $array = Delta_Config::getHelpers()->get('pager')->toArray();
    $array['_pagerKey'] = $this->_pagerKey;
    $array['_ascendingKey'] = $this->_ascendingKey;
    $array['_descendingKey'] = $this->_descendingKey;
    $array['_recordSet'] = $this->_recordSet;
    $array['_actionName'] = $this->_actionName;
    $array['_lastPage'] = $this->_lastPage;
    $array['_maxRow'] = $this->_maxRow;
    $array['_startPosition'] = $this->_startPosition;
    $array['_endPosition'] = $this->_endPosition;
    $array['_previousPage'] = $this->_previousPage;
    $array['_nextPage'] = $this->_nextPage;
    $array['_currentPage'] = $this->_currentPage;
    $array['_queries'] = $this->_queries;
    $array['_cipher'] = $this->_cipher;
    $array['_orders'] = array();

    if (sizeof($this->_requestSort)) {
      list($name, $order) = each($this->_requestSort);
      $array['_orders'][$name] = $order;

    } else {
      foreach ($this->_orders as $priority => $values) {
        list($name, $order) = each($values);
        $array['_orders'][$name] = $order;
      }
    }

    $array['_actionName'] = $this->_actionName;
    $array['bind'] = TRUE;

    $view = Delta_FrontController::getInstance()->getResponse()->getView();
    $view->getHelperManager()->addHelper('pager', $array);
  }
}
