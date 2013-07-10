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
 * ファイルに文字を書き込む機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file
 */

class Delta_FileWriter extends Delta_Object
{
  /**
   * ファイルハンドラ。
   * @var resource
   */
  protected $_handler;

  /**
   * 追記書き込みモード。
   * @var bool
   */
  protected $_appendMode;

  /**
   * 出力パス。
   * @var string
   */
  protected $_path;

  /**
   * @var int
   */
  protected $_mode;

  /**
   * 遅延書き込みを行うかどうか。
   *
   * @var bool
   */
  protected $_lazyWrite;

  /**
   * 入力エンコーディング。
   * @var string
   */
  protected $_inputEncoding;

  /**
   * 出力エンコーディング。
   * @var string
   */
  protected $_outputEncoding;

  /**
   * {@link writeLine()} で使用する改行コード。
   * @var string
   */
  protected $_linefeed = PHP_EOL;

  /**
   * データに含まれる改行コードを {@link $_linefeed} に変換するかどうか。
   * @var bool
   */
  protected $_convertLinefeed = FALSE;

  /**
   * 出力バッファ。
   * @var string
   */
  protected $_writeBuffer;

  /**
   * コンストラクタ。
   *
   * @param string $path 出力するファイルのパス。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
   * @param bool $appendMode 追記モード時は TRUE、新規作成モード時は FALSE を指定。
   * @param bool $lazyWrite 遅延書き込みモードの指定。
   *   TRUE 指定時は {@link write()} や {@link writeLine()} で書き込まれる内容をバッファリングし、ファイルが閉じられる直前 (または {@link flush()} をコールしたタイミング) で出力を行う。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path = NULL, $appendMode = TRUE, $lazyWrite = TRUE)
  {
    $this->_inputEncoding = Delta_Config::getApplication()->get('charset.default');
    $this->_outputEncoding = $this->_inputEncoding;
    $this->_appendMode = $appendMode;
    $this->_lazyWrite = $lazyWrite;

    if ($path !== NULL) {
      $this->setWritePath($path);
    }
  }

  /**
   * 出力ファイルのパスを設定します。
   *
   * @param string $path 出力先のファイルパス。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setWritePath($writePath)
  {
    if (Delta_FileUtils::isAbsolutePath($writePath)) {
      $this->_path = $writePath;
    } else {
      $this->_path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $writePath;
    }

    if ($this->_appendMode) {
      $this->_handler = fopen($this->_path, 'a');
    } else {
      $this->_handler = fopen($this->_path, 'w');
    }
  }

  /**
   * ファイルが書き込みが可能かどうかチェックします。
   *
   * @return bool ファイルが書き込み可能な場合は TRUE、書き込めない場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isWritable()
  {
    $result = FALSE;

    if ($this->_path !== NULL) {
      $result = is_writable($this->_path);
    }

    return $result;
  }

  /**
   * ファイルの書き込み権限を設定します。
   *
   * @param int $mode 書き込み権限を 8 進数で指定。(例えば 0644)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setMode($mode)
  {
    $this->_mode = $mode;
  }

  /**
   * {@link writeLine()} メソッドで使用する改行コードを設定します。
   * 未指定の場合は OS の改行コードに依存します。
   *
   * @param string $linefeed 改行コード。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setLinefeed($linefeed)
  {
    $this->_linefeed = $linefeed;
  }

  /**
   * 出力文字列に含まれる改行コードを {@link setLinefeed()} メソッドで指定した改行コードに変換するかどうか設定します。
   * デフォルトの動作では変換処理を行いません。

   * @param bool $convertLinefeed 改行コードの変換を行う場合は TRUE、変換しない場合は FALSE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setConvertLinefeed($convertLinefeed)
  {
    $this->_convertLinefeed = $convertLinefeed;
  }

  /**
   * 入力エンコーディングを設定します。
   * 未指定の場合は application.yml に定義された 'charaset.default' が使用されます。
   *
   * @param string $inputEncoding 入力エンコーディング。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setInputEncoding($inputEncoding)
  {
    $this->_inputEncoding = $inputEncoding;
  }

  /**
   * 出力エンコーディングを設定します。
   * 未指定の場合は application.yml に定義された 'charaset.default' が使用されます。
   *
   * @param string $outputEncoding 出力エンコーディング。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setOutputEncoding($outputEncoding)
  {
    $this->_outputEncoding = $outputEncoding;
  }

  /**
   * ファイルへの書き込みを行います。
   *
   * @param string $data 書き込むデータ。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function write($data)
  {
    if ($this->_lazyWrite) {
      $this->_writeBuffer .= $data;

    } else {
      $this->realWrite($data);
    }
  }

  /**
   * ファイルへの書き込みを行います。文字の終端には改行コードが付加されます。
   *
   * @see Delta_LogWriter::write()
   * @see Delta_LogWriter::setLinefeed()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function writeLine($data)
  {
    $data .= $this->_linefeed;
    $this->write($data);
  }

  /**
   * 文字列 data を出力エンコーディング形式に変換します。
   *
   * @param string $data 出力対象の文字列。未指定の場合は出力バッファの内容を対象とします。
   * @return string 出力データを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function buildOutputData($data = NULL)
  {
    if ($data === NULL) {
      $data = $this->_writeBuffer;
    }

    if ($this->_inputEncoding !== $this->_outputEncoding) {
      $data = mb_convert_encoding($data, $this->_outputEncoding, $this->_inputEncoding);
    }

    if ($this->_convertLinefeed) {
      $data = Delta_StringUtils::replaceLinefeed($data, $this->_linefeed);
    }

    return $data;
  }

  /**
   * 出力バッファに含まれるデータを取得します。
   *
   * @return string 出力バッファに含まれるデータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getWriteBuffer()
  {
    return $this->_writeBuffer;
  }

  /**
   *
   *
   * @param string $data
   * @throws Delta_IOException
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function realWrite($data)
  {
    if ($this->_path === NULL) {
      throw new Delta_IOException('Write path is not set.');

    } else if ($this->_handler === NULL) {
      $message = 'File has been closed.';
      throw new Delta_IOException($message);
    }

    $isNewFile = FALSE;

    if (!is_file($this->_path)) {
      $isNewFile = TRUE;
    }

    if (flock($this->_handler, LOCK_EX)) {
      fwrite($this->_handler, $this->buildOutputData($data));
      flock($this->_handler, LOCK_UN);
    }

    if ($this->_mode !== NULL && $isNewFile) {
      chmod($this->_path, $this->_mode);
    }
  }

  /**
   * 遅延書き込みモードの場合、バッファリングされているデータを出力します。
   * このメソッドは {@link __destruct()} からコールされますが、任意のタイミングで呼び出すことも可能です。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function flush()
  {
    if ($this->_writeBuffer) {
      $this->realWrite($this->_writeBuffer);
      $this->clear();
    }
  }

  /**
   * バッファリングされている全てのデータを破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_writeBuffer = NULL;
  }

  /**
   * ファイルを閉じます。
   *
   * @return bool ファイルのクローズに成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    $result = FALSE;
    $this->flush();

    if (fclose($this->_handler)) {
      $result = TRUE;
      $this->_handler = NULL;
    }

    return $result;
  }

  /**
   * デストラクタ。
   * 遅延書き込みが有効な場合、バッファの内容をファイルに書き出します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
     // デストラクタでスローされた例外は例外ハンドラに遷移されない
    try {
      $this->flush();

    } catch (Exception $e) {
      Delta_ExceptionStackTraceDelegate::invoker($e);

      die();
    }
  }
}
