<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ファイルから文字を読み込む機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file
 * @since 1.1
 */

class Delta_FileReader extends Delta_Object
{
  /**
   * @var string
   */
  protected $_path;

  /**
   * @var resource
   */
  protected $_handler;

  /**
   * @var string
   */
  protected $_defaultEncoding;

  /**
   * @var string
   */
  protected $_inputEncoding;

  /**
   * @var string
   */
  protected $_linefeed = PHP_EOL;

  /**
   * コンストラクタ。
   *
   * @param string $path 読み込むファイルのパス。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
   * @throws Delta_IOException ファイルが存在しない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path)
  {
    if (!Delta_FileUtils::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    if (!is_file($path)) {
      $message = sprintf('File does not exist. [%s]', $path);
      throw new Delta_IOException($message);
    }

    $this->_defaultEncoding = Delta_Config::getApplication()->getString('charset.default');
    $this->_path = $path;

    $this->open();
  }

  /**
   * ファイルの入力エンコーディングを設定します。
   *
   * @param string $inputEncoding ファイルの入力エンコーディング。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setInputEncoding($inputEncoding)
  {
    $this->_inputEncoding = $inputEncoding;
  }

  /**
   * ファイルの改行コードを設定します。
   *
   * @param string $linefeed ファイルの改行コード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setLinefeed($linefeed)
  {
    $this->_linefeed = $linefeed;
  }

  /**
   * ファイルを開きます。
   * このメソッドは {@link __construct()} からコールされるため、通常呼び出す必要はありません。
   * {@link close()} で閉じたファイルを再度開く際に利用します。
   *
   * @return bool ファイルのオープンに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function open()
  {
    $result = FALSE;

    if ($this->_handler === NULL) {
      $this->_handler = fopen($this->_path, 'r');

      if ($this->_handler) {
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * ファイルポインタをファイルストリームの先頭に移動します。
   *
   * @return bool ファイルポインタの移動が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function rewind()
  {
    $this->checkHandlerState();

    return rewind($this->_handler);
  }

  /**
   * ファイルが開いた状態にあるかチェックします。
   *
   * @throws Delta_IOException ファイルが開いていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkHandlerState()
  {
    if ($this->_handler) {
      return TRUE;
    }

    $message = 'File has been closed.';
    throw new Delta_IOException($message);
  }

  /**
   * ファイルが読み込み可能などうかチェックします。
   *
   * @return bool ファイルが読み込み可能な場合は TRUE、読み込めない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isReadable()
  {
    return is_readable($this->_path);
  }

  /**
   * ファイルポインタを指定した位置に移動します。
   *
   * @param int $offset オフセットバイト。
   * @return bool 移動に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function seekOffset($offset)
  {
    $result = FALSE;
    $this->checkHandlerState();

    if (fseek($this->_handler, $offset)) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 現在のファイルポインタからオフセットを加えた位置に移動します。
   *
   * @param int $offset オフセットバイト。
   * @return bool 移動に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function seekCurrentOffset($offset)
  {
    $result = FALSE;
    $this->checkHandlerState();

    if (fseek($this->_handler, $offset, SEEK_CUR)) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 現在のファイルポインタから指定した文字数を取得します。
   *
   * @param int $char 読み込む文字数。
   * @return string ファイルポインタから読み込んだ文字を返します。
   *   ファイルポインタがファイル終端に達している場合や、共有ロックに失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function read($char = 1)
  {
    $this->checkHandlerState();
    $buffer = FALSE;

    if (flock($this->_handler, LOCK_SH) && !$this->isEOF()) {
      $buffer = fgets($this->_handler, $char + 1);
      flock($this->_handler, LOCK_UN);

      if ($this->_inputEncoding !== NULL) {
        $buffer = mb_convert_encoding($buffer, $this->_defaultEncoding, $this->_inputEncoding);
      }
    }

    return $buffer;
  }

  /**
   * 現在のファイルポインタから 1 行を取得します。
   *
   * @return string ファイルポインタから読み込まれた 1 行を返します。
   *   ファイルポインタがファイル終端に達している場合や、共有ロックに失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readLine()
  {
    $this->checkHandlerState();
    $buffer = FALSE;

    if (flock($this->_handler, LOCK_SH) && !$this->isEOF()) {
      $buffer = fgets($this->_handler);
      flock($this->_handler, LOCK_UN);

      if ($buffer !== FALSE && $this->_inputEncoding !== NULL) {
        $buffer = mb_convert_encoding($buffer, $this->_defaultEncoding, $this->_inputEncoding);
      }
    }

    return $buffer;
  }

  /**
   * 現在のファイルポインタから全ての行を配列データとして取得します。
   *
   * @return array ファイルポインタから読み込まれた全ての行を配列で返します。
   *   ファイルは共有ロックモードで読み込まれますが、ロックに失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readLines()
  {
    $lines = explode($this->_linefeed, $this->readContents());

    if ($lines !== FALSE) {
      array_pop($lines);
    }

    return $lines;
  }

  /**
   * 現在のファイルポインタから全てのデータを取得します。
   *
   * @return string フアイルポインタから読み込まれた全てのデータを返します。
   *   ファイルは共有ロックモードで読み込まれますが、ロックに失敗した場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readContents()
  {
    $this->checkHandlerState();
    $buffer = NULL;

    if (flock($this->_handler, LOCK_SH) && !$this->isEOF()) {
      while ($line = fgets($this->_handler)) {
        $buffer .= $line;
      }

      flock($this->_handler, LOCK_UN);

      if ($this->_inputEncoding !== NULL) {
        $buffer = mb_convert_encoding($buffer, $this->_defaultEncoding, $this->_inputEncoding);
      }

    } else {
      $buffer = FALSE;
    }

    return $buffer;
  }

  /**
   * ファイルポインタの現在位置を取得します。
   *
   * @return int ファイルポインタの現在位置を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPosition()
  {
    $this->checkHandlerState();

    return ftell($this->_handler);
  }

  /**
   * リソースを取得します。
   *
   * @return resource リソースを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getResource()
  {
    return $this->_handler;
  }

  /**
   * ファイルポインタがファイル終端に達しているかチェックします。
   *
   * @return bool ファイルポインタがファイル終端に達している場合は TRUE、達していない場合は FALSE を返します。
   * @throws Delta_IOException ファイルハンドラが閉じている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isEOF()
  {
    $this->checkHandlerState();

    return feof($this->_handler);
  }

  /**
   * ファイルを閉じます。
   *
   * @return bool ファイルのクローズに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $result = FALSE;

    if ($this->_handler && fclose($this->_handler)) {
      $this->_handler = NULL;
      $result = TRUE;
    }

    return $result;
  }
}
