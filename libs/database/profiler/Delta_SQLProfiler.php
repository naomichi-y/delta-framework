<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.profiler
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.profiler
 */

class Delta_SQLProfiler extends Delta_Object
{
  /**
   * プロファイラの有効状態。
   * @var bool
   */
  private $_isActive = FALSE;

  /**
   * 統計リスト。
   * @var array
   */
  private $_reports = array();

  /**
   * SELECT クエリ実行回数
   * @var int
   */
  private $_selectCount = 0;

  /**
   * INSERT クエリ実行回数
   * @var int
   */
  private $_insertCount = 0;

  /**
   * UPDATE クエリ実行回数
   * @var int
   */
  private $_updateCount = 0;

  /**
   * DELETE クエリ実行回数
   * @var int
   */
  private $_deleteCount = 0;

  /**
   * その他のクエリ実行回数
   * @var int
   */
  private $_otherCount = 0;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * Delta_SQLProfiler のインスタンスを取得します。
   *
   * @return Delta_SQLProfiler Delta_SQLProfiler のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_SQLProfiler();
      $instance->_isActive = Delta_DebugUtils::isDebug();
    }

    return $instance;
  }

  /**
   * プロファイラが有効な状態にあるかどうかチェックします。
   *
   * @return bool プロファイラが有効な場合は TRUE、無効な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActive()
  {
    $result = FALSE;

    if ($this->_isActive) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * プロファイラを開始します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function start()
  {
    $this->_isActive = TRUE;
  }

  /**
   * プロファイラを停止します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function stop()
  {
    $this->_isActive = FALSE;
  }

  /**
   * コールバック関数を実行し、処理にかかった時間を取得します。
   *
   * @param mixed $callback 実行するコールバック関数。
   * @param array $arguments コールバック関数に渡す引数。
   * @param mixed $result コールバック関数の戻り値が格納されます。
   * @return float 処理にかかった時間を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function run($callback, array $arguments = array(), &$result = NULL)
  {
    $startTime = microtime(TRUE);
    $result = call_user_func_array($callback, $arguments);
    $endTime = microtime(TRUE);

    $processTime = $endTime - $startTime;
    $processTime = number_format($processTime, 6);

    return $processTime;
  }

  /**
   * プロファイラにステートメントを追加します。
   *
   * @param string $dsn データソース名。
   * @param string $statement プロファイラに追加するステートメント。
   * @param int $time statement を実行するのにかかった時間。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addStatement($dsn, $statement, $time)
  {
    $this->_reports[] = $this->buildStatementReport($dsn, $statement, $time);
  }

  /**
   * プロファイラにプリペアードステートメントを追加します。
   *
   * @param string $dsn データソース名。
   * @param string $preparedStatement プロファイラに追加するプリペアードステートメント。
   * @param array $variables プリペアードステートメントに割り当てるバインド変数のリスト。
   * @param int $time statement を実行するのにかかった時間。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addPreparedStatement($dsn, $preparedStatement, array $variables, $time)
  {
    $parser = new Delta_SQLPreparedStatementParser($preparedStatement);
    $parser->bindVariables($variables);
    $rawQueryString = $parser->buildExpandBindingQuery();

    $report = $this->buildStatementReport($dsn, $rawQueryString, $time);
    $report->preparedStatement = $preparedStatement;
    $report->statementHash = hash('md5', $preparedStatement);

    $this->_reports[] = $report;
  }

  /**
   * @param string $dsn
   * @param string $statement
   * @param float $time
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildStatementReport($dsn, $statement, $time)
  {
    $report = new Delta_SQLProfilerReport();
    $report->dsn = $dsn;

    if (Delta_BootLoader::isBootTypeWeb()) {
      $route = Delta_FrontController::getInstance()->getRequest()->getRoute();

      $report->moduleName = $route->getModuleName();
      $report->actionName = $route->getForwardStack()->getLast()->getActionName();

    } else if (Delta_BootLoader::isBootTypeConsole()) {
      $console = Delta_Console::getInstance();
      $report->commandName = $console->getCommandName();
    }

    if (stripos($statement, 'SELECT') !== FALSE) {
      $statementType = Delta_SQLProfilerReport::STATEMENT_TYPE_SELECT;
      $this->_selectCount++;

    } else if (stripos($statement, 'INSERT') !== FALSE) {
      $statementType = Delta_SQLProfilerReport::STATEMENT_TYPE_INSERT;
      $this->_insertCount++;

    } else if (stripos($statement, 'UPDATE') !== FALSE) {
      $statementType = Delta_SQLProfilerReport::STATEMENT_TYPE_UPDATE;
      $this->_updateCount++;

    } else if (stripos($statement, 'DELETE') !== FALSE) {
      $statementType = Delta_SQLProfilerReport::STATEMENT_TYPE_DELETE;
      $this->_deleteCount++;

    } else {
      $statementType = Delta_SQLProfilerReport::STATEMENT_TYPE_OTHER;
      $this->_otherCount++;
    }

    $report->statementType = $statementType;
    $report->statement = $statement;
    $report->statementHash = hash('md5', $statement);
    $report->time = $time;

    $backtrace = debug_backtrace();

    foreach ($backtrace as $index =>  $trace) {
      if (isset($trace['class']) && substr($trace['class'], 0, 6) !== 'Delta_') {
        break;
      }
    }

    if (isset($backtrace[$index]['class'])) {
      $report->className = $backtrace[$index]['class'];
    }

    $report->fileName = $backtrace[$index - 1]['file'];
    $report->methodName = $backtrace[$index]['function'];
    $report->line = $backtrace[$index - 1]['line'];

    return $report;
  }

  /**
   * {@link Delta_PerformanceListener} が有効な場合、全ての SQL の実行ログを取得します。
   *
   * @return array SQL の実行結果を {@link Delta_SQLProfilerReport} オブジェクトの配列で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getReports()
  {
    return $this->_reports;
  }

  /**
   * SELECT クエリの実行回数を取得します。
   *
   * @return int SELECT クエリの実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getSelectCount()
  {
    return $this->_selectCount;
  }

  /**
   * INSERT クエリの実行回数を取得します。
   *
   * @return int INSERT クエリの実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getInsertCount()
  {
    return $this->_insertCount;
  }

  /**
   * UPDATE クエリの実行回数を取得します。
   *
   * @return int UPDATE クエリの実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getUpdateCount()
  {
    return $this->_updateCount;
  }

  /**
   * DELETE クエリの実行回数を取得します。
   *
   * @return int DELETE クエリの実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDeleteCount()
  {
    return $this->_deleteCount;
  }

  /**
   * CRUD (INSERT、SELECT、UPDATE、DELETE) 以外のクエリの実行回数を取得します。
   *
   * @return int CRUD 以外のクエリの実行回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getOtherCount()
  {
    return $this->_otherCount;
  }

  /**
   * プロファイリングされた情報を全て破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_reports = array();

    $this->_selectCount = 0;
    $this->_insertCount = 0;
    $this->_updateCount = 0;
    $this->_deleteCount = 0;
    $this->_otherCount = 0;
  }
}
