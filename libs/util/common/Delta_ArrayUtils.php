<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 配列を操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_ArrayUtils
{
  /**
   * キー名変換定数。(小文字変換)
   */
  const CONVERT_TYPE_STRTOLOWER = 1;

  /**
   * キー名変換定数。(大文字変換)
   */
  const CONVERT_TYPE_STRTOUPPER = 2;

  /**
   * キー名変換定数。(camelcaps 変換)
   */
  const CONVERT_TYPE_CAMELCAPS = 4;

  /**
   * 文字要素の前方を表す定数。
   */
  const APPEND_STRING_BEFORE = 1;

  /**
   * 文字要素の後方を表す定数。
   */
  const APPEND_STRING_AFTER = 2;

 /**
   * 配列 array に expects 以外のキーが存在するかどうかチェックします。
   *
   * @param array $array 対象とする配列。
   * @param mixed $expects 除外対象とするキー、またはキーのリスト。
   * @return bool expects 以外のキーが存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isExistKeyWithExpect(array $array, $expects)
  {
    $exists = FALSE;
    $expects = (array) $expects;

    foreach ($array as $name => $value) {
      if (!in_array($name, $expects)) {
        $exists = TRUE;
        break;
      }
    }

    return $exists;
  }

  /**
   * 配列 array の中に search が含まれているかチェックします。
   * 検索時は search の大文字・小文字を区別しません。
   *
   * @param mixed $search 検索する値。スカラー型の値を指定可能。
   * @param array $array 検索対象となる配列。
   * @return bool search が存在する場合は TRUE、存在しない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isExistValueInArray($search, array $array)
  {
    foreach ($array as $key) {
      if (strcasecmp($search, $key) === 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * 配列 array から index 番目の要素を削除し、index 以降の要素を 1 つずつ前にずらします。
   *
   * @param array &$array 対象となる配列。
   * @param int $index 削除するインデックス。
   * @return bool 要素の削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function removeShift(array &$array, $index)
  {
    if (empty($array[$index])) {
      return FALSE;
    }

    unset($array[$index]);

    $last = array_splice($array, $index);
    $array = array_merge($array, $last);

    return TRUE;
  }

  /**
   * 配列 array の任意の位置に要素 value を挿入します。
   *
   * @param array &$array 対象となる配列。
   * @param mixed $value 挿入する要素。
   * @param int $pos 挿入する位置。(開始値は 0)
   * @return bool 要素の挿入に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function insert(array &$array, $value, $pos = 0)
  {
    $last = array_splice($array, $pos);
    array_push($array, $value);
    $array = array_merge($array, $last);

    return TRUE;
  }

  /**
   * 配列 array に含まれる name キーの値を callback の戻り値に置換します。
   * <code>
   * $array = array(
   *   'foo' => 100,
   *   'bar' => array(
   *     'baz' => 200
   *   )
   * );
   *
   * // array('foo' => 100, 'bar' => array('baz' => '*'))
   * Delta_ArrayUtils::replaceValueWithCallback($array, 'bar.baz', function($match) {
   *   return '*';
   * });
   * </code>
   *
   * @param array &$array 対象となる配列。
   * @param string $name 置換対象のキー名。'.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * @param mixed $callback 置換した値を返すコールバック関数。引数には name にマッチする値が格納されます。
   * @return bool name 値の置換に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function replaceValueWithCallback(array &$array, $name, $callback)
  {
    // 対象キーを検索するロジックは Delta_ArrayUtils::removeKey() と同じ
    $split = explode('.', $name);
    $result = FALSE;

    if (sizeof($split) > 1) {
      $_array0 = &$array;
      $j = sizeof($split) - 1;

      for ($i = 0; $i < $j; $i++) {
        if (isset(${'_array' . $i}[$split[$i]])) {
          $current = '_array' . ($i + 1);
          ${$current} = &${'_array' . $i}[$split[$i]];

        } else {
          break;
        }
      }

      if (isset(${$current}[$split[$i]])) {
        ${$current}[$split[$i]] = $callback(${$current}[$split[$i]]);
        $result = TRUE;
      }

    } else if (isset($array[$name])) {
      $array[$name] = $callback(${$current}[$split[$i]]);
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 配列 array から name キーを削除します。
   *
   * @param array &$array 対象となる配列。
   * @param string $name 削除対象のキー名。'.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * @return bool キーの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function removeKey(array &$array, $name)
  {
    $split = explode('.', $name);
    $result = FALSE;

    if (sizeof($split) > 1) {
      $_array0 = &$array;
      $j = sizeof($split) - 1;

      for ($i = 0; $i < $j; $i++) {
        if (isset(${'_array' . $i}[$split[$i]])) {
          $current = '_array' . ($i + 1);
          ${$current} = &${'_array' . $i}[$split[$i]];

        } else {
          break;
        }
      }

      if (isset(${$current}[$split[$i]])) {
        unset(${$current}[$split[$i]]);

        $result = TRUE;
        $i--;

        for (; $i >= 0; $i--) {
          ${$current} = &${'_array' . $i};
          $key = key(${$current});

          if (sizeof(${$current}[$key]) == 0) {
            unset(${$current}[$key]);
          }
        }
      }

    } else if (isset($array[$name])) {
      unset($array[$name]);
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 文字列 string を元に配列を構築します。
   *
   * @param string $string 配列を構成する文字列。
   *   文字列内に '[]'、または '.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * <code>
   * $string = 'foo[bar]';
   * Delta_ArrayUtils::build($string, 'baz'); // array('foo' => 'bar' => 'baz'))
   *
   * $string = 'foo.bar';
   * Delta_ArrayUtils::build($string, 'baz'); // array('foo' => 'bar' => 'baz'))
   * </code>
   *
   * @param string $value 対象となる配列に格納する値。
   * @param array &$mergeArray 構築した配列と mergeArray に指定した配列をマージします。
   * @return array 構築した配列を返します。mergeArray が指定されている場合は値を返しません。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function build($string, $value = NULL, array &$mergeArray = NULL)
  {
    if (strpos($string, '[') !== FALSE) {
      $from = array('[]', '[', ']');
      $to = array('.0', '.', '');

      $string = str_replace($from, $to, $string);
    }

    if (strpos($string, '.') !== FALSE) {
      $split = explode('.', $string);
      $i = sizeof($split);

      ${'_array' . ($i)} = $value;
      $i--;

      for (; $i >= 0; $i--) {
        $current = '_array' . $i;
        ${$current}[$split[$i]] = ${'_array' . ($i + 1)};
      }

      $build = $_array0;

    } else {
      $build = array($string => $value);
    }

    if (is_array($mergeArray)) {
      $mergeArray = Delta_ArrayUtils::mergeRecursive($mergeArray, $build);

      return;
    }

    return $build;
  }

  /**
   * 連想配列を再帰的にマージします。
   * array1 と array2 で同じキーが存在する場合は、array2 の値で上書きされます。
   *
   * @param array $array1 マージ対象の連想配列 1。
   * @param array $array2 マージ対象の連想配列 2。
   * @return array array1 と array2 をマージした連想配列の結果を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function mergeRecursive(array $array1, array $array2)
  {
    foreach($array2 as $key => $value){
      if (isset($array1[$key]) && is_array($value)){
        $array1[$key] = Delta_ArrayUtils::mergeRecursive($array1[$key], $value);

      } else {
        if (is_array($array1)) {
          $array1[$key] = $value;
        } else {
          $array1 = $array2;
        }
      }
    }

    return $array1;
  }

  /**
   * 配列 array から key に対応する値を取得します。
   * <code>
   * $array = array('foo' => array('bar' => 'baz'));
   *
   * // array('bar' => 'baz');
   * $value = Delta_ArrayUtils::find($array, 'foo');
   *
   * // 'baz'
   * $value = Delta_ArrayUtils::find($array, 'foo.bar');
   * </code>
   *
   * @param array $array 検索対象の配列。
   * @param string $key 検索するキー名。'.' (ピリオド) 区切りのキー名が指定された場合は連想配列として認識されます。
   * @param mixed $alternative キーに対応する値が見つからない場合に返す代替値。
   * @param bool &$result 値が見つかった場合は TRUE、見つからない場合は FALSE が格納されます。
   * @return mixed キーに対応する値、もしくは alternative を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function find(array $array, $key, $alternative = NULL, &$keyExists = NULL)
  {
    $keyExists = TRUE;

    // key に '.' を含まない文字の解析 (高速)
    if (strpos($key, '.') === FALSE) {
      if (array_key_exists($key, $array)) {
        $array = $array[$key];

      } else {
        $array = NULL;
        $keyExists = FALSE;
      }

    } else  {
      $split = explode('.', $key);
      $j = sizeof($split);

      // key に '.' を 1 つ含む文字の解析 (中速)
      if ($j == 2) {
        if (isset($array[$split[0]]) && array_key_exists($split[1], $array[$split[0]])) {
          $array = $array[$split[0]][$split[1]];

        } else {
          $array = NULL;
          $keyExists = FALSE;
        }

      // key に '.' を 2 つ以上含む文字の解析 (低速)
      } else {
        for ($i = 0; $i < $j; $i++) {
          if (array_key_exists($split[$i], $array)) {
            $isset = TRUE;

          } else {
            $isset = FALSE;
            $keyExists = FALSE;
          }

          if ($isset && is_array($array[$split[$i]])) {
            $array = $array[$split[$i]];

          } else {
            if ($i + 1 == $j && $isset) {
              $array = $array[$split[$i]];
            } else {
              $array = NULL;
            }

            break;
          }
        }
      }
    }

    if ($array === NULL) {
      $array = $alternative;

    } else if ($alternative !== NULL) {
      $type = gettype($alternative);

      // $array を $alternative 型にキャストする
      switch ($type) {
        case 'double':
          $type = 'float';

        case 'boolean':
          if (is_string($array)) {
            if (strcasecmp($array, 'TRUE') === 0) {
              $array = TRUE;
            } else if (strcasecmp($array, 'FALSE') === 0) {
              $array = FALSE;
            }

          } else {
            $array = (bool) $array;
          }

        case 'integer':
        case 'string':
        case 'array':
          if (gettype($array) !== $type) {
            settype($array, $type);
          }

          break;
      }
    }

    return $array;
  }

  /**
   * 配列 array の最初の要素を取得します。
   * <code>
   * $array = array(100, 200, 300);
   *
   * // 100
   * $value = Delta_ArrayUtils::firstValue($array);
   * </code>
   *
   * @param array $array 検索対象の配列。
   * @return mixed 配列 array の最初の要素を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function firstValue(array $array)
  {
    return $array[0];
  }

  /**
   * 配列 array に key 名で追加された最初の値を取得します。
   * <code>
   * $array = array('foo' => array(100, 200));
   *
   * // 100
   * $value = Delta_ArrayUtils::firstKeyValue($array, 'foo');
   * </code>
   *
   * @param array $array 検索対象の配列。
   * @param string $key 検索するキー名。
   * @return mixed key 名で最初に追加された値を返します。キーが見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function firstKeyValue(array $array, $key)
  {
    $result = NULL;

    if (isset($array[$key])) {
      $result = $array[$key][0];
    }

    return $result;
  }

  /**
   * 配列 array の最後の要素を取得します。
   * <code>
   * $array = array(100, 200, 300);
   *
   * // 300
   * $value = Delta_ArrayUtils::lastValue($array);
   * </code>
   *
   * @param array $array 検索対象の配列。
   * @return mixed 配列 array の最後の要素を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function lastValue(array $array)
  {
    return $array[sizeof($array) - 1];
  }

  /**
   * 配列 array において key 名で最後に追加された値を取得します。
   * <code>
   * $array = array('foo' => array(100, 200));
   *
   * // 200
   * $value = Delta_ArrayUtils::lastKeyValue($array, 'bar');
   * </code>
   *
   * @param array $array 検索対象の配列。
   * @param string $key 検索するキー名。
   * @return mixed key 名で最後に追加された値を返します。キーが見つからない場合は NULL を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function lastKeyValue(array $array, $key)
  {
    $result = NULL;

    if (isset($array[$key])) {
      $index = sizeof($array[$key]) - 1;
      $result = $array[$key][$index];
    }

    return $result;
  }

  /**
   * 配列 array 内の全てのキー名を指定したフォーマットに変換します。
   * {@link array_change_key_case()} と異なり、連想配列にも対応します。
   *
   * @param array $array 対象となる配列。
   * @param string $key 変換フォーマット。Delta_ArrayUtils::CONVERT_TYPE_* 定数を指定。
   * @return array 変換後の配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertKeyNames(array $array, $convertType)
  {
    if (is_array($array)) {
      foreach ($array as $key => $value) {
        unset($array[$key]);

        switch ($convertType) {
          case self::CONVERT_TYPE_STRTOLOWER:
            $key = strtolower($key);
            break;

          case self::CONVERT_TYPE_STRTOUPPER;
            $key = strtoupper($key);
            break;

          case self::CONVERT_TYPE_CAMELCAPS:
            $key = Delta_StringUtils::convertCamelCase($key);
            break;

          default:
            break;
        }

        if (is_array($value)) {
          $array[$key] = self::convertKeyNames($value, $convertType);

        } else {
          $array[$key] = $value;
        }
      }
    }

    return $array;
  }

  /**
   * 配列に含まれる全ての値からホワイトスペースを取り除きます。
   * 配列が連想配列で構成される場合は全ての要素に対し同じ処理を繰り返します。
   *
   * @param array $array 対象となる配列。
   * @return array 配列の値からホワイトスペースを取り除いた結果を返します。
   * @see trim()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function trim(array $array)
  {
    if (!is_array($array)) {
      return $array;
    }

    foreach ($array as $name => &$value) {
      if (is_array($value)) {
        $value = self::trim($value);

      } else {
        $value = trim($value);
      }
    }

    return $array;
  }

  /**
   * XML データを連想配列に変換します。
   * <i>この関数は配列変換時に XML の属性情報を破棄する点に注意して下さい。</i>
   *
   * @param SimpleXMLElement $xml SimpleXMLElement オブジェクト。
   * @return array 変換後の連想配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function xmlToArray($xml)
  {
    return json_decode(json_encode($xml), TRUE);
  }

  /**
   * 配列が持つ要素 (スカラー値) の前後に文字列を結合します。
   * この関数は他時点配列に対応しています。
   *
   * @param array $array 対象となる配列。
   * @param string $append array の各要素に結合する文字列。(対象要素はスカラー値のみ)
   * @param int $type Delta_ArrayUtils::APPEND_STRING_* 定数を指定。ビット和形式で指定することも可能。
   *   既定値は Delta_ArrayUtils::APPEND_STRING_BEFORE|Delta_ArrayUtils::APPEND_STRING_AFTER。
   * @param array $options 文字結合オプション。
   *   - excludes: 結合対象外とする文字を配列形式で指定。
   *   - force: {FALSE} 結合開始位置に append と同じ文字がある場合の結合方法。
   *     TRUE 指定時は文字が重複しても結合するが、FALSE 指定時は結合を行わない。
   * @return array 文字列を結合した新しい配列を返します。
   *
   * <code>
   *   $array = array('foo', 'bar', 'baz', 'NULL');
   *   $options = array('excludes' => array('NULL'));
   *   $type = Delta_ArrayUtils::APPEND_STRING_BEFORE|Delta_ArrayUtils::APPEND_STRING_AFTER;
   *
   *   $array = Delta_ArrayUtils::appendEachString($array, '"', $type, $options);
   *
   *   // "foo", "bar", "baz", NULL
   *   echo implode(', ', $array);
   * </code>
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function appendEachString(array $array, $append, $type = 3, array $options = array())
  {
    $before = NULL;
    $after = NULL;

    $excludes = self::find($options, 'excludes', array());
    $force = self::find($options, 'force', FALSE);

    if ($type & self::APPEND_STRING_BEFORE) {
      $before = $append;
    }

    if ($type & self::APPEND_STRING_AFTER) {
      $after = $append;
    }

    $encoding = Delta_Config::getApplication()->get('charset.default');
    $beforeLength = mb_strlen($before, $encoding);
    $afterLength = mb_strlen($after, $encoding);

    $iterator = function($currentArray, $iterator)
      use ($before,
        $after,
        $excludes,
        $force,
        $encoding,
        $beforeLength,
        $afterLength) {

      $newArray = array();

      foreach ($currentArray as $key => $value) {
        if (is_array($value)) {
          $newArray[$key] = $iterator($value, $iterator);

        } else {
          $isScalar = is_scalar($value);

          // 配列の要素がスカラー値の場合、文字列を結合する
          if ($isScalar && !in_array($value, $excludes)) {
            if ($force) {
              $newArray[$key] = $before . $value . $after;

            } else {
              // 文字列の先頭に追加
              if ($before === NULL) {
                $newArray[$key] = $value;

              } else {
                $compare = mb_substr($value, 0, $beforeLength, $encoding);

                // 開始文字が $append と同じ場合、結合を行わない
                if ($compare === $before) {
                  $newArray[$key] = $value;
                } else {
                  $newArray[$key] = $before . $value;
                }
              }

              // 文字列の末尾に追加
              if ($after !== NULL) {
                // 末尾の文字が $append と同じ場合、結合を行わない
                $length = mb_strlen($value, $encoding);
                $compare = mb_substr($value, $length - $afterLength, $afterLength, $encoding);

                if ($compare !== $after) {
                  $newArray[$key] .= $after;
                }
              }
            }

          // 配列の要素がスカラー値ではない
          } else {
            $newArray[$key] = $value;
          }
        }
      }

      return $newArray;
    };

    return $iterator($array, $iterator);
  }

  /**
   * 配列 array が連想配列形式かチェックします。
   *
   * @param array $array 対象の配列。
   * @return bool 連想配列の場合は TRUE、配列の場合は FALSE を返す。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isAssoc(array $array)
  {
    $i = 0;

    if (is_array($array) && sizeof($array)) {
      foreach ($array as $key => $value) {
        if ($key !== $i++) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * 配列に含まれる数値の平均値を算出します。
   * <code>
   * // 2.5
   * Delta_ArrayUtils::average(array(1, 2, 3, 4));
   * </code>
   *
   * @param array $array 数値要素を持つ配列。
   * @return int 配列に含まれる数値の平均値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function average(array $array)
  {
    $sum = 0;

    foreach ($array as $value) {
      $sum += $value;
    }

    return $sum / sizeof($array);
  }

  /**
   * 配列をマトリクス形式に変換します。
   * <code>
   * $array = array(1, 2, 3, 4, 5, 6, 7);
   *
   * // array(
   * //   array(1, 2, 3),
   * //   array(4, 5, 6),
   * //   array(7, NULL, NULL)
   * // );
   * $value = Delta_ArrayUtils::matrix($array);
   * </code>
   *
   * @param array $array 対象となる配列。
   * @param int $column 最大列。
   * @return array マトリクス形式の配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function matrix(array $array, $column = 3)
  {
    $matrix = array();
    $j = sizeof($array);
    $row = 0;

    for ($i = 0; $i < $j; $i++) {
      for ($k = 0; $k < $column; $k++) {
        $pos = $i + $k;

        if (isset($array[$pos])) {
          $matrix[$row][] = $array[$pos];
        } else {
          $matrix[$row][] = NULL;
        }
      }

      $i = $pos;
      $row++;
    }

    return $matrix;
  }
}
