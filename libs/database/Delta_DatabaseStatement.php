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
 * データベースステートメントを実行するクラスです。
 * ステートメントは {@link Delta_DatabaseConnection::createStatement()} メソッドで作成することができます。
 *
 * <code>
 * $conn = $this->getDatabase()->getConnection();
 *
 * // 結果セットを取得する
 * $query = 'SELECT manager_id, manager_name FROM managers';
 * $stmt = $conn->createStatement($query);
 * $resultSet = $stmt->executeQuery();
 *
 * // プリペアードステートメントを使う (名前付きプレースホルダ)
 * $query = 'INSERT INTO managers(manager_id, manager_name) VALUES(:manager_id, :manager_name)';
 * $stmt = $conn->createStatement($query);
 * $stmt->bindParam(':manager_id', $managerId);
 * $stmt->bindParam(':manager_name', $managerName);
 * $affectedCount = $stmt->execute($bindings);
 *
 * // プリペアードステートメントを使う (疑問符プレースホルダ)
 * $query = 'INSERT INTO managers(manager_id, manager_name) VALUES(?, ?)';
 * $stmt = $conn->createStatement($query);
 * $stmt->bindParam(1, $managerId);
 * $stmt->bindParam(2, $managerName);
 * $affectedCount = $stmt->execute();
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 */

class Delta_DatabaseStatement extends Delta_Object
{
  /**
   * フェッチタイプ定数。
   * ステートメント実行結果のレコードセットをクラスにマッピングする。
   */
  const FETCH_TYPE_CLASS = 1;

  /**
   * フェッチタイプ定数。
   * ステートメント実行結果のレコードセットを配列にマッピングする。
   */
  const FETCH_TYPE_ASSOC = 2;

  /**
   * 結果セットクラス名。
   * @var string
   */
  protected $_resultSetClassName = 'Delta_DatabaseResultSet';

  /**
   * データベースコネクションオブジェクト。
   * @var Delta_DatabaseConnection
   */
  private $_connection;

  /**
   * 実行クエリ。
   * @var string
   */
  private $_query;

  /**
   * フェッチタイプ。
   * @var int
   */
  private $_fetchType = self::FETCH_TYPE_CLASS;

  /**
   * マッピングクラス名。
   * @var string
   */
  private $_mappingClassName = 'Delta_RecordObject';

  /**
   * プリペアードステートメントオブジェクト。
   * @var PDOStatement
   */
  private $_statement;

  /**
   * バインド変数リスト。
   * @var array
   */
  private $_bindVariables = array();

  /**
   * ステートメントの有効状態。
   * @var bool
   */
  private $_isActive = TRUE;

