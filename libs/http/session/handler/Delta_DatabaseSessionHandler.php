<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session.handler
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * HTTP セッションをデータベースで管理します。
 *
 * <i>セッションハンドラを有効にするには、あらかじめ "delta install-database-session" コマンドを実行し、データベースにセッションテーブルを作成>しておく必要があります。</i>
 * <i>現在のところ、MySQL 以外のデータベースドライバは動作をサポートしていません。</i>
 *
 * application.yml の設定例:
 * <code>
 * session:
 *   # セッションハンドラ
 *   handler:
 *     # セッションハンドラのクラス名 (固定)
 *     class: Delta_DatabaseSessionHandler
 *
 *     # セッションテーブルが配置されたデータソース ('database' 属性を参照)
 *     dataSource: default
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session.handler
 */

class Delta_DatabaseSessionHandler extends Delta_Object
{
  /**
   * @var Delta_DatabaseManager
   */
  private $_database;

  /**
   * @var string
   */
  private $_dataSourceId;

  /**
   * @var Delta_DatabaseConnection
   */
  private $_connection;

  /**
   * コンストラクタ。
   *
   * @param Delta_ParameterHolder $holder application.yml に定義されたハンドラ属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct(Delta_ParameterHolder $config)
  {
    $this->_database = Delta_DIContainerFactory::getContainer()->getComponent('database');
    $this->_dataSourceId = $config->get('dataSource', Delta_DatabaseManager::DEFAULT_DATASOURCE_ID);

    session_set_save_handler(
      array($this, 'open'),
      array($this, 'close'),
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc')
    );
  }

  /**
   * セッション管理をデータベースにハンドリングします。
   *
   * @param Delta_ParameterHolder $config セッションハンドラ属性。
   * @return Delta_DatabaseSessionHandler Delta_DatabaseSessionHandler のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function handler(Delta_ParameterHolder $config)
  {
    return new Delta_DatabaseSessionHandler($config);
  }

  /**
   * セッションストレージへの接続を行います。
   *
   * @param string $savePath セッションの保存パス。
   * @param string $sessionName セッション名。
   * @return bool セッションストレージへの接続に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws Delta_UnsupportedException ドライバがサポートされていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function open($savePath, $sessionName)
  {
    $this->_database->getProfiler()->stop();
    $this->_connection = $this->_database->getConnection($this->_dataSourceId);
    $driverName = $this->_connection->getDriverName();

    if ($driverName !== 'mysql') {
      $message = sprintf('Driver is not supported. [%s]', $driverName);
      throw new Delta_UnsupportedException($message);
    }

    return TRUE;
  }

  /**
   * セッションストレージへの接続を閉じます。
   * このメソッドはセッション操作が終了する際に実行されます。
   *
   * @return bool セッションが正常に閉じられた場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $result = FALSE;

    if ($this->_database) {
      $this->_database->getProfiler()->start();
      $result = TRUE;
    }

    return $result;
  }

  /**
   * セッションに格納されている値を取得します。
   *
   * @param string $sessionId セッション ID。
   * @return string セッションに格納されている値を返します。値が存在しない場合は空文字を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function read($sessionId)
  {
    $result = '';

    if ($this->_connection) {
      $query = 'SELECT session_data '
        .'FROM delta_sessions '
        .'WHERE session_id = :session_id';

      $stmt = $this->_connection->createStatement($query);
      $stmt->bindParam(':session_id', $sessionId);
      $resultSet = $stmt->executeQuery();

      if ($sessionData = $resultSet->readField(0)) {
        $result = $sessionData;
      }
    }

    return $result;
  }

  /**
   * セッションにデータを書き込みます。
   *
   * @param string $sessionId セッション ID。
   * @param mixed $sessionData 書き込むデータ。
   * @return bool 書き込みが成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($sessionId, $sessionData)
  {
    $result = FALSE;

    if ($this->_connection) {
      $query = 'REPLACE INTO delta_sessions(session_id, session_data, register_date, update_date) '
        .'VALUES(:session_id, :session_data, NOW(), NOW())';

      $stmt = $this->_connection->createStatement($query);
      $stmt->bindParam(':session_data', $sessionData);
      $stmt->bindParam(':session_id', $sessionId);
      $affectedRows = $stmt->execute();

      if ($affectedRows) {
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * セッションを破棄します。
   *
   * @param string $sessionId セッション ID。
   * @return bool セッションの破棄に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function destroy($sessionId)
  {
    $result = TRUE;

    if ($this->_connection) {
      $query = 'DELETE FROM delta_sessions '
        .'WHERE session_id = :session_id';

      $pstmt = $this->_connection->createStatement($query);
      $pstmt->bindParam(':session_id', $sessionId);
      $pstmt->execute();

      $result = TRUE;
    }

    return $result;
  }

  /**
   * ガベージコレクタを起動します。
   *
   * @param int $lifetime セッションの生存期間。単位は秒。
   * @return bool ガベージコレクタの起動に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function gc($lifetime)
  {
    $result = FALSE;

    if ($this->_connection) {
      $query = 'DELETE FROM delta_sessions '
        .'WHERE update_date < NOW() + \'- :lifetime secs\'';

      $pstmt = $this->_connection->createStatement($query);
      $pstmt->bindParam(':lifetime', $lifetime);
      $pstmt->execute();

      $result = TRUE;
    }

    return $result;
  }
}
