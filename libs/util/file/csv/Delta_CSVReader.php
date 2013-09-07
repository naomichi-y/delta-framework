<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file.csv
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * CSV 形式のデータを読み込みます。
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt Common Format and MIME Type for Comma-Separated Values (CSV) Files
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.file.csv
 * @since 1.1
 */

class Delta_CSVReader extends Delta_FileReader
{
  /**
   * @var string
   */
  private $_separator = ',';

  /**
   * @see Delta_FileReader::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($path)
  {
    parent::__construct($path);

    $this->_linefeed = "\r\n";
  }

  /**
   * フィールドを区切るセパレータを設定します。
   *
   * @param string $separator セパレータ文字。既定値はカンマが使用される。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setSeparator($separator)
  {
    $this->_separator = $separator;
  }

  /**
   * CSV レコードを配列として取得します。
   *
   * @return array CSV レコードを配列として返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readFields()
  {
    $line = $this->readLine();
    $fields = FALSE;

    if ($line !== FALSE) {
      $line = rtrim($line, $this->_linefeed);
      $fields = Delta_StringUtils::splitExclude($line, $this->_separator, '"', FALSE);

      foreach ($fields as &$value) {
        if (substr($value, 0, 1) === '"' && substr($value, -1, 1) === '"') {
          $value = str_replace('""', '"', trim($value, '"'));
        }
      }
    }

    return $fields;
  }

  /**
   * CSV データを連想配列として取得します。
   *
   * @return array CSV データを連想配列として返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function readAssocContents()
  {
    $data = array();

    while ($fields = $this->readFields()) {
      $data[] = $fields;
    }

    return $data;
  }
}