  /**
   * コンストラクタ。
   *
   * @param Delta_DatabaseConnection コネクションオブジェクト。
   * @param string $query 実行クエリ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_DatabaseConnection $connection, $query)
  {
    $this->_connection = $connection;
    $this->_query = $query;
  }

  /**
   * 実行クエリを文字列形式で取得します。
   *
   * @return string 実行するクエリを文字列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getQueryString()
  {
    return $this->_query;
  }

  /**
   * ステートメントに変数をバインドします。
   * このメソッドの実装は、{@link PDO::bindValue()} に依存しています。
   *
   * @param string $name バインド変数名。
   *   名前付きプレースホルダの場合は :name 形式、疑問符プレースホルダを使用する場合は 1 から始まるパラメータの位置を指定。
   * @param mixed $value バインド変数値。
   * @param int $dataType データタイプ。PDO::PARAM_* 定数を指定可能。
   * @return bool バインドに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bindValue($name, $value, $dataType = PDO::PARAM_STR)
  {
    $result = FALSE;
    $value = $this->convertDataType($value, $dataType);

    if ($this->_statement === NULL) {
      $this->_statement = $this->_connection->getAdapter()->prepare($this->_query);
    }

    if ($this->_statement->bindValue($name, $value, $dataType)) {
      $result = TRUE;
      $this->_bindVariables[$name] = $value;
    }

    return $result;
  }

  /**
   * ステートメントに変数をバインドします。
   * このメソッドの実装は、{@link PDO::bindParam()} に依存しています。
   *
   * @param string $name バインド変数名。
   *   名前付きプレースホルダの場合は :name 形式、疑問符プレースホルダを使用する場合は 1 から始まるパラメータの位置を指定。
   * @param mixed $value バインド変数値。
   * @param int $dataType データタイプ。PDO::PARAM_* 定数を指定可能。
   * @param int $length データ長。
   * @return bool バインドに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bindParam($name, &$value, $dataType = PDO::PARAM_STR, $length = NULL)
  {
    $result = FALSE;
    $value = $this->convertDataType($value, $dataType);

    if ($this->_statement === NULL) {
      $this->_statement = $this->_connection->getAdapter()->prepare($this->_query);
    }

    if ($this->_statement->bindParam($name, $value, $dataType, $length)) {
      $this->_bindVariables[$name] = $value;
      $result = TRUE;
    }

    return $result;
  }

  /**
   * データ型を PDO が提供する型に変換します。
   *
   * @param mixed $value 変換対象のデータ。
   * @param int $dataType PDO::PARAM_* 定数。
   * @return mixed データ型変換後の値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function convertDataType($value, $dataType = PDO::PARAM_STR)
  {
    if ($value !== NULL) {
      switch ($dataType) {
        case PDO::PARAM_BOOL:
          $value = (boolean) $value;
          break;

        case PDO::PARAM_NULL:
          $value = NULL;
          break;

        case PDO::PARAM_INT:
          $value = (integer) $value;
          break;

        case PDO::PARAM_STR:
          $value = (string) $value;
          break;
      }
    }

    return $value;
  }

  /**
   * ステートメントに対するフェッチモードを設定します。
   * デフォルトのフェッチタイプは {@link FETCH_TYPE_CLASS} です。
   *
   * @param int $fetchType フェッチタイプ。FETCH_TYPE_* 定数を指定。
   * @param string $mappingClassName フェッチしたデータセットを格納するマッピングクラス。(fetchType が FETCH_CLASS の場合に使用)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFetchMode($fetchType, $mappingClassName = 'Delta_RecordObject')
  {
    $this->_fetchType = $fetchType;
    $this->_mappingClassName = $mappingClassName;
  }

  /**
   * 実行形式のクエリ (INSERT、UPDATE 等) を実行します。
   * プリペアード形式のステートメントを実行する場合、{@link bindValue()}、{@link bindParam()}、あるいは execute() の引数にバインド変数を設定する必要があります。
   *
   * @param array $bindValues バインド変数の配列。
   *   名前付きプレースホルダの場合は array(':{name}' => '{value}') 形式、疑問符プレースホルダを使用する場合は array('{value1}', '{value2}'...) 形式でパラメータを指定します。
   * @return int 実行したクエリによって作用したレコード数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function execute(array $bindValues = array())
  {
    return $this->executeStatement($bindValues, TRUE);
  }

  /**
   * 参照形式のクエリ (SELECT 等) を実行します。
   * プリペアード形式のステートメントを実行する場合、{@link bindValue()}、{@link bindParam()}、あるいは execute() の引数にバインド変数を設定する必要があります。
   *
   * @param array $bindValues バインド変数の配列。
   *   名前付きプレースホルダの場合は array(':{name}' => '{value}') 形式、疑問符プレースホルダを使用する場合は array('{value1}', '{value2}'...) 形式でパラメータを指定します。
   * @return Delta_DatabaseResultSet クエリを実行した結果のレコードセットを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function executeQuery(array $bindValues = array())
  {
    return $this->executeStatement($bindValues, FALSE);
  }

  /**
   * クエリを実行します。
   *
   * @param array $bindValues {@link execute()}、{@link executeQuery()} メソッドを参照。
   * @param bool $returnAffectedCount 実行形式のクエリは TRUE、参照形式のクエリは FALSE を指定。
   * @return mixed $returnAffectedCount が TRUE の場合は作用したレコード数、FALSE の場合は Delta_DatabaseResultSet を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function executeStatement(array $bindValues, $returnAffectedCount)
  {
    // ステートメントにバインド変数が設定されているかチェック
    $hasBindVariables = FALSE;

    if (sizeof($bindValues)) {
      $hasBindVariables = TRUE;
      $this->_bindVariables = $bindValues;

    } else if (sizeof($this->_bindVariables)) {
      $hasBindVariables = TRUE;
    }

    // クエリ実行関数
    $execute = function($prepare, $bindValues) {
      if ($this->_statement === NULL) {
        $this->_statement = $this->_connection->getAdapter()->prepare($this->_query);
      }

      // バインド変数がセットされてる場合はプリペアードステートメントを実行
      if ($prepare) {
        if (sizeof($bindValues)) {
          $this->_statement->execute($bindValues);
        } else {
          $this->_statement->execute();
        }

      // バインド変数を持たない場合は PDO::query() を実行
      } else {
        $this->_statement = $this->_connection->getAdapter()->query($this->_query);
      }
    };

    // プロファイラが有効な場合、クエリの実行時間を計測する
    $container = Delta_DIContainerFactory::getContainer();
    $profiler = $container->getComponent('database')->getProfiler();

    if ($profiler->isActive()) {
      $processTime = $profiler->run($execute, array($hasBindVariables, $bindValues));
      $dsn = $this->_connection->getDSN();

      if ($hasBindVariables) {
        $profiler->addPreparedStatement($dsn, $this->_query, $this->_bindVariables, $processTime);
      } else {
        $profiler->addStatement($dsn, $this->_query, $processTime);
      }

    } else {
      $execute($hasBindVariables, $bindValues);
    }

    // 実行クエリの戻り値
    if ($returnAffectedCount) {
      $result = $this->_statement->rowCount();

    // 参照クエリの戻り値
    } else {
      if ($this->_fetchType == self::FETCH_TYPE_CLASS) {
        $nativeType = PDO::FETCH_CLASS;

        // PDO::FETCH_PROPS_LATE を指定することで、マッピングクラスのコンストラクタをコールした後に __set() メソッドがコールされる
        $this->_statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $this->_mappingClassName);

      } else {
        $this->_statement->setFetchMode(PDO::FETCH_ASSOC);
        $nativeType = PDO::FETCH_ASSOC;
      }

      $result = new $this->_resultSetClassName($this->_statement, $nativeType);
    }

    return $result;
  }

  /**
   * 最後に挿入したレコードの ID、またはシーケンスの値を取得します。
   * <strong>このメソッドは {@link PDO::lastInsertId()} の実装に依存しています。
   * 従ってデータベースドライバによっては、一貫性のある結果を返さない場合がある点に注意して下さい。</strong>
   *
   * @param string $name 値が返されるべきシーケンスオブジェクト名。データベースドライバによっては指定する必要があります。
   * @return int 最後に挿入したレコードの ID、またはシーケンスの値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLastInsertId($name = NULL)
  {
    return $this->_connection->getAdapter()->lastInsertId($name);
  }

  /**
   * プリペアードステートメントにバインド変数を展開した文字列形式のクエリを生成します。
   *
   * @return string バインド変数を展開したクエリ文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getExpandBindingQuery()
  {
    $parser = new Delta_SQLPreparedStatementParser($this->_query);
    $parser->bindVariables($this->_bindVariables);

    return $parser->buildExpandBindingQuery();
  }

  /**
   * ステートメントが有効な状態にあるかどうかチェックします。
   *
   * @return bool ステートメントが有効な場合は TRUE、{@link close() メソッドによりステートメントが閉じられている場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActive()
  {
    return $this->_isActive;
  }

  /**
   * コネクションオブジェクトを取得します。
   *
   * @return Delta_DatabaseConnection コネクションオブジェクトを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnection()
  {
    return $this->_connection;
  }

  /**
   * ステートメントを閉じます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $this->_statement->closeCursor();
    $this->_bindVariables = array();
    $this->_isActive = FALSE;
  }
}
