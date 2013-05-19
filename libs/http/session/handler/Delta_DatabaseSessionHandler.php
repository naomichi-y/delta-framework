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
 * セッションの管理をデータベースにハンドリングします。
 * この機能を有効にするには、あらかじめ "delta install-database-session" コマンドを実行し、データベースにセッションテーブルを作成しておく必要があります。
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
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_DatabaseSessionHandler($config);
    }

    return $instance;
  }

  /**
   * セッションストレージへの接続を行います。
   *
   * @param string $savePath セッションの保存パス。
   * @param string $sessionName セッション名。
   * @return bool セッションストレージへの接続に成功した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function open($savePath, $sessionName)
  {
    $this->_database->getProfiler()->stop();
    $this->_connection = $this->_database->getConnection($this->_dataSourceId);

    return TRUE;
  }

  /**
   * セッションストレージへの接続を閉じます。
   * このメソッドはセッション操作が終了する際に実行されます。
   *
   * @return bool セッション操作が正常に終了した場合は TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $this->_database->getProfiler()->start();

    return TRUE;
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
    $sql = 'SELECT session_data '
          .'FROM delta_sessions '
          .'WHERE session_id = :session_id';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindParam(':session_id', $sessionId);
    $resultSet = $stmt->executeQuery();

    if ($sessionData = $resultSet->readField(0)) {
      return $sessionData;
    }

    return '';
  }

  /**
   * セッションに値を書き込みます。
   * 通常はオブジェクトが破棄された後にコールされます。
   *
   * @param string $sessionId セッション ID。
   * @param mixed $sessionData 書き込むデータ。
   * @return bool 書き込みに成功したかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($sessionId, $sessionData)
  {
    $sql = 'SELECT COUNT(*) '
          .'FROM delta_sessions '
          .'WHERE session_id = :session_id';

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindParam(':session_id', $sessionId);
    $resultSet = $stmt->executeQuery();
    $count = $resultSet->readField(0);

    if ($count) {
      $sql = 'UPDATE delta_sessions '
            .'SET session_data = :session_data, '
            .'update_date = NOW() '
            .'WHERE session_id = :session_id';

    } else {
      $sql = 'INSERT INTO delta_sessions(session_id, session_data, register_date, update_date) '
            .'VALUES(:session_id, :session_data, NOW(), NOW())';
    }

    $stmt = $this->_connection->createStatement($sql);
    $stmt->bindParam(':session_data', $sessionData);
    $stmt->bindParam(':session_id', $sessionId);
    $stmt->execute();

    return TRUE;
  }

  /**
   * セッションを破棄します。
   *
   * @param string $sessionId セッション ID。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function destroy($sessionId)
  {
    $sql = 'DELETE FROM delta_sessions '
          .'WHERE session_id = :session_id';

    $pstmt = $this->_connection->createStatement($sql);
    $pstmt->bindParam(':session_id', $sessionId);
    $pstmt->execute();

    return TRUE;
  }

  /**
   * ガベージコレクタを起動します。
   *
   * @param int $lifetime セッションの生存期間。単位は秒。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function gc($lifetime)
  {
    $sql = 'DELETE FROM delta_sessions '
      .'WHERE update_date < NOW() + \'- :lifetime secs\'';

    $pstmt = $this->_connection->createStatement($sql);
    $pstmt->bindParam(':lifetime', $lifetime);
    $pstmt->execute();

    return TRUE;
  }
}
