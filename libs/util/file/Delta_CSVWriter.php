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
 * CSV 形式でファイルにデータを書き込みます。
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt Common Format and MIME Type for Comma-Separated Values (CSV) Files
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file
 */

class Delta_CSVWriter extends Delta_FileWriter
{
  /**
   * 各フィールドをダブルクォートで囲むかどうか。
   * @var bool
   */
  private $_fieldEnclosedWithQuote = FALSE;

  /**
   * フィールドを区切るセパレータ。
   * @var string
   */
  private $_fieldSeparator = ',';

  /**
   * ヘッダが含まれているかどうか。
   * @var string
   */
  private $_header = 'absent';

  /**
   * フィールドサイズ。
   * @var int
   */
  private $_fieldSize;

  /**
   * コンストラクタ。
   *
   * @see Delta_FileWriter::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path = NULL)
  {
    parent::__construct($path, FALSE);
  }

  /**
   * レコードの各フィールドをダブルクォーテーションで囲むかどうか設定します。
   *
   * @param bool $fieldEnclosedWithQuote 各フィールドをダブルクォートで囲む場合は TRUE を指定。既定値は FALSE。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFieldEnclosedWithQuote($fieldEnclosedWithQuote)
  {
    $this->_fieldEnclosedWithQuote = $fieldEnclosedWithQuote;
  }

  /**
   * レコードの各フィールド間を区切るセパレータを設定します。
   *
   * @param string $fieldSeparator セパレータ文字。既定値はカンマが使用される。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFieldSeparator($fieldSeparator)
  {
    $this->_fieldSeparator = $fieldSeparator;
  }

  /**
   * CSV の行を生成します。
   *
   * @param array $fields フィールド配列。
   * @return string CSV 形式のレコードを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildRecord(array $fields)
  {
    $buffer = NULL;

    if (is_array($fields)) {
      foreach ($fields as $field) {
        if ($this->_fieldEnclosedWithQuote) {
          // 文字列中に含まれる全てのダブルクォートをエスケープ
          $offset = 0;

          while (($pos = mb_strpos($field, '"', $offset, $this->_inputEncoding)) !== FALSE) {
            $field = Delta_StringUtils::insert($field, '"', $pos, NULL, $this->_inputEncoding);
            $offset = $pos + 2;
          }

          $field = '"' . $field . '"';

        } else {
          $pattern = '/[' . $this->_linefeed . '",]/';

          if (preg_match($pattern, $field)) {
            $field = '"' . $field . '"';
          }
        }

        $buffer .= $field . $this->_fieldSeparator;
      }
    }

    $buffer = rtrim($buffer, $this->_fieldSeparator) . $this->_linefeed;

    return $buffer;
  }

  /**
   * CSV の見出し行を設定します。
   *
   * @param array $fields 見出しリスト。
   * @throws Delta_ParseException 見出し列数がレコード列数とマッチしない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setHeader(array $fields)
  {
    $size = sizeof($fields);

    if ($this->_fieldSize === NULL) {
      $this->_fieldSize = $size;

    } else if ($this->_fieldSize != $size) {
      $message = sprintf('Invalid number of columns. [%s]', implode(',', $fields));
      throw new Delta_ParseException($message);
    }

    $this->_writeBuffer .= $this->buildRecord($fields);
    $this->_header = 'present';
  }

  /**
   * CSV レコードを追加します。
   *
   * @param array $fields フィールドの配列。
   * @throws Delta_ParseException フィールド数が見出し列数、または他の行の列数とマッチしない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addRecord(array $fields)
  {
    $size = sizeof($fields);

    if ($this->_fieldSize === NULL) {
      $this->_fieldSize = $size;

    } else if ($this->_fieldSize != $size) {
      $message = sprintf('Invalid number of columns. [%s]', implode(',', $fields));
      throw new Delta_ParseException($message);
    }

    $this->_writeBuffer .= $this->buildRecord($fields);
  }

  /**
   * 連想配列のデータを CSV に追加します。
   *
   * @param array $records 連想配列形式のデータ。array(array(1, 'foo'), array(2, 'bar')) といった形式を指定します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addRecords(array $records)
  {
    foreach ($records as $record) {
      $this->addRecord($record);
    }
  }

  /**
   * 生成した CSV データをファイルに出力します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function writeCSV()
  {
    if (!$this->_lazyWrite) {
      $this->flush();
    }
  }

  /**
   * CSV データをダウンロードします。
   * download() メソッドはファイルをダウンロードするのに必要な HTTP ヘッダを自動的にクライアントへ送信します。
   *
   * @param string $fileName ダウンロード時のファイル名。未指定の場合はアクション名がファイル名として使用される。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function download($fileName = NULL)
  {
    if ($fileName === NULL) {
      $fileName = Delta_ActionStack::getInstance()->getLastEntry()->getActionName() . '.csv';
    }

    $contentType = sprintf('text/csv; charset=%s; header=%s',
      $this->_outputEncoding,
      $this->_header);

    $response = Delta_DIContainerFactory::getContainer()->getComponent('response');

    $response->setContentType($contentType);
    $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
    $response->write($this->buildOutputData());

    $this->clear();
  }

  /**
   * @see Delta_FileWriter::__destruct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    if ($this->_path !== NULL) {
      parent::__destruct();
    }
  }
}
