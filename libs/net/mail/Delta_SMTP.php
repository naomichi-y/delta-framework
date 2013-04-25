<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * SMTP プロトコル経由でメールを送信するためのコマンドユーティリティです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net.mail
 */

class Delta_SMTP extends Delta_Object {
  /**
   * {@link Delta_Socket} オブジェクト。
   * @var Delta_Socket
   */
  private $_socket;

  /**
   * 接続先ホスト名。
   * @var string
   */
  private $_host;

  /**
   * SMTP 応答ステータス。
   * @var int
   */
  private $_status = -1;

  /**
   * 応答パラメータリスト。
   * @var array
   */
  private $_parameters = array();

  /**
   * 改行コード。
   * @var string
   */
  private $_lineFeed = "\r\n";

  /**
   * ESMTP が有効かどうか。
   * @var bool
   */
  private $_esmtp = TRUE;

  /**
   * コンストラクタ。
   *
   * @param string $host 接続先のホスト名。ホスト指定時は {@link connect()} メソッドによりホストへの接続が行われます。
   * @param int $port 接続先のポート番号。
   * @param int $timeout 接続タイムアウト秒の指定。
   * @throws Delta_ConnectException ホストへの接続に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($host = NULL, $port = 25, $timeout = 30)
  {
    $this->_socket = new Delta_Socket();
    $this->_host = $host;

    if ($host !== NULL) {
      $this->connect($host, $port, $timeout);
    }
  }

  /**
   * SMTP サーバへ接続します。
   *
   * @param string $host 接続先のホスト名。
   * @param int $port 接続先のポート番号。
   * @param int $timeout 接続タイムアウト秒の指定。
   * @throws Delta_ConnectException ホストへの接続に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function connect($host, $port = 25, $timeout = 30)
  {
    $this->_socket->connect($host, $port, $timeout);
    $this->_socket->setTimeout($timeout);

    $message = $this->_socket->readLine();
    $status = (int) substr($message, 0, 3);

    if ($status != 220) {
      $message = sprintf('Failure connect to %s:%s', $host, $port);
      throw new Delta_ConnectException($message);
    }

    // ESMTP をサポートしているかチェック
    $response = explode(' ', $message);

    if (strcasecmp(trim($response[2]), 'ESMTP') !== 0) {
      $this->_esmtp = FALSE;
    }
  }

  /**
   * サーバに EHLO コマンド (サポートしていない場合は HELO) を送信し、SMTP セッションを開始します。
   *
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendEhlo()
  {
    if ($this->_esmtp) {
      $this->sendCommand('EHLO', $this->_host);
    } else {
      $this->sendCommand('HELO', $this->_host);
    }

    $this->parseResponse();

    if ($this->_status != 250) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに MAIL FROM コマンドを送信し、メールトランザクションを開始します。
   * (このコマンド以前にサーバに渡された宛先やデータは消去されます)
   *
   * @param string $mailFrom エンベロープ上の送信者アドレス。
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendMailFrom($mailFrom)
  {
    $mailFrom = '<' . $mailFrom . '>';

    $this->sendCommand('MAIL FROM:', $mailFrom);
    $this->parseResponse();

    if ($this->_status != 250) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに RCPT TO コマンドを送信し、メールの受信者を設定します。
   * 複数の宛先に送信する場合は、{@link sendRcptTo()} メソッドを複数回コールする必要があります。
   * このメソッドは {@link sendMailFrom()} より後にしか利用することは出来ません。
   *
   * @param string $rcptTo メールの受信者アドレス。
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendRcptTo($rcptTo)
  {
    $rcptTo = '<' . $rcptTo . '>';

    $this->sendCommand('RCPT TO:', $rcptTo);
    $this->parseResponse();

    if ($this->_status != 250 && $this->_status != 251) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに DATA コマンドを送信し、メールヘッダとボディを設定します。
   *
   * @param string $data メッセージデータ。
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendData($data)
  {
    $this->sendCommand('DATA');
    $this->parseResponse();

    if ($this->_status == 354) {
      $data = preg_replace('/^\./m', '..', $data) . $this->_lineFeed . '.';

      $this->sendCommand($data);
      $this->parseResponse();

      if ($this->_status == 250) {
        return;
      }
    }

    throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
  }

  /**
   * サーバに QUIT コマンドを送信し、SMTP セッションを終了します。
   *
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendQuit()
  {
    $this->sendCommand('QUIT');
    $this->parseResponse();

    if ($this->_status != 221) {
      throw new Delta_ConnectException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに RSET コマンドを送信し、メールトランザクションをリセットします。
   * サーバはここまでに送られてきた送信者、受信者、データ等を全て破棄し、{@link sendHelo()} メソッドコール直後の状態に戻ります。
   *
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendRset()
  {
    $this->sendCommand('RSET');
    $this->parseResponse();

    if ($this->_status != 250) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに NOOP コマンドを送信し、応答が返ってくることを確認します。
   *
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendNoop()
  {
    $this->sendCommand('NOOP');
    $this->parseResponse();

    if ($this->_status != 250) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }
  }

  /**
   * サーバに HELP コマンドを送信し、コマンドのヘルプを取得します。
   *
   * @param string $command 調べるコマンド。未指定の場合は簡単なヘルプを返します。
   * @return string ヘルプを返します。
   * @throws Delta_CommandException コマンドの実行に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function sendHelp($command = NULL)
  {
    if ($command === NULL) {
      $command = 'HELP';
    } else {
      $command = 'HELP ' . $command;
    }

    $this->sendCommand($command);
    $this->parseResponse();

    if ($this->_status !== 211 && $this->_status != 214) {
      throw new Delta_CommandException(implode($this->_parameters, $this->_lineFeed));
    }

    $message = implode($this->_parameters, $this->_lineFeed) . $this->_lineFeed;

    return $message;
  }

  /**
   * サーバにコマンドを送信します。
   *
   * @param string $command 送信するコマンド。
   * @param string $arguments コマンド引数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function sendCommand($command, $arguments = NULL)
  {
    if ($arguments === NULL) {
      $command = $command . $this->_lineFeed;
    } else {
      $command = $command . ' ' . $arguments . $this->_lineFeed;
    }

    $this->_socket->write($command);
  }

  /**
   * サーバからのレスポンスを解析します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function parseResponse()
  {
    $this->_parameters = array();

    while ($line = $this->_socket->readLine()) {
      $this->_status = (int) substr($line, 0, 3);
      $this->_parameters[] = trim(substr($line, 4));

      if (substr($line, 3, 1) !== '-') {
        break;
      }
    }
  }

  /**
   * SMTP サーバへの接続を閉じます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $this->_socket->close();
  }
}
