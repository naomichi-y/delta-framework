<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package database.profiler
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
 * @package database.profiler
 */

class Delta_SQLPreparedStatementParser extends Delta_Object
{
  /**
   * 名前付きプレースホルダ。
   * @var int
   */
  const PLACE_HOLDER_TYPE_NAME = 1;

  /**
   * 疑問符プレースホルダ。
   * @var int
   */
  const PLACE_HOLDER_TYPE_ID = 2;

  /**
   * @var string
   */
  private $_query;

  /**
   * @var array
   */
  private $_bindVariables = array();

  /**
   * コンストラクタ。
   *
   * @param string $query 解析対象のプリペアードステートメント。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct($query)
  {
    $this->_query = $query;
  }

  /**
   * プリペアードステートメントにバインド変数を割り当てます。
   *
   * @param array $bindVariables プリペアードステートメントに割り当てるバインド変数のリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function bindVariables(array $bindVariables)
  {
    if ($this->getPlaceHolderType($this->_query) == self::PLACE_HOLDER_TYPE_ID) {
      $array = array();
      $i = 1;

      foreach ($bindVariables as $index => $value) {
        $array[$i] = $value;
        $i++;
      }

      $this->_bindVariables = $array;

    } else {
      $this->_bindVariables = $bindVariables;
    }
  }

  /**
   * プリペアードステートメントにバインド変数を展開した状態の SQL 構文を取得します。
   *
   * @return string バインド変数を展開した生の SQL 構文返します。
   * @throws Delta_ParseException
   *   - 疑問符プレースホルダ使用時: ステートメントのプレースホルダにバインドされた変数の数がマッチしない場合に発生。
   *   - 名前付きプレースホルダ使用時: 予測されていないバインド変数が割り当てられた場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function buildExpandBindingQuery()
  {
    $type = $this->getPlaceHolderType($this->_query);

    switch ($type) {
      case self::PLACE_HOLDER_TYPE_NAME:
        $rawQuery = $this->bindByParameterName($this->_query, $this->_bindVariables);
        break;

      case self::PLACE_HOLDER_TYPE_ID:
        $rawQuery = $this->bindByInterrogation($this->_query, $this->_bindVariables);
        break;

      default:
        $rawQuery = $this->_query;
        break;
    }

    return $rawQuery;
  }

  /**
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getPlaceHolderType($preparedStatement)
  {
    if (Delta_StringUtils::searchIndex($preparedStatement, ':', 0, '\'') !== FALSE) {
      return self::PLACE_HOLDER_TYPE_NAME;

    } else if (Delta_StringUtils::searchIndex($preparedStatement, '?', 0, '\'') !== FALSE) {
      return self::PLACE_HOLDER_TYPE_ID;

    } else {
      return NULL;
    }
  }

  /**
   * 名前付きプレースホルダの変数をステートメントに展開します。
   *
   * @param string $statement データベースサーバに有効な SQL 文。
   * @param array $bindVariables バインド変数リスト。
   * @return array バインド変数を展開したステートメントを返します。
   * @throws Delta_ParseException 予測されないバインド変数が割り当てられた場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function bindByParameterName($statement, array $bindVariables)
  {
    $encoding = Delta_Config::getApplication()->get('charset.default');
    $bindingVariables = array();

    while (($pos = Delta_StringUtils::searchIndex($statement, ':', 0, '\'')) !== FALSE) {
      // バインド変数が埋め込まれている位置を検索
      $length = mb_strlen($statement, $encoding);

      if (preg_match('/:[\w]+/', mb_substr($statement, $pos, $length, $encoding), $matches)) {
        if (array_key_exists($matches[0], $bindVariables)) {
          $statementValue = $this->convertStatementValue($bindVariables[$matches[0]]);
          $statement = Delta_StringUtils::replaceSubstring($statement, $statementValue, $pos, strlen($matches[0]));

          // 一度参照したバインド変数は記憶しておく
          // ('WHERE :foo IS NULL OR foo = :foo' のようなクエリが発行された場合、bindingVariables には 'foo' が格納される)
          if (!in_array($matches[0], $bindingVariables)) {
            $bindingVariables[] = $matches[0];
          }

        } else {
          $message = sprintf('Invalid parameter number: parameter was not defined. [%s]', $matches[0]);
          throw new Delta_ParseException($message);
        }
      }
    }

    // バインドされていない変数が存在する場合
    if (sizeof($bindVariables) != sizeof($bindingVariables)) {
      $diff = array_diff(array_keys($bindVariables), $bindingVariables);

      $message = sprintf('Invalid parameter number: parameter was not defined. [%s]', current($diff));
      throw new Delta_ParseException($message);
    }

    return $statement;
  }

  /**
   * 疑問符プレースホルダの変数をステートメントに展開します。
   *
   * @param string $statement データベースサーバに有効な SQL 文。
   * @param array $bindVariables バインド変数リスト。
   * @return array バインド変数を展開したステートメントを返します。
   * @throws Delta_ParseException ステートメントのプレースホルダにバインドされた変数の数がマッチしない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function bindByInterrogation($statement, array $bindVariables)
  {
    $flag = FALSE;
    $i = 1;

    // バインド値をパラメータ ID 順にソートし直す
    // (bindParam(2, $value); bindParam(1, $value) のように ID が順不同に設定される可能性があるため)
    ksort($bindVariables);

    while (($pos = Delta_StringUtils::searchIndex($statement, '?', 0, '\'')) !== FALSE) {
      if ($flag) {
        $message = 'Invalid parameter number: number of bound variables does not match number of tokens.';
        throw new Delta_ParseException($message);
      }

      $statementValue = $this->convertStatementValue($bindVariables[$i]);
      $statement = Delta_StringUtils::replaceSubstring($statement, $statementValue, $pos, 1);

      $i++;

      if (!isset($bindVariables[$i])) {
        $flag = TRUE;
      }
    }

    // ステートメントのプレースホルダより多くバインド変数がセットされた場合は例外を発生させる
    if ($i <= sizeof($bindVariables)) {
      $message = 'Invalid parameter number: number of bound variables does not match number of tokens.';
      throw new Delta_ParseException($message);
    }

    return $statement;
  }

  /**
   * データベースに格納する値を型に合った適切な形式に変換します。
   *
   * @param string $string 変換対象の値。
   * @return string 変換後の値を返します。変換基準は次の通り。
   *   - string: 文字列にエスケープ処理を加えた上でシングルクォートで括ります。
   *   - int: 値を文字列型にキャストします。
   *   - float: 値を文字列型にキャストします。
   *   - bool: 文字列として 'TRUE'、もしくは 'FALSE' を返します。
   *   - null: 文字列として 'NULL' を返します。
   *   - その他: string と同じ変換処理を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function convertStatementValue($string)
  {
    if (is_string($string)) {
      $string = '\'' . addslashes($string) . '\'';

    } else if (is_int($string) || is_float($string)) {
      $string = (string) $string;

    } else if (is_bool($string)) {
      if ($string) {
        $string = 'TRUE';
      } else {
        $string = 'FALSE';
      }

    } else if (is_null($string)) {
      $string = 'NULL';

    // 予測されない型が指定された場合
    } else {
      $string = '\'' . addslashes($string) . '\'';
    }

    return $string;
  }
}
