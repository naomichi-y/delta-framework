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
 * データベースのコネクションオブジェクトです。
 * コネクションは {@link Delta_DatabaseManager::getConnection()}、あるいは {@link Delta_DatabaseManager::getConnectionWithConfig()} メソッドから生成することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 */

class Delta_DatabaseConnection extends Delta_Object
{
  /**
   * ステートメントクラス名。
   * @var string
   */
  protected $_statementClassName = 'Delta_DatabaseStatement';

  /**
   * データベースアダプタ。
   * @var PDO
   */
  private $_adapter;

  /**
   * DSN 情報。
   * @var string
   */
  private $_dsn;

  /**
   * データベースの名前空間。
   * @var string
   */
  private $_namespace;

  /**
   * トランザクションコントローラ。
   * @var Delta_DatabaseTransactionController
   */
  private $_transactionController;

  /**
   * トランザクションが有効状態にあるかどうか。
   * @var bool
   */
  private $_isActiveTransaction = FALSE;

  /**
   * コンストラクタ。
   *
   * @param string $dsn データソース、または DSN。
   * @param string $username 接続ユーザ名。
   * @param string $password 接続パスワード。
   * @param array $options 接続オプション。指定可能な属性は {@link PDO::__construct()} メソッドを参照。
   * @throws PDOException データベース接続に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($dsn, $user, $password, array $options = array())
  {
    $this->_adapter = new PDO($dsn, $user, $password, $options);
    $this->_dsn = $dsn;
  }

  /**
   * トランザクションコントローラを設定します。
   *
   * @param Delta_DatabaseTransactionController トランザクションコントローラ。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTransactionController(Delta_DatabaseTransactionController $transactionController)
  {
    $this->_transactionController = $transactionController;
  }

  /**
   * トランザクションコントローラを取得します。
   *
   * @return Delta_DatabaseTransactionController トランザクションコントローラを返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTransactionController()
  {
    return $this->_transactionController;
  }

  /**
   * トランザクションコントローラが有効な状態にあるかどうかチェックします。
   *
   * @return bool トランザクションコントローラが有効な場合は TRUE、無効な場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActiveTransactionController()
  {
    if ($this->_transactionController === NULL) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * コネクションの名前空間を設定します。
   * {@link Delta_DatabaseManager::getConnection()} メソッドでコネクション生成時に、適切な名前が割り当てられます。
   *
   * @param string $namespace コネクションの名前空間を設定します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setNamespace($namespace)
  {
    $this->_namespace = $namespace;
  }

  /**
   * コネクションの名前空間を取得します。
   *
   * @return string コネクションの名前空間を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getNamespace()
  {
    return $this->_namespace;
  }

  /**
   * データソースを取得します。
   *
   * @return string データソースを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDSN()
  {
    return $this->_dsn;
  }

  /**
   * コネクションが有効な状態にあるかどうかチェックします。
   *
   * @return bool コネクションが有効な状態であれば TRUE、無効 (接続が閉じられている場合) であれば FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActive()
  {
    $result = FALSE;

    if ($this->_adapter) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 自動コミットモードを設定します。
   * デフォルトのコミットモードは、データベースの種類やデータベース設定に依存します。
   *
   * @param bool $autoCommit 自動コミットを有効にする場合は TRUE、無効にする場合は FALSE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAutoCommit($autoCommit)
  {
    $this->getAdapter()->setAttribute(PDO::ATTR_AUTOCOMMIT, $autoCommit);
  }

  /**
   * データベースアダプタを取得します。
   *
   * @return PDO データベースアダプタを返します。
   * @throws RuntimeException コネクションが閉じられてる場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAdapter()
  {
    if ($this->_adapter === NULL) {
      $message = sprintf('Database connection is closed. [%s]', $this->_dsn);
      throw new RuntimeException($message);
    }

    return $this->_adapter;
  }

  /**
   * クエリ用の文字列をクォートします。
   * {@link createStatement()} メソッドでステートメントを構築する場合、クエリデータのクォート処理は必要ありません。
   * このメソッドは {@link PDO::quote()} の実装に依存しています。
   *
   * @param string $string 対象となる文字列。
   * @param int $dataType クォートスタイルを適用するデータ型。PDO::PARAM_* 定数を指定可能。
   * @return string クォートされた文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function quote($string, $dataType = PDO::PARAM_STR)
  {
    return $this->getAdapter()->quote($string, $dataType);
  }

  /**
   * 新しいステートメントを作成します。
   *
   * @param string $query 実行するクエリ。データは {@link quote()} メソッドで適切にエスケープする必要があります。
   * @return Delta_DatabaseStatement Delta_DatabaseStatement のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createStatement($query)
  {
    return new $this->_statementClassName($this, $query);
  }

  /**
   * 参照クエリ (SELECT) を実行します。
   * 実行形式のクエリ (INSERT、UPDATE 等) やプリペアード形式のステートメントを実行する場合は、{@link createStatement()} メソッドを使用して下さい。
   *
   * @param string $query 実行するクエリ。
   * @return Delta_DatabaseResultSet Delta_DatabaseResultSet のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rawQuery($query)
  {
    $statement = $this->createStatement($query);
    $resultSet = $statement->executeQuery();

    return $resultSet;
  }

  /**
   * 最後に挿入したレコードの ID、またはシーケンスの値を取得します。
   * <strong>このメソッドは {@link PDO::lastInsertId()} の実装に依存しています。
   * データベースドライバによっては、一貫性のある結果を返さない場合がある点に注意して下さい。</strong>
   *
   * @param string $name 値が返されるべきシーケンスオブジェクト名。データベースドライバによっては指定する必要があります。
   * @return int 最後に挿入したレコードの ID、またはシーケンスの値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLastInsertId($name = NULL)
  {
    return $this->getAdapter()->lastInsertId($name);
  }

  /**
   * トランザクションを開始します。
   * スクリプト内で明示的に {@link commit()} メソッドがコールされない場合は、自動的にロールバック処理が実行されます。
   *
   * @return bool トランザクションが開始した場合に TRUE、失敗した場合 (トランザクション内でメソッドを再コールした場合を含む) に FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function beginTransaction()
  {
    $result = FALSE;

    if (!$this->_isActiveTransaction) {
      $result = $this->getAdapter()->beginTransaction();
      $this->_isActiveTransaction = TRUE;
    }

    return $result;
  }

  /**
   * トランザクションが開始されているかチェックします。
   * <strong>このメソッドは、{@link beginTransaction()} メソッドで開始されたトランザクションのみ判定します。
   * ネイティブコードで書かれたトランザクションは判定基準とならない点に注意して下さい。</strong>
   *
   * @return bool トランザクションが開始されてる場合に TRUE、開始されてない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActiveTransaction()
  {
    return $this->_isActiveTransaction;
  }

  /**
   * 現在有効なトランザクションをコミットします。
   *
   * @return bool トランザクションのコミットに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function commit()
  {
    $result = FALSE;

    if ($this->isActiveTransaction()) {
      $this->_isActiveTransaction = FALSE;
      $result = $this->getAdapter()->commit();
    }

    return $result;
  }

  /**
   * 現在有効なトランザクションをロールバックします。
   *
   * @return bool トランザクションのロールバックに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rollBack()
  {
    $result = FALSE;

    if ($this->isActiveTransaction()) {
      $this->_isActiveTransaction = FALSE;
      $result = $this->getAdapter()->rollback();
    }

    return $result;
  }

  /**
   * 現在のコネクションで有効なデータベースドライバの名前を取得します。
   *
   * @return string データベースドライバの名前を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDriverName()
  {
    return $this->getAdapter()->getAttribute(PDO::ATTR_DRIVER_NAME);
  }

  /**
   * テーブルを操作するオペレータオブジェクトを取得します。
   *
   * @return Delta_DatabaseCommand データベースに対応するオペレータオブジェクトを返します。
   *   例えば MySQL を使用している場合は、{@link Delta_DatabaseMySQLCommand} オブジェクトを返します。
   * @throws Delta_UnsupportedException オペレータがデータベースドライバに対応していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCommand()
  {
    $command = NULL;
    $driverName = $this->getDriverName();

    switch ($driverName) {
      case 'mysql':
        $command = new Delta_DatabaseMySQLCommand($this);
        break;

      case 'pgsql':
        $command = new Delta_DatabasePostgreSQLCommand($this);
        break;

      default:
        $message = sprintf('Database driver is not supported. [%s]', $driverName);
        throw new Delta_UnsupportedException($message);

        break;
    }

    return $command;
  }

  /**
   * 現在有効なコネクションを閉じます。
   * データベースへの持続接続が有効な場合、実際には接続が閉じられず、メモリ領域が開放される点に注意して下さい。
   * {@link Delta_PDOConnector} で開いた全てのコネクションを閉じる場合は {@link Delta_DatabaseManager::closeAll()} メソッドを使用して下さい。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $this->_adapter = NULL;
    $this->_isActiveTransaction = FALSE;
  }
}
