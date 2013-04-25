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

class Delta_FileWriter extends Delta_Object {
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
   * 書き込みオプション。
   * @var bool
   */
  protected $_writeFlag;

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
   * @param string $path 出力先のファイルパス。絶対パス、または ({APP_ROOT_DIR}/logs からの) 相対パスでの指定が可能。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path = NULL)
  {
    if ($path !== NULL) {
      $this->setWritePath($path);
    }

    $this->_inputEncoding = Delta_Config::getApplication()->get('charset.default');
    $this->_outputEncoding = $this->_inputEncoding;

    $this->setWriteAppend(TRUE);
    $this->setLazyWrite(TRUE);
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
   * データをファイルに書き込むタイミングを設定します。
   * デフォルトの動作では遅延書き込みが有効となります。
   *
   * @param bool $lazyWrite 遅延書き込みを行う場合は TRUE、逐一書き込みを行う場合は FALSE を指定。
   *   遅延書き込みについては {@link write()} メソッドの項も合わせて参照して下さい。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setLazyWrite($lazyWrite)
  {
    $this->_lazyWrite = $lazyWrite;
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
   * ファイルが既に存在する場合に、データを追記するか上書きするかモードを設定します。
   * 未設定の場合はデフォルトで追記モードとなります。
   *
   * @param bool $writeAppend データを追記する場合は TRUE、上書きする場合は FALSE を指定。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setWriteAppend($writeAppend)
  {
    if ($writeAppend) {
      $this->_writeFlag = FILE_APPEND | LOCK_EX;
    } else {
      $this->_writeFlag = LOCK_EX;
    }
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
   *   遅延書き込みが有効な場合、出力内容はバッファリングされ、Delta_LogWriter オブジェクトが消滅する ({@link __destruct()} がコールされた} (または {flush()} メソッドをコールした) タイミングで一斉に出力されます。
   *   なお、{@link Delta_LogRotatePolicy ローテートポリシー} は実際の出力が行われるタイミングで発動する点に注意して下さい。
   *   例えば {@link Delta_LogRotateDateBasedPolicy} で日次ローテートを有効にした時、1 回目の write() が 23:59:59、2 回目の write() が翌 00:00:00 にコールされたとしても、実際は翌日分のログに 2 つのデータが書き込まれます。
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
    }

    $createFlag = FALSE;

    if (!is_file($this->_path)) {
      $createFlag = TRUE;
    }

    Delta_FileUtils::writeFile($this->_path, $this->buildOutputData($data), $this->_writeFlag);

    if ($this->_mode !== NULL && $createFlag) {
      chmod($this->_path, $this->_mode);
    }
  }

  /**
   * 遅延書き込みが有効な場合、バッファリングされた全てのデータを出力します。
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
   * 遅延書き込みが有効な場合、バッファリングされた全てのデータを破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_writeBuffer = NULL;
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
      if ($this->_lazyWrite) {
        $this->flush();
      }

    } catch (Exception $e) {
      Delta_ExceptionStackTraceDelegate::invoker($e);

      die();
    }
  }
}
