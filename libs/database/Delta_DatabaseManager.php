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
 * データベースの接続情報を管理するマネージャクラスです。
 *
 * このクラスは 'database' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_WebApplication::getDatabase()} からインスタンスを取得することができます。
 *
 * アプリケーション属性:
 * <code>
 * # データベース設定
 * database:
 *   # 接続先の名前空間。{@link Delta_DatabaseManager::getConnection()} メソッドで使用される標準の名前空間は {@link Delta_DatabaseManager::DEFAULT_DATASOURCE_ID}。
 *   {datasource}:
 *     # データソース名、または DSN。
 *     # (DSN については {@link PDO::__construct()} メソッドも参照)
 *     dsn: {string}
 *
 *     # 接続ユーザ名。
 *     user: {string}
 *
 *     # 接続パスワード。
 *     password: {string}
 *
 *     # 接続オプション。指定可能なオプションは {@link PDO::__construct()} メソッドの PDO 定数を参照。
 *     # ('PDO::' の指定は不要)
 *     options:
 *       #  永続接続 (既定値は TRUE)。
 *       ATTR_PERSISTENT: {bool}
 *
 *       # エラーレポート (固定値)。
 *       ATTR_ERRMODE: ERRMODE_EXCEPTION
 * </code>
 *
 * データベースコネクションの取得:
 * <code>
 * $database = $this->getDatabase();
 *
 * // 'default' データベースに接続
 * $conn = $database->getConnection();
 * $conn->createStatement($query);
 *
 * // 'slave' データベースに接続
 * $conn = $database->getConnection('slave');
 * $conn->getCommand()->getSlaveStatus();
 *
 * // 接続先データベースをパラメータで指定
 * $conn = $database->getConnectionWithConfig($dsn, $user, $password);
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database
 */

class Delta_DatabaseManager extends Delta_Object
{
  /**
   * データベース接続に使用するデフォルトの名前空間。
   * @var string
   */
  const DEFAULT_DATASOURCE_ID = 'default';

  /**
   * コネクションクラス名。
   * @var string
   */
  protected $_connectionClassName = 'Delta_DatabaseConnection';

  /**
   * トランザクションコントローラ。
   * @var Delta_DatabaseTransactionController
   */
  private $_transactionController;

  /**
   * 接続オプション。
   */
  private $_connectionOptions = array();

  /**
   * コネクションプール。
   * @var array
   */
  private $_connections = array();

  /**
   * 接続再試行回数。
   * @var int
   */
  private $_connectRetryMaxCount = 1;

  /**
   * 接続を再試行するまでの待ち時間。(単位はマイクロ秒)
   * @var int
   */
  private $_connectRetryWait = 500000;

  /**
   * プロファイラの有効状態。
   * @var bool
   */
  private $_isActiveProfiler = FALSE;

