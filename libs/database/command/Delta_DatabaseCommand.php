<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.command
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * このクラスは、実験的なステータスにあります。
 * これは、この関数の動作、関数名、ここで書かれていること全てが delta の将来のバージョンで予告なく変更される可能性があることを意味します。
 * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.command
 */

abstract class Delta_DatabaseCommand extends Delta_Object
{
  /**
   * データベースコネクションオブジェクト。
   * @var Delta_DatabaseConnection
   */
  protected $_connection;

  /**
   * コンストラクタ。
   *
   * @param Delta_DatabaseConnection $connection Delta_DatabaseConnection オブジェクト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_DatabaseConnection $connection)
  {
    $this->_connection = $connection;
  }

  /**
   * テーブルの一覧を取得します。
   *
   * @return array テーブルの一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getTables();

  /**
   * テーブルの (インデックスを含めない) データサイズを取得します。
   *
   * @param string $tableName 対象のテーブル名。
   * @return int テーブルのデータサイズを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getTableSize($tableName);

  /**
   * ビューの一覧を取得します。
   *
   * @return array ビューの一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getViews();

  /**
   * テーブルのフィールド一覧を取得します。
   *
   * @param string $tableName チェック対象のテーブル名。
   * @return array テーブルのフィールド一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getFields($tableName);

  /**
   * 指定したテーブルが存在するかどうかチェックします。
   *
   * @param string $tableName チェック対象のテーブル名。
   * @return bool テーブルが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function existsTable($tableName)
  {
    return in_array($tableName, $this->getTables());
  }

  /**
   * テーブルに定義されたプライマリキーを取得します。
   *
   * @param string $tableName 対象テーブル。
   * @return array プライマリキーの一覧を配列で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getPrimaryKeys($tableName)
  {
    $query = sprintf('SELECT * FROM %s LIMIT 0 OFFSET 0', $tableName);
    $stmt = $this->_connection->getAdapter()->query($query);
    $j = $stmt->columnCount();

    $primaryKeys = array();

    for ($i = 0; $i < $j; $i++) {
      $meta = $stmt->getColumnMeta($i);

      if (in_array('primary_key', $meta['flags'])) {
        $primaryKeys[] = $meta['name'];
      }
    }

    return $primaryKeys;
  }

  /**
   * テーブルに指定したフィールドが存在するかどうかチェックします。
   *
   * @param string $tableName チェック対象のテーブル名。
   * @param string $fieldName チェック対象のフィールド名。
   * @return bool フィールドが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function existsField($tableName, $fieldName)
  {
    return in_array($fieldName, $this->getFields($tableName));
  }

  /**
   * テーブルを作成します。
   * <strong>このメソッドは、実験的なステータスにあります。
   * これは、このメソッドの動作、メソッド名、ここで書かれていること全てが delta の将来のバージョンで予告な>く変更される可能性があることを意味します。
   * 注意を喚起するとともに自分のリスクでこのクラスを使用してください。</strong>
   *
   * @param array $data データ構成配列。
   * <code>
   * name: {string} テーブル名
   * columns: {array} カラムのリスト
   *   - name: {string} カラム名
   *     type: {string} カラム型。主にサポートする型は次の通り
   *       - tinyint
   *       - smallint
   *       - mediumint
   *       - int
   *       - float
   *       - bigint
   *       - string
   *       - text
   *       - date
   *       - datetime
   *     length: {int} カラム長。カラム型が文字列の場合は必須
   *     notNull: {bool} NOT NULL 制約。既定値は FALSE
   *     unsigned: {bool} Unsigned 制約。既定値は FALSE。(MySQL のみサポート)
   *     default: {string} デフォルト制約。
   *     unique: {bool} ユニーク制約。既定値は FALSE。
   *     primaryKey: {bool} プライマリキー制約。既定値は FALSE
   * indexes: {array} インデックス制約
   *   - name: {string} インデックス名
   *     columns: {array} インデックス対象カラムリスト
   *       - name: {string} 対象カラム名
   * </code>
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createTable(array $data)
  {
    $holder = new Delta_ParameterHolder($data, TRUE);
    $tableName = $holder->get('name');
    $columns = $holder->get('columns');

    $buffer = sprintf('CREATE TABLE %s (', $tableName);
    $primaryKeys = array();

    if (is_object($columns)) {
      foreach ($columns as $column) {
        $driverName = $this->_connection->getDriverName();

        $autoIncrement = $column->getBoolean('autoIncrement');
        $columnName = $column->getString('name');
        $length = $column->getInt('length');

        $buffer .= $columnName . ' ';

        // 型の生成
        if ($autoIncrement && $driverName == 'pgsql') {
          $buffer .= 'SERIAL';

        } else {
          $dataType = $this->getNativeDataType($column->getString('type'), $length);
          $buffer .= $dataType;

          // Unsigned 制約
          if ($column->getBoolean('unsigned') && $driverName == 'mysql') {
            $buffer .= ' UNSIGNED';
          }

          if ($autoIncrement) {
            $buffer .= ' AUTO_INCREMENT';
          }
        }

        // デフォルト制約
        if ($column->hasName('default')) {
          $buffer .= sprintf(' DEFAULT %s', $this->_connection->quote($column->getString('default')));
        }

        // NOT NULL 制約
        if ($column->getBoolean('notNull')) {
          $buffer .= ' NOT NULL';
        }

        // ユニーク制約
        if ($column->getBoolean('unique')) {
          $buffer .= ' UNIQUE';
        }

        $buffer .= ', ';
        $isPrimaryKey = $column->getBoolean('primaryKey');

        if ($isPrimaryKey) {
          $primaryKeys[] = $columnName;
        }
      }
    }

    // プライマリ制約
    if (sizeof($primaryKeys)) {
      $buffer .= sprintf('PRIMARY KEY(%s), ', implode(', ', $primaryKeys));
    }

    $buffer = sprintf('%s)', rtrim($buffer, ', '));

    $stmt = $this->_connection->createStatement($buffer);
    $stmt->execute();

    // インデックス制約
    $indexes = $holder->get('indexes');

    if (sizeof($indexes)) {
      foreach ($indexes as $index) {
        $indexName = $index->getString('name');
        $columns = array();

        foreach ($index->get('columns') as $column) {
          $columns[] = $column->getString('name');
        }

        $this->createIndex($tableName, $indexName, $columns);
      }
    }
  }

  /**
   * テーブルにインデックスを追加します。
   *
   * @param string $tableName テーブル名。
   * @param string $indexName インデックス名。
   * @param array $columns インデックス対象のカラムリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function createIndex($tableName, $indexName, array $columns)
  {
    $query = sprintf('CREATE INDEX %s ON %s(%s)',
      $indexName,
      $tableName,
      implode(', ', $columns));

    $this->_connection->createStatement($query)->execute();
  }

  /**
   * テーブルからインデックスを削除します。
   *
   * @param string $tableName テーブル名。
   * @param string $indexName 削除するインデックス名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dropIndex($tableName, $indexName)
  {
    $query = sprintf('DROP INDEX %s ON %s', $indexName, $tableName);
    $this->_connection->createStatement($query)->execute();
  }

  /**
   * Delta_DatabaseCommand がサポートするデータ型から、データベースのネイティブ型を取得します。
   *
   * @param string $type Delta_DatabaseCommand がサポートするデータ型。
   * @return string データベースがサポートするネイティブ型。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  protected function getNativeDataType($type, $length = NULL)
  {
    return $type;
  }

  /**
   * テーブルを削除します。
   *
   * @param string $tableName 削除対象のテーブル名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dropTable($tableName)
  {
    if ($this->existsTable($tableName)) {
      $query = sprintf('DROP TABLE %s', $tableName);
      $this->_connection->createStatement($query)->execute();
    }
  }

  /**
   * CSV データをテーブルにインポートします。
   *
   * @param string $tableName テーブル名。
   * @param string $path CSV ファイルのパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param array $options インポートオプション。
   *   - csvEncoding {UTF-8} CSV ファイルのエンコーディング。
   *   - databaseEncoding {UTF-8} インポート先のデータベースエンコーディング。
   *   - header {TRUE} CSV ファイルにヘッダ行が含まれる場合は TRUE、含まれない場合は FALSE を指定。
   *       ヘッダ行を含まない場合、CSV ファイルには全てのテーブルカラムを定義する必要があります。
   *   - delimiter {,} CSV ファイルのデリミタ。
   *   - enclosure {"} CSV データの区切り文字。
   *   - replace {FALSE} TRUE を指定した場合、データは登録 (INSERT) ではなく置換 (REPLACE) に置き換わります。
   *       このオプションは MySQL ドライバでのみ有効です。
   * <code>
   * # CSV データのサンプル
   * # delimiter で括られていない文字は数値、また SQL ステートメントと見なされます。
   * greeting_id, message, register_date
   * 1, "Hello", NOW()
   * # '#' から始まる行や空行はスキップされます。
   * 2, "こんにちは", NOW()
   * ...
   * </code>
   * @return int 登録したデータ件数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function importCSV($tableName, $path, array $options = array())
  {
    $path = Delta_FileUtils::buildAbsolutePath($path);

    $holder = new Delta_ParameterHolder($options);
    $databaseEncoding = $holder->getString('databaseEncoding', 'UTF-8');
    $csvEncoding = $holder->getString('csvEncoding', 'UTF-8');
    $changeEncoding = FALSE;

    if (strcasecmp($databaseEncoding, $csvEncoding) !== FALSE) {
      $changeEncoding = TRUE;
      $dataEncoding = $databaseEncoding;
    } else {
      $dataEncoding = $csvEncoding;
    }

    $header = $holder->getBoolean('header', TRUE);
    $delimiter = $holder->getString('delimiter', ',');
    $enclosure = $holder->getString('enclosure', '"');
    $escape = $holder->getString('escape', '\\');
    $replace = $holder->getString('replace', FALSE);

    $fp = fopen($path, 'r');
    $i = 0;
    $records = array();

    while (($data = fgets($fp)) !== FALSE) {
      if ($changeEncoding) {
        $data = mb_convert_encoding($data, $databaseEncoding, $csvEncoding);
      }

      // 空行や '#' から始まる行はスキップ
      if (strlen(trim($data)) && substr($data, 0, 1) !== '#') {
        $data = Delta_StringUtils::splitExclude($data, $delimiter, $enclosure, FALSE, $dataEncoding);
        $data = Delta_ArrayUtils::trim($data, NULL, TRUE, TRUE, FALSE, $dataEncoding);
        $i++;

        // 一行目のデータ
        if ($i == 1) {
          if ($header) {
            $fieldNames = $data;
          } else {
            $fieldNames = $this->getFields($tableName);
          }

          continue;
        }

        foreach ($data as &$field) {
          $length = mb_strlen($field, $dataEncoding);

          if (mb_substr($field, 0, 1, $dataEncoding) === $enclosure && mb_substr($field, $length - 1, 1, $dataEncoding) === $enclosure) {
            $field = mb_substr($field, 1, $length - 2, $dataEncoding);
          } else if (!is_numeric($field)) {
            $field = new Delta_DatabaseExpression($field);
          }
        }

        $records[] = $data;
      }
    }

    fclose($fp);

    if ($i == 0) {
      $affectedCount = 0;

    } else {
      if ($replace) {
        $options = array('replace' => TRUE);
        $affectedCount = $this->bulkInsert($tableName, $fieldNames, $records, $options);
      } else {
        $affectedCount = $this->bulkInsert($tableName, $fieldNames, $records);
      }
    }

    return $affectedCount;
  }

  /**
   * ダミーテーブルを使って、データベース上で単一の式を実行します。
   *
   * @param string $exprssion 実行する式。
   *   例えば 'NOW()' を指定すると、コマンドを実行した時間が返されます。
   * @return string 式の実行結果を返します。値の形式はデータベースに依存します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function expression($expression)
  {
    $query = sprintf('SELECT %s FROM dual', $expression);
    $resultSet = $this->_connection->rawQuery($query);

    return $resultSet->readField(0);
  }

  /**
   * テーブルにレコードを追加します。
   *
   * @param string $tableName データを登録するテーブル名。
   * @param array $data フィールド名とデータから構成される連想配列。
   * @param string $name シーケンスオブジェクト名。詳しくは {@link PDO::lastInsertId()} メソッドを参照。
   * @return int 最後に挿入された行の ID、あるいはシーケンス値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function insert($tableName, array $data, $name = NULL)
  {
    $names = array();
    $values = array();

    foreach ($data as $name => $value) {
      // NULL 値は INSERT クエリに含めない (MySQL 5.6 以降、auto_increment フィールドに NULL を指定するとエラーとなる)
      if ($value !== NULL) {
        $names[] = $name;
        $values[] = $value;
      }
    }

    $query = sprintf('INSERT INTO %s(%s) VALUES(%s)',
      $tableName,
      implode(', ', $names),
      implode(', ', $this->quoteValues($values)));

    $stmt = $this->_connection->rawQuery($query);
    $lastInsertId = $this->_connection->getLastInsertId($name);

    return $lastInsertId;
  }

  /**
   * バルクインサートを実行します。
   *
   * @param string $tableName データを登録するテーブル名。
   * @param string $fieldNames 対象フィールド名の配列。
   * @param array $data 二次元配列で構成されるデータリスト。
   *   <code>
   *   $fieldNames = array('greeting_id', 'language', 'message', 'register_date');
   *   $data = array(
   *     array(1, 'en', 'Hello', new Delta_DatabaseExpression('NOW()'));
   *     array(2, 'jp', 'こんにちは', new Delta_DatabaseExpression('NOW()'));
   *   );
   *
   *   $command->bulkInsert('greeting', $fieldNames, $data);
   *   </code>
   * @param array $options インサートオプション。
   *   - count {1000} 一回のクエリで送信するレコード数。
   *       件数が多いほどメモリ使用量やネットワーク負荷が増える点に注意して下さい。
   *       例えば MySQL の場合、一度の送信可能なデータ量は 'max-allowed-packet' の設定に依存します。
   *   - replace {FALSE} TRUE を指定した場合、データは登録 (INSERT) ではなく置換 (REPLACE) に置き換わります。
   *       このオプションは MySQL ドライバでのみ有効です。
   * @return int 登録したデータ件数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bulkInsert($tableName, $fieldNames, array $data, array $options = array())
  {
    $holder = new Delta_ParameterHolder($options);
    $count = $holder->getInt('count', 1000);
    $replace = $holder->getBoolean('replace');

    $recordCount = sizeof($data);
    $data = array_chunk($data, $count);
    $j = sizeof($data);

    if ($j == 0) {
      return 0;
    }

    // インサートモード
    if ($replace) {
      $prefixQuery = 'REPLACE';
    } else {
      $prefixQuery = 'INSERT';
    }

    // バルククエリの構築
    $prefixQuery = sprintf('%s INTO %s(%s) VALUES',
      $prefixQuery,
      $tableName,
      implode(', ', $fieldNames));
    $executeCount = 0;

    for ($i = 0; $i < $j; $i++) {
      $valuesQuery = NULL;

      // $count 単位でステートメントを構築
      foreach ($data[$i] as $index => $values) {
        $valuesQuery .= sprintf('(%s), ',
          implode(', ', $this->quoteValues($values)));
      }

      $executeQuery = $prefixQuery . rtrim($valuesQuery, ', ');
      $stmt = $this->_connection->createStatement($executeQuery);
      $executeCount += $stmt->execute();
    }

    return $executeCount;
  }

  /**
   * レコードを更新します。
   *
   * @param string $tableName 更新対象のテーブル名。
   * @param array $data 更新対象のフィールド名と更新値で構成される連想配列。
   * @param array $where 更新条件のフィールド名と条件値で構成される連想配列。
   * @return int 作用したレコード数を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function update($tableName, array $data = array(), array $where = array())
  {
    $result = FALSE;

    if (sizeof($data)) {
      $updateQuery = NULL;

      // 更新カラム
      foreach ($data as $name => $value) {
        if (!is_object($value)) {
          $value = $this->_connection->quote($value);
        }

        if ($updateQuery) {
          $updateQuery .= ', ';
        }

        $updateQuery .= sprintf('%s = %s', $name, $value);
      }

      // 更新条件
      $whereQuery = NULL;

      if (sizeof($where)) {
        foreach ($where as $name => $value) {
          if ($whereQuery) {
            $whereQuery .= 'AND ';
          }

          if (!is_object($value)) {
            $value = $this->_connection->quote($value);
          }

          $whereQuery .= sprintf('%s = %s ', $name, $value);
        }

        $whereQuery = ' WHERE ' . rtrim($whereQuery);

      } else {
        $updateQuery = rtrim($updateQuery);
      }

      $query = sprintf('UPDATE %s SET %s%s', $tableName, $updateQuery, $whereQuery);
      $stmt = $this->_connection->createStatement($query);

      $result = $stmt->execute();
    }

    return $result;
  }

  /**
   * レコードを削除します。
   *
   * @param string $tableName 削除対象のテーブル名。
   * @param array $where 削除条件をフィールド名と条件値で構成した連想配列。(AND 条件)
   * @return int 作用したレコード数を返します。
   * @since 1.1
   */
  public function delete($tableName, array $where = array())
  {
    $query = 'DELETE FROM ' . $tableName;

    if (sizeof($where)) {
      $query .= ' WHERE ';

      foreach ($where as $name => $value) {
        if (!is_object($value)) {
          $value = $this->_connection->quote($value);
        }

        $query .= sprintf('%s = %s AND ', $name, $value);
      }

      $query = rtrim($query, 'AND ');
    }

    $stmt = $this->_connection->createStatement($query);

    return $stmt->execute();
  }

  /**
   * 値で構成される配列データを SQL エスケープします。
   *
   * @param array $array 値の配列。
   * @return SQL エスケープされた値配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function quoteValues(array $array)
  {
    array_walk($array, function(&$value, $name) {
      if (!is_object($value)) {
        $value = $this->_connection->quote($value);
      }
    });

    return $array;
  }

  /**
   * テーブルに登録されているレコード数を取得します。
   *
   * @return int テーブルに登録されているレコード数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRecordCount($tableName)
  {
    $query = sprintf('SELECT COUNT(*) AS count FROM %s', $tableName);

    return $this->_connection->rawQuery($query)->readFirst()->count;
  }

  /**
   * テーブルに含まれる全てのデータを削除します。
   * このメソッドは内部的に SQL の 'TRUNCATE' コマンドを実行します。
   *
   * @param string $tableName 対象となるテーブル名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function truncate($tableName)
  {
    $query = sprintf('TRUNCATE TABLE %s', $tableName);
    $this->_connection->rawQuery($query);
  }

  /**
   * データベースのバージョン情報を取得します。
   *
   * @param bool $versionOnly バージョン番号のみ返す場合は TRUE を指定。
   * @return string データベースのバージョン情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  abstract public function getVersion();
}
