<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * リモートホストに対しソケット通信を行うためのユーティリティクラスです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package net
 */

class Delta_Socket extends Delta_Object
{
  /**
   * ソケットリソース。
   * @var resource
   */
  private $_socket;

  /**
   * 一度に読み込むバイト数。
   * @var int
   */
  private $_buffer = 1024;

  /**
   * 対象ホストにソケット接続します。
   *
   * @param string $host ホスト名。
   * @param int $port ポート番号。
   * @param int $timeout 接続タイムアウト秒。
   * @param int $flag 接続設定フラグの任意の組み合わせを指定できるビットフィールド。STREAM_CLIENT_* 定数を指定可能。
   * @param resource $context {@link stream_context_create()} で作成した有効なコンテキストリソース。
   * @param string $protocol 接続するプロトコル。
   * @throws Delta_ConnectException ホストへの接続に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function connect($host,
    $port,
    $timeout = 30,
    $flag = STREAM_CLIENT_CONNECT,
    $context = NULL,
    $protocol = 'tcp')
  {
    $remoteSocket = sprintf('%s://%s:%s', $protocol, $host, $port);

    if ($context === NULL) {
      $context = stream_context_create();
    }

    // Windows 環境では接続エラーを取得できない場合があるので、使用するクラス側でレスポンスの正当性をチェックを行う必要がある
    $socket = @stream_socket_client($remoteSocket,
      $errorNumber,
      $errorMessage,
      $timeout,
      $flag,
      $context);

    if (!$socket) {
      $message = sprintf('Failure connect to %s:%s [%s]', $host, $port, $errorMessage);
      throw new Delta_ConnectException($message, $errorNumber);
    }

    $this->_socket = $socket;
  }

  /**
   * ソケット通信が有効な状態にあるかチェックします。
   *
   * @return bool ソケット通信が有効な状態の場合に TRUE を返します。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function isAlive()
  {
    if (!is_resource($this->_socket)) {
      throw new Delta_ConnectException('Not connected.');
    }

    return TRUE;
  }

  /**
   * 現在通信中のソケットに関するメタデータを取得します。
   *
   * @return array ソケットに関するメタデータを返します。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getStatus()
  {
    $this->isAlive();

    return stream_get_meta_data($this->_socket);
  }

  /**
   * 現在通信中のソケットのブロックモードを設定します。
   * ストリームはデフォルトではブロックモードで開きます。
   *
   * @param int $blockMode 非ブロックモード時は 0、ブロックモード時は 1 を指定。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setBlockMode($blockMode)
  {
    $this->isAlive();

    stream_set_blocking($this->_socket, $blockMode);
  }

  /**
   * ストリームのタイムアウト秒を設定します。
   *
   * @param int $timeout タイムアウト秒。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setTimeout($timeout)
  {
    $this->isAlive();

    stream_set_timeout($this->_socket, $timeout);
  }

  /**
   * データを書き込みます。
   *
   * @param string $string 書き込むデータ。
   * @param int $blockSize 1 度に書き込むバッファサイズ。
   * @return int 書き込んだバイト数を返します。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($string, $blockSize = NULL)
  {
    $this->isAlive();
    $pos = 0;

    if ($blockSize === NULL) {
      $pos = fwrite($this->_socket, $string);

    } else {
      $length = strlen($string);

      while ($pos < $length) {
        $pos += fwrite($this->_socket, substr($string, $pos, $blockSize));
      }
    }

    return $pos;
  }

  /**
   */
  public function read($length)
  {
    $this->isAlive();

    return fread($this->_socket, $length);
  }

  /**
   * ストリームから一行データを取得します。
   *
   * @return string ストリームから取得したデータ行を返します。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readLine()
  {
    $this->isAlive();

    return fgets($this->_socket);
  }

  /**
   * ストリームから全データを取得します。
   *
   * @return string ストリームから取得した全データを返します。
   * @throws Delta_ConnectException 接続が無効な状態の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readAll()
  {
    $this->isAlive();
    $buffer = NULL;

    while (!feof($this->_socket)) {
      $buffer .= fgets($this->_socket, $this->_buffer);
    }

    return $buffer;
  }

  /**
   * ソケット通信を閉じます。
   *
   * @return bool ソケットが正常に閉じられたかどうかを TRUE/FALSE で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    if (is_resource($this->_socket)) {
      return fclose($this->_socket);
    }

    return FALSE;
  }
}