  /**
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * @since 1.2
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_DatabaseManager();
    }

    return $instance;
  }

  /**
   * トランザクションコントローラを設定します。
   *
   * @param Delta_DatabaseTransactionController $transactionController トランザクションコントローラ。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTransactionController(Delta_DatabaseTransactionController $transactionController)
  {
    $this->_transactionController = $transactionController;
  }

  /**
   * データベースマネージャが管理している全てのコネクションを取得します。
   *
   * @return array {@link Delta_DatabaseConnection コネクション} のリストを返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnections()
  {
    return $this->_connections;
  }

  /**
   * application.yml に定義されたデータベース接続情報を取得します。
   *
   * @param string $dataSourceId 参照対象のデータソース ID。
   * @return array データベースの接続情報を配列形式で返します。
   *   指定された名前空間の定義が見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDataSourceInfo($dataSourceId = self::DEFAULT_DATASOURCE_ID)
  {
    $key = sprintf('database.%s', $dataSourceId);
    $info = Delta_Config::getApplication()->getArray($key);

    return $info;
  }

  /**
   * データベースに接続する際のオプションを設定します。
   * このメソッドは {@link getConnection()} メソッドを実行する前に呼び出す必要があります。
   *
   * <code>
   * // 持続接続を無効にする
   * $this->getDatabase()->setConnectionOptions('default', array(PDO::ATTR_PERSISTENT => TRUE));
   * $conn = $this->getDatabase()->getConnection();
   * </code>
   * application.yml の 'database.{dataSourceId}.options' に同じ属性が定義されてる場合は、オプション値が上書きされます。
   * (options に指定していない値は、'database.{dataSourceId}.options' に定義された値が有効となります)
   *
   * @param string $dataSourceId データソース ID。
   * @param array $options 接続オプションのリスト。
   *   指定可能な値は {@link PDO::__construct()} の $driver_options で指定可能な値と同じ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setConnectionOptions($dataSourceId, array $options)
  {
    $this->_connectionOptions[$dataSourceId] = $options;
  }

  /**
   * データベース接続オブジェクトを取得します。
   * 特定の接続環境下でのみ接続オプションを変更したい場合は、{@link setConnectionOptions()} メソッドを利用して下さい。
   *
   * @param mixed $dataSourceId データソース ID (application.yml の 'database' 属性下で定義した ID)。
   *   未指定の場合は {@link Delta_DatabaseManager::DEFAULT_DATASOURCE_ID} の名前空間に指定された接続情報が使用される。
   * @throws Delta_ParseException データソースが読み込めない場合に発生。
   * @return Delta_DatabaseStatement Delta_DatabaseStatement のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnection($dataSourceId = self::DEFAULT_DATASOURCE_ID)
  {
    if (empty($this->_connections[$dataSourceId]) || !$this->_connections[$dataSourceId]->isActive()) {
      $key = sprintf('database.%s', $dataSourceId);
      $databaseConfig = Delta_Config::getApplication()->get($key);

      if ($databaseConfig === NULL) {
        $message = sprintf('"%s" attribute has not been defined in config/application.yml', $key);
        throw new Delta_ParseException($message);
      }

      // 接続情報の取得
      $dsn = $databaseConfig->getString('dsn');
      $user = $databaseConfig->getString('user');
      $password = $databaseConfig->getString('password');
      $options = $databaseConfig->getArray('options');

      // PDO 定数の解析
      $optionsArray = array();

      foreach ($options as $name => $value) {
        $const = sprintf('PDO::%s', $name);

        if (defined($const)) {
          $optionsArray[constant($const)] = $value;
        }
      }

      // 接続オプションのオーバーライド
      if (isset($this->_connectionOptions[$dataSourceId])) {
        foreach ($this->_connectionOptions[$dataSourceId] as $name => $value) {
          $optionsArray[$name] = $value;
        }
      }

      $connection = $this->createAdapter($dsn, $user, $password, $optionsArray);
      $connection->setDataSourceId($dataSourceId);

      $this->_connections[$dataSourceId] = $connection;
    }

    return $this->_connections[$dataSourceId];
  }

  /**
   * データベース接続オブジェクトを取得します。
   *
   * @param string $dsn データソース名、または DSN。
   * @param string $user 接続ユーザ名。
   * @param string $password 接続パスワード。
   * @param array $options 接続オプション。指定可能なオプションは {@link PDO::__construct()} メソッドを参照。
   * @return Delta_DatabaseStatement Delta_DatabaseStatement のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConnectionWithConfig($dsn,
    $user,
    $password,
    array $options = array(
      PDO::ATTR_PERSISTENT => TRUE,
    ))
  {
    $dataSourceId = serialize(sprintf('_%s%s', $dsn, $user));

    if (empty($this->_connections[$dataSourceId]) || !$this->_connections[$dataSourceId]->isActive()) {
      $connection = $this->createAdapter($dsn, $user, $password, $options);
      $connection->setDataSourceId($dataSourceId);

      $this->_connections[$dataSourceId] = $connection;
    }

    return $this->_connections[$dataSourceId];
  }

  /**
   * データベース接続アダプタを作成します。
   *
   * @param string $dsn データベース名、または DSN。
   * @param string $user 接続ユーザ名。
   * @param string $password 接続パスワード。
   * @param array $options 接続オプション。指定可能なオプションは {@link PDO::__construct()} メソッドを参照。
   * @return Delta_DatabaseStatement Delta_DatabaseStatement のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function createAdapter($dsn, $user, $password, array $options)
  {
    // エラー通知タイプの指定
    $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

    // 永続接続をデフォルトで有効にする
    if (!isset($options[PDO::ATTR_PERSISTENT])) {
      $options[PDO::ATTR_PERSISTENT] = TRUE;
    }

    // 接続開始
    $connection = NULL;
    $failCount = 0;

    while (TRUE) {
      try {
        $connection = new $this->_connectionClassName($dsn, $user, $password, $options);

        if ($this->_transactionController) {
          $connection->setTransactionController($this->_transactionController);
        }

        break;

      } catch (PDOException $e) {
        if ($failCount < $this->_connectRetryMaxCount) {
          usleep($this->_connectRetryWait);
          $failCount++;

          continue;
        }

        throw $e;
      }
    }

    $driverName = $connection->getDriverName();
    $this->connectionSetup($driverName, $connection->getAdapter());

    return $connection;
  }

  /**
   * データベースドライバ固有の接続処理を行います。
   * このメソッドは、{@link getConnection()}、あるいは {@link getConnectionWithConfig()} メソッドでコネクションオブジェクトが生成された直後にコールされます。
   *
   * @param string $driverName ドライバ名。
   * @param PDO $adapter アダプタオブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function connectionSetup($driverName, $adapter)
  {
    if ($driverName === 'mysql') {
      $adapter->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);
    }
  }

  /**
   * 接続に失敗した場合のリトライ接続回数を設定します。(既定値は 1)
   *
   * @param int $connectRetryMaxCount 最大リトライ回数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setConnectRetryMaxCount($connectRetryMaxCount)
  {
    $this->_connectRetryMaxCount = $connectRetryMaxCount;
  }

  /**
   * 接続に失敗した場合の再接続待機時間を設定します。(既定値は 0,5 秒)
   *
   * @param float $connectRetryWait 再接続試行時の待ち時間。(1.0 を指定した場合は 1 秒待機)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setConnectRetryWait($connectRetryWait)
  {
    $this->_connectRetryWait = $connectRetryWait * 1000000;
  }

  /**
   * プロファイラを取得します。
   *
   * @return Delta_SQLProfiler Delta_SQLProfiler のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getProfiler()
  {
    return Delta_SQLProfiler::getInstance();
  }

  /**
   * Delta_DatabaseManager が管理している全ての接続情報を開放します。
   * マネージャが有効なコネクションを保持している場合、全てのコネクションは切断される点に注意して下さい。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function closeAll()
  {
    foreach ($this->_connections as $dataSourceId => $connection) {
      if ($connection->isActive()) {
        $connection->close();
      }

      unset($this->_connections[$dataSourceId]);
    }
  }
}

