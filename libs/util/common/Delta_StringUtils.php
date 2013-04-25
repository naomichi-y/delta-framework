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
 * 文字列を操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_StringUtils
{
  /**
   * 英大文字定数。
   */
  const STRING_CASE_LOWER = 1;

  /**
   * 英小文字定数。
   */
  const STRING_CASE_UPPER = 2;

  /**
   * 数値文字定数。
   */
  const STRING_CASE_NUMERIC = 4;

 /**
   * 文字列 string にインデントを設定します。
   * 文字列が複数行の場合は各行に対して同じインデントを設定します。
   *
   * @param string $string 対象となる文字列。
   * @param int $indent インデント数。
   * @param string $char インデントに用いる文字。
   * @return string インデントを設定した文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function indent($string, $indent = 2, $char = ' ')
  {
    $lines = preg_split("/([\n|\r|\r\n])/", $string, -1, PREG_SPLIT_DELIM_CAPTURE);
    $buffer = NULL;
    $char = str_repeat($char, $indent);

    $j = sizeof($lines);

    for ($i = 0; $i < $j; $i += 2) {
      if ($i + 1 < $j) {
        $buffer .= $char . $lines[$i] . $lines[$i + 1];
      } else if (strlen($lines[$i])) {
        $buffer .= $char . $lines[$i];
      }
    }

    return $buffer;
  }

  /**
   * ランダムな文字列を生成します。
   *
   * @param int $length 生成する文字列の長さ。
   * @param int $type 生成する文字列のタイプを Delta_StringUtils::STRING_CASE_* 定数で指定。
   *   Delta_StringUtils::STRING_CASE_LOWER|Delta_StringUtils::STRING_CASE_UPPER|Delta_StringUtils::STRING_CASE_NUMERIC を指定した場合は英数字の組み合わせとなる。
   * @param bool $similar TRUE を指定した場合、視覚的に間違いやすい文字が連続しないよう並び順を調整します。
   *   対象となる文字の組み合わせは次の通り。
   *   - 0 と O (数値のゼロと英大文字のオー)
   *   - 1 と I (数値のイチと英大文字のアイ)
   *   - l と I (英小文字のエルと英大文字のアイ)
   * @return string 生成した文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildRandomString($length = 8, $type = self::STRING_CASE_LOWER, $similar = TRUE)
  {
    $seed = NULL;
    $buffer = NULL;

    if ($type & self::STRING_CASE_LOWER) {
      $seed .= 'abcdefghijklmnopqrstuvwxyz';
    }

    if ($type & self::STRING_CASE_UPPER) {
      $seed .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    if ($type & self::STRING_CASE_NUMERIC) {
      $seed .= '0123456789';
    }

    $max = strlen($seed) - 1;
    $previous = NULL;

    mt_srand();

    for ($i = 0; $i < $length; $i++) {
      $pos = mt_rand(0, $max);
      $current = $seed[$pos];

      if ($similar && $type & self::STRING_CASE_NUMERIC && $i > 0) {
        $contain = FALSE;

        // '0' と 'O'
        if (($previous === '0' && $current === 'O') || ($previous === 'O' && $current === '0')) {
          $contain = TRUE;

        // '1' と 'I'
        } else if (($previous === '1' && $current === 'I') || ($previous === 'I' && $current === '1')) {
          $contain = TRUE;

        // 'l' と 'I'
        } else if (($previous === 'l' && $current === 'I') || ($previous === 'I' && $current === 'l')) {
          $contain = TRUE;
        }

        if ($contain) {
          $length++;
          $current = NULL;
        }

        $previous = $current;
      }

      $buffer .= $current;
    }

    return $buffer;
  }

  /**
   * マルチバイトに対応した {@link trim()} 関数の機能を提供します。
   *
   * @param string $string 変換対象の文字列。
   * @param string $charlist 削除する文字のリスト。未指定時はホワイトスペースを除去します。
   * @param bool $ltrim 文字列の最初から空白 (もしくは他の文字) を取り除く場合に TRUE を指定。
   * @param bool $rtrim 文字列の最後から空白 (もしくは他の文字) を取り除く場合に TRUE を指定。
   * @param bool $emptyToNull 結果が空文字の時に NULL を返したい場合は TRUE を指定。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function trim($string,
    $charlist = NULL,
    $ltrim = TRUE,
    $rtrim = TRUE,
    $emptyToNull = FALSE,
    $encoding = NULL)
  {

    $isTransformEncoding = FALSE;

    if ($encoding !== NULL && strcasecmp($encoding, 'UTF-8') !== 0) {
      $string = mb_convert_encoding($string, 'UTF-8', $encoding);
      $isTransformEncoding = TRUE;
    }

    if ($charlist === NULL) {
      $charlist = '\s\0\x0b\p{Zs}\p{Zl}\p{Zp}';
    } else {
      $charlist = preg_quote($charlist, '/');
    }

    $begin = sprintf('^[%s]+', $charlist);
    $end = sprintf('[%s]+$', $charlist);

    if ($ltrim && $rtrim) {
      $pattern = sprintf('%s|%s', $begin, $end);

    } else if ($ltrim) {
      $pattern = $begin;

    } else if ($rtrim) {
      $pattern = $end;

    } else {
      return $string;
    }

    $pattern = sprintf('/%s/u', $pattern);
    $string = preg_replace($pattern, '', $string);

    if ($string === '' && $emptyToNull) {
      $string = NULL;
    }

    if ($isTransformEncoding) {
      $string = mb_convert_encoding($string, $encoding, 'UTF-8');
    }

    return $string;
  }

  /**
   * 文字列 string を separator 区切りに分割します。
   *
   * @param string $string マルチバイト文字列。
   * @param int $width 分割する文字列長。
   * @param string $separator 分割に使用する文字。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 指定した文字で分割された文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function wordwrap($string, $width = 78, $separator = "\r\n", $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $buffer = NULL;
    $pos = 0;
    $length = mb_strlen($string, $encoding);

    while (strlen($line = mb_substr($string, $pos, $width, $encoding))) {
      $buffer .= $line . $separator;
      $pos += $width;
    }

    $buffer = rtrim($buffer, '=' . $separator);

    return $buffer;
  }

  /**
   * 文字列 string に含まれる改行コード (CR、LF、CRLF) を全て to に変換します。
   *
   * @param string $string 変換対象の文字列。
   * @param string $to 統一する改行コード。
   * @return string 改行コード統一後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function replaceLinefeed($string, $to = "\n")
  {
    $string = str_replace("\r\n", "\n", $string);
    $string = str_replace(array("\r", "\n"), $to, $string);

    return $string;
  }

  /**
   * 指定された文字列を元に SHA1 を用いて 40bit のハッシュ値を生成します。
   * ハッシュ生成のソルトにはアプリケーション固有の秘密鍵 (application.yml に定義された 'secretKey') が使用されます。
   * <code>
   * // 例えば 'cd64e1dc810669d42c0d8b713fe31391391688ce' を返す
   * $string = Delta_StringUtils::buildHash('Hello world!');
   * </code>
   *
   * @param string $string 対象となる文字列。
   * @return string 生成したハッシュ値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildHash($string)
  {
    $hash = '';
    $secretKey = Delta_Config::getApplication()->getString('secretKey');

    // ストレッチング
    for ($i = 0; $i < 1000; $i++) {
      $hash = sha1($hash . $string . $secretKey);
    }

    return $hash;
  }

  /**
   * 文字列 string を camelCaps 記法に変換します。
   *
   * @param string $string 変換対象の文字列。
   * @return camelCaps 形式に変換した文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertCamelCase($string)
  {
    $buffer = NULL;
    $string = trim($string, '_');

    $i = 0;
    $j = strlen($string);

    if ($j == 0) {
      return $string;
    }

    // 文字の先頭に英大文字が 2 文字以上含まれているか
    if (preg_match('/^[A-Z]{2,}/', $string, $matches)) {
      if (strlen($matches[0]) == $j) {
        $buffer = $matches[0];
        $i = $j;

      } else {
        $buffer = substr($matches[0], 0, -1);
        $i = strlen($matches[0]) - 1;
      }

    } else if (ctype_upper($string[0])) {
      $buffer = strtolower($string[0]);
      $i = 1;
    }

    // 'foo__bar' のようにアンダースコアが 2 つ以上連続する場合、2 文字目以降のアンダースコアは削除しない
    $underscore = FALSE;

    for (; $i < $j; $i++) {
      if ($string[$i] !== '_' || $underscore) {
        $buffer .= $string[$i];
        $underscore = FALSE;

      } else if ($i + 1 < $j) {
        $string[$i + 1] = strtoupper($string[$i + 1]);
        $underscore = TRUE;
      }
    }

    return $buffer;
  }

  /**
   * 文字列 string をクラス名の命名記法に変換します。
   *
   * @param string $string 変換対象の文字列。
   * @return string 対象文字列をクラス名の命名記法に変換します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertPascalCase($string)
  {
    return ucfirst(self::convertCamelCase($string));
  }

  /**
   * 文字列 string をアンダースコア形式のファイル名記法に変換します。
   *
   * @param string $string 変換対象の文字列。
   * @param bool $underscore string に含まれるアンダースコアをダブルアンダースコア ('__') に変換する場合は TRUE、変換しない場合は FALSE を指定。
   * string が 'FooBar' の場合 (アンダースコアを含まない)
   *   - TRUE: foo_bar…Delta_StringUtils::convertPascalCase('foo_bar') は 'FooBar' を返す
   *   - FALSE: foo_bar…Delta_StringUtils::convertPascalCase('foo_bar') は 'FooBar' を返す
   *
   * string が 'Foo_Bar' の場合 (アンダースコアを含む)
   *   - TRUE: foo__bar…Delta_StringUtils::convertPascalCase('foo__bar') は 'Foo_Bar' を返す
   *   - FALSE: foo_bar…Delta_StringUtils::convertPascalCase('foo_bar') は 'FooBar' を返す (逆変換が不可能となる)
   * @return string 対象文字列をアンダースコア形式のファイル名記法に変換します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertSnakeCase($string, $underscore = TRUE)
  {
    if ($underscore) {
      $string = str_replace('_', '__', $string);
    }

    // 'FooBarBaz' -> '_Foo_Bar_Baz'
    $string = preg_replace('/[A-Z]{1,}/', '_\\0', $string);

    // '_FOOBar' -> '_FOO_Bar'
    $string = preg_replace('/([A-Z])([A-Z])([^A-Z])/', '\\1_\\2\\3', $string);

    // '_Foo_Bar_Baz' -> 'foo_bar_baz'
    $string = strtolower(ltrim($string, '_'));

    return $string;
  }

  /**
   * 文字列内の大文字を小文字に、小文字を大文字に変換します。
   * <code>
   * // 'hello WORLD!'
   * $string = Delta_StringUtils::convertSwapCase('HELLO world!');
   * </code>
   *
   * @param string $string 対象となる文字列。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function convertSwapCase($string)
  {
    $encoding = Delta_Config::getApplication()->get('charset.default');
    $newString = NULL;
    $j = mb_strlen($string, $encoding);

    for ($i = 0; $i < $j; $i++) {
      $char = mb_substr($string, $i, 1, $encoding);

      if (ctype_upper($char)) {
        $newString .= strtolower($char);
      } else if (ctype_lower($char)) {
        $newString .= strtoupper($char);
      } else {
        $newString .= $char;
      }
    }

    return $newString;
  }

  /**
   * 文字列 string に含まれる target の出現回数を取得します。
   * <code>
   * // 2
   * $count = Delta_StringUtils::countMatches('Hello world!', 'o');
   * </code>
   *
   * @param string $string 対象となる文字列。
   * @param string $target 検索対象文字列。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return int target の出現回数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function countMatches($string, $target, $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $offset = 0;
    $length = mb_strlen($string, $encoding);
    $addOffset = mb_strlen($target, $encoding);
    $count = 0;

    if ($target === NULL) {
      if ($string === NULL) {
        $count = 1;
      }

    } else if ($target === '') {
      if ($string === '') {
        $count = 1;
      }

    } else {
      while (TRUE) {
        if (($offset = mb_strpos($string, $target, $offset, $encoding)) !== FALSE) {
          $offset = $offset + $addOffset;
          $count++;

          if ($offset >= $length) {
            break;
          }

        } else {
          break;
        }
      }
    }

    return $count;
  }

  /**
   * string に含まれる数字のみを結合して取得します。
   *
   * @param string $string 対象文字列。
   * @return string 数字文字列を結合した結果文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getDigitOnly($string)
  {
    $string = (string) $string;
    $length = strlen($string);

    $buffer = NULL;

    for ($i = 0; $i < $length; $i++) {
      if (ctype_digit($string[$i])) {
        $buffer .= $string[$i];
      }
    }

    return $buffer;
  }

  /**
   * 文字列 string を delimiter 区切りに配列分割します。
   * 解析対象のフィールドが excludeTag で括られている場合、excludeTag 内の文字列は delimiter 解析の対象外となります。
   *
   * @param string $string 解析対象の文字列。
   * @param string $delimiter フィールドのデリミタ。
   * @param mixed $excludeTag マッチ対象外のタグ。配列形式で開始タグと終了タグを指定可能。
   * @param bool $trim 結果を返す際に excludeTag 文字を含める場合は TRUE、含めない場合は FALSE を指定。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return array 文字列を delimiter で分割した配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function splitExclude($string,
    $delimiter = ',',
    $excludeTag = '"',
    $trim = TRUE,
    $encoding = NULL)
  {
    $string .= $delimiter;
    $split = array();

    $offset = 0;
    $length = strlen($delimiter);

    if (!is_array($excludeTag)) {
      $excludeTag = array($excludeTag);
      $excludeTag[1] = $excludeTag[0];
    }

    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    while ($pos = self::searchIndex($string, $delimiter, $offset, $excludeTag, $encoding)) {
      $value = mb_substr($string, $offset, $pos - $offset, $encoding);

      if ($trim) {
        $temp = trim($value);

        if (substr($temp, 0, 1) == $excludeTag[0] && substr($temp, -1) == $excludeTag[1]) {
          $value = substr($temp, strlen($excludeTag[0]), - strlen($excludeTag[1]));
        }
      }

      $split[] = $value;
      $offset = $pos + $length;
    }

    if (sizeof($split) == 0) {
      $split[] = rtrim($string, $delimiter);
    }

    return $split;
  }

  /**
   * 文字列 string から search が最初に現れる位置を取得します。
   * {@link strpos()} 関数と異なり、excludeTag で囲まれた内部の文字列はマッチ対象外となります。
   *
   * @param string $string 検索対象の文字列。
   * @param string $search 検索文字列。
   * @param int $offset 検索開始位置。offset 指定時も返される位置は string の先頭からの相対位置となります。
   * @param mixed $excludeTag 検索から除外するタグ。
   *   ' (シングルクォート) を渡した場合、シングルクォートで括られた中に存在する $search はマッチ対象外となります。
   *   配列形式で開始・終了タグを指定することも可能です。
   *   またバックスラッシュでエスケープされた文字はタグと見なされません。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string search が最初に現れる位置を返します。見つからない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function searchIndex($string, $search, $offset = 0, $excludeTag = '"', $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $j = mb_strlen($string, $encoding);

    if (is_array($excludeTag)) {
      $startTag = $excludeTag[0];
      $endTag = $excludeTag[1];

    } else {
      $startTag = $endTag = $excludeTag;
    }

    $exclude = FALSE;
    $pos = FALSE;
    $searchLength = mb_strlen($search, $encoding);

    for ($i = $offset; $i < $j; $i++) {
      // 除外タグ内
      if ($exclude) {
        // $endTag が見つかる位置を検索
        if (($k = mb_strpos($string, $endTag, $i, $encoding)) !== FALSE) {
          // $endTag がエスケープされた形式であれば $endTag とは見なさない
          if (mb_substr($string, $k - 1, 1, $encoding) != '\\') {
            $i = $k;
            $exclude = FALSE;
          }

        // $endTag が存在しない場合 (タグが片方しかない不正な文字列)
        } else {
          $pos = FALSE;
          break;
        }

      // 検索文字列を検知した場合
      } else if (strcmp(mb_substr($string, $i, $searchLength, $encoding), $search) == 0) {
        $pos = $i;
        break;

      // 除外タグ外
      } else {
        $nextExcludeTagPos = mb_strpos($string, $startTag, $i, $encoding);
        $nextSearchPos = mb_strpos($string, $search, $i, $encoding);

        if ($nextExcludeTagPos === FALSE) {
          if ($nextSearchPos === FALSE) {
            $pos = FALSE;
            break;

          } else {
            $pos = $nextSearchPos;
            break;
          }

        } else {
          if ($nextSearchPos === FALSE) {
            $pos = FALSE;
            break;

          } else {
            if ($nextExcludeTagPos < $nextSearchPos) {
              $i = $nextExcludeTagPos;
              $exclude = TRUE;
            } else {
              $i = $nextSearchPos - 1;
            }
          }
        }
      }
    }

    return $pos;
  }

  /**
   * 文字列 string 内の from を全て to に置換します。
   * excludeTag で囲まれた文字列内は置換対象外となります。
   *
   * @param string $from 置換前の文字列。
   * @param string $to 置換後の文字列。
   * @param string $string 置換対象の文字列。
   * @param mixed $excludeTag マッチ対象外のタグ。配列形式で開始タグと終了タグを指定可能。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 置換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function excludeReplace($from, $to, $string, $excludeTag = '"', $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $fromLength = mb_strlen($from, $encoding);
    $toLength = mb_strlen($to, $encoding);

    $oldPos = $newPos = -1;
    $offset = 0;

    while (($newPos = self::searchIndex($string, $from, $offset, $excludeTag, $encoding)) !== FALSE) {
      $string = self::insert($string, $to, $newPos, $fromLength, $encoding);

      $oldPos = $newPos;
      $offset = $newPos + $toLength;
    }

    return $string;
  }

  /**
   * 文字列 string の位置 to に insert 文字列を挿入します。
   *
   * @param string $string 対象文字列。
   * @param string $insert 挿入文字列。
   * @param int $to 挿入位置。
   * @param int $range 挿入位置で元の文字列を上書きする場合の削除文字数。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function insert($string, $insert, $to = 0, $range = NULL, $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $before = mb_substr($string, 0, $to, $encoding);

    if ($range === NULL) {
      $length = mb_strlen($string, $encoding) - mb_strlen($before, $encoding);
      $after = mb_substr($string, $to, $length, $encoding);

    } else {
      $offset = $to + $range;

      $length = mb_strlen($string, $encoding);
      $length = mb_strlen(mb_substr($string, $offset, $length - $offset, $encoding), $encoding);

      $after = mb_substr($string, $offset, $length, $encoding);
    }

    $string = $before . $insert . $after;

    return $string;
  }

  /**
   * 文字列 string の長さが length になるよう、文字列の右端を切り捨てます。
   * 文字列が切り捨てられた場所には suffix が追加されます。
   *
   * @param string $string 対象文字列。
   * @param int $length 文字列の上限。
   * @param string $suffix 切り捨てが発生した箇所に追加する文字列。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function truncate($string, $length = 32, $suffix = '...', $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    if (mb_strlen($string, $encoding) > $length) {
      $stringLength = $length - mb_strlen($suffix, $encoding);

      if ($stringLength > 0) {
        $string = mb_substr($string, 0, $stringLength, $encoding) . $suffix;
      } else {
        $string = mb_substr($suffix, 0, $length);
      }
    }

    return $string;
  }

  /**
   * 文字列 string の長さが length になるよう、文字列の中央を切り捨てます。
   * 文字列が切り捨てられた場所には suffix が追加されます。
   *
   * @param string $string 対象文字列。
   * @param int $length 文字列の上限。
   * @param string $suffix 切り捨てが発生した箇所に追加する文字列。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function truncateCenter($string, $length = 32, $suffix = '...', $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $stringLength = mb_strlen($string, $encoding);

    if ($stringLength > $length) {
      $suffixLength = mb_strlen($suffix, $encoding);
      $freeLength = $length - $suffixLength;
      $i = $freeLength / 2;

      if ($freeLength % 2 == 0) {
        $string = mb_substr($string, 0, $i, $encoding) . $suffix . mb_substr($string, -$i, $i, $encoding);
      } else {
        $string = mb_substr($string, 0, $i + 1, $encoding) . $suffix . mb_substr($string, -$i, $i, $encoding);
      }
    }

    return $string;
  }

  /**
   * HTML 特殊文字をエスケープします。
   * 変換される文字は application.yml の 'htmlEscape' 属性に設定した形式によります。
   * (デフォルトで使用される関数は {@link htmlspecialchars()}、ENT_QUOTES フラグが適用される)
   *
   * @param mixed $values 変換対象のデータ。文字列や配列のほか、オブジェクトを指定することも可能。
   *   - 配列が指定された場合: 返されるデータは {@link Delta_HTMLEscapeArrayDecorator} によってラッピングされたオブジェクトを返します。
   *   - オブジェクトが指定された場合: 返されるデータは {@link Delta_HTMLEscapeObjectDecorator} によってラッピングされたオブジェクトを返します。
   *   配列の中にネスト構造で格納された別の配列やオブジェクトについても、{@link Delta_HTMLEscapeDecorator} によって自動的にラッピングされた値が返されます。
   * @param int $flags 変換に使用するフラグ。詳しくは {@link htmlentities()} 関数を参照。
   *   未指定の場合は 'htmlEscape.flags' 属性に指定された形式で変換を行う。
   * @param bool $doubleEncode 既存の全ての HTML エンティティを再エンコードするかどうかの指定。
   * @param bool $allows 変換対象外とする HTML タグ。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 変換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function escape($values,
    $flags = NULL,
    $doubleEncode = FALSE,
    array $allows = array(),
    $encoding = NULL)
  {
    $result = NULL;
    $config = Delta_Config::getApplication();

    if ($encoding === NULL) {
      $encoding = $config->get('charset.default');
    }

    if (is_string($values)) {
      $htmlEscape = $config->get('htmlEscape');
      $function = $htmlEscape->get('function');

      if ($flags === NULL) {
        $flags = $htmlEscape->get('flags');
      }

      if ($doubleEncode) {
        $result = call_user_func($function, $values, $flags, $encoding);
      } else {
        $result = call_user_func($function, $values, $flags, $encoding, FALSE);
      }

    } else if (is_array($values)) {
      $result = new Delta_HTMLEscapeArrayDecorator($values);

    } else if (is_object($values)) {
      $result = new Delta_HTMLEscapeObjectDecorator($values);

    } else {
      $result = $values;
    }

    if (sizeof($allows)) {
      foreach($allows as $allow) {
        $regexp = '/&lt;\/?'. $allow . '( .*?&gt;|\/?&gt;)/i';
        $result = preg_replace_callback($regexp,
          function($string) {
            $from = array('&lt;', '&gt;', '&quot;');
            $to = array('<', '>', "\"");

            return str_replace($from, $to, $string[0]);
          },
          $result);
      }
    }

    return $result;
  }

  /**
   * string が NULL か空文字列であるかどうかチェックします。
   *
   * @param string $string 対象となる文字列。
   * @return bool string が NULL または空文字列の場合は TRUE、値が格納されている場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function nullOrEmpty($string)
  {
    if ($string === NULL || $string === '') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * 文字列の一部を置換します。
   * この関数は、{@link substr_replace()} 関数のマルチバイト対応版です。
   *
   * @param string $string 対象となる文字列。
   * @param string $replacement 置換文字列。
   * @param int $start 正の場合、置換は string で start 番目の文字から始まります。負の場合、置換は string の終端から string 番目の文字から始まります。
   * @param int $length 正の場合、string の置換される部分の長さを表します。負の場合、置換を停止する位置が string の終端から何文字目かを表します。
   * @param string $encoding 文字列のエンコーディング形式。未指定の場合は application.yml に定義された 'charaset.default' が使用される。
   * @return string 置換後の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function replaceSubstring($string, $replacement, $start, $length = NULL, $encoding = NULL)
  {
    if ($encoding === NULL) {
      $encoding = Delta_Config::getApplication()->get('charset.default');
    }

    $stringLength = mb_strlen($string, $encoding);

    if ($start < 0) {
      $start = max(0, $stringLength + $start);
    } else if ($start > $stringLength) {
      $start = $stringLength;
    }

    if ($length < 0) {
      $length = max(0, $stringLength - $start + $length);
    } else if ($length === NULL || ($length > $stringLength)) {
      $length = $stringLength;
    }

    if (($start + $length) > $stringLength) {
      $length = $stringLength - $start;
    }

    $string = mb_substr($string, 0, $start, $encoding)
      .$replacement
      .mb_substr($string, $start + $length, $stringLength - $start - $length, $encoding);

    return $string;
  }

  /**
   * gzip 圧縮された文字列をデコードします。
   * <i>この関数を使うには、PHP のコンパイル時に {@link http://www.php.net/manual/zlib.setup.php Zlib モジュール} を有効化しておく必要があります。</i>
   *
   * @param string $data デコードするデータ。
   * @return string デコードされた文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function decodeGzip($data)
  {
    // マニュアルには gzdecode() 関数の存在が示されているが、現時点 (PHP 5.3.5) で SVN 版にしかコミットされていない
    $path = tempnam(APP_ROOT_DIR . '/tmp','gz_');
    file_put_contents($path, $data);

    ob_start();
    readgzfile($path);
    $data = ob_get_clean();

    unlink($path);

    return $data;
  }

  /**
   * 文字列 string を delimiter によって分割します。
   * 分割された文字列の前後にあるホワイトスペースは除去されます。
   *
   * @param string $delimiter デリミタ文字列。
   * @param string $string 対象となる文字列。
   * @return array delimiter によって分割された配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function trimExplode($delimiter = ',', $string)
  {
    if (is_string($delimiter)) {
      $regexp = '/\\s*(?:' . preg_quote($delimiter) . ')\\s*/';
      $string = trim(preg_replace($regexp, $delimiter, $string));

      return explode($delimiter, $string);
    }

    return $string;
  }

  /**
   * 文字列 string を Unicode エスケープします。
   *
   * @param string $string 対象となる文字列。(UTF-8)
   *   英数字、及び '-' (ハイフン)、'.' (ピリオド) を除く全ての文字がエスケープ対象となる。
   * @return string Unicode エスケープした文字列 (\uXXXX) を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function escapeUnicode($string)
  {
    return preg_replace_callback('/[^-\.0-9a-zA-Z]+/u', function($matches) {
      $utf16 = mb_convert_encoding($matches[0], 'UTF-16', 'UTF-8');
      return preg_replace('/[0-9a-f]{4}/', '\u$0', bin2hex($utf16));

    }, $string);
  }

  /**
   * 配列要素を文字列によって連結します。
   *
   * @param string $glue 連結に用いる文字列。
   * @param array $array 連結したい文字列の配列。
   * @param bool $afterInsert glue の挿入位置。TRUE の場合は array 文字列の後、FALSE の場合は array 文字列より先に挿入する。
   * @param mixed $callback array 文字列を連結する際に実行するコールバック関数。
   * @return string array 文字列を glue によって連結した結果を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function joinArray($glue, array $array, $afterInsert = TRUE, $callback = NULL)
  {
    $buffer = NULL;

    if ($afterInsert) {
      foreach ($array as $value) {
        if ($callback) {
          $buffer .= call_user_func($callback, $value);
        } else {
          $buffer .= $value;
        }

        next($array);

        if (current($array)) {
          $buffer .= $glue;
        }
      }

    } else {
      foreach ($array as $value) {
        if ($callback) {
          $buffer .= $glue . call_user_func($callback, $value);
        } else {
          $buffer .= $glue . $value;
        }
      }
    }

    return $buffer;
  }

  /**
   * 対象となる文字列を quoted-printable 形式でエンコード (8bit またはリテラルの文字列データを 7bit 形式に変換) します。
   * <i>この関数は {@link quoted_printable_encode()} 関数と似ていますが、linefeed、width を指定できる点が異なります。</i>
   *
   * @param string $string 対象となる文字列。
   * @param string $linefeed 改行コード。
   * @param int $width 1 行辺りの最大長。
   * @return string エンコード結果の文字列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function encodeQuotedPrintable($string, $linefeed = "\n", $width = 76)
  {
    // string に含まれる改行コードを $linefeed に統一
    $string = preg_replace('!(\r\n|\r|\n)!', $linefeed, $string);

    $from = array();
    $from[] = '/([\000-\010\013\014\016-\037\075\177-\377])/e';
    $from[] = '/([\011\040])' . $linefeed . '/e';

    $replace = array();
    $replace[] = "'=' . sprintf('%02X', ord('\\1'))";
    $replace[] = "'=' . sprintf('%02X', ord('\\1')) . '" . $linefeed . "'";

    $string = preg_replace($from, $replace, $string);

    // $string に含まれる改行コードを 16 進数表記に変換
    $string = str_replace($linefeed, sprintf('=%02X', ord($linefeed)), $string);

    // 改行処理
    $buffer = NULL;
    $offset = 0;

    if (strlen($string) > $width) {
      while (($line = substr($string, $offset, $width)) !== FALSE) {
        $length = strlen($line);

        if ($length == $width) {
          if (($pos = strpos($line, '=', $length - 3)) !== FALSE) {
            $buffer .= substr($line, 0, $pos) . '=' . $linefeed;
            $pos = $length - $pos;

          } else {
            $buffer .= substr($line, 0, $length - 1) . '=' . $linefeed;
            $pos = 1;
          }

          $offset += $length - $pos;

        } else {
          $buffer .= $line;
          $offset += $length;
        }
      }

      $string = rtrim($buffer, '=' . $linefeed);
    }

    return $string;
  }

  /**
   * JSON 形式の文字列を整形されたテキストに変換します。
   *
   * @param string $json JSON 形式の文字列データ。
   * @param string $linefeed 改行コード。
   * @param string $whitespace ホワイトスペース。
   * @param int $indent インデント数。
   * @return string 整形された JSON 文字列を返します。形式が不正な場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function formatJSONString($json, $linefeed = "\n", $whitespace = ' ', $indent = 2)
  {
    $json = trim($json);
    $array = json_decode($json, TRUE);

    if (!is_array($array)) {
      return FALSE;
    }

    $buffer = NULL;
    $currentIndent = $indent;

    $callback = function($array, $arrayType, &$buffer, &$currentIndent)
      use (&$callback, $linefeed, $whitespace, $indent) {

      foreach ($array as $name => $value) {
        if (is_array($value)) {
          if ($arrayType === 'assoc') {
            $buffer .= sprintf('%s"%s": ',
              str_repeat($whitespace, $currentIndent),
              $name);

          } else {
            $buffer .= sprintf('%s', str_repeat($whitespace, $currentIndent));
          }

          $currentIndent += $indent;
          $inline = NULL;

          if (Delta_ArrayUtils::isAssoc($value)) {
            $buffer .= '{';
            $callback($value, 'assoc', $inline, $currentIndent);
            $tag = '}';

          } else {
            $buffer .= '[';
            $callback($value, 'array', $inline, $currentIndent);
            $tag = ']';
          }

          $currentIndent -= $indent;

          if (strlen($inline)) {
            $buffer .= sprintf('%s%s%s%s%s,%s',
              $linefeed,
              $inline,
              $linefeed,
              str_repeat($whitespace, $currentIndent),
              $tag,
              $linefeed);

          } else {
            $buffer .= sprintf('%s%s,%s',
              $inline,
              $tag,
              $linefeed);
          }

        } else {
          if (is_string($value)) {
            $value = sprintf('"%s"', $value);
          }

          if ($arrayType === 'assoc') {
            $buffer .= sprintf('%s"%s": %s,%s',
              str_repeat($whitespace, $currentIndent),
              $name,
              $value,
              $linefeed);
          } else {
             $buffer .= sprintf('%s%s,%s',
              str_repeat($whitespace, $currentIndent),
              $value,
              $linefeed);
          }
        }
      }

      $buffer = rtrim($buffer, $linefeed . ',');
    };

    if (Delta_ArrayUtils::isAssoc($array)) {
      $arrayType = 'assoc';
    } else {
      $arrayType = 'array';
    }

    $callback($array, $arrayType, $buffer, $currentIndent);

    for ($i = $currentIndent; $i > $indent; $i = $i - $indent) {
      $currentIndent -= $indent;
      $buffer .= str_repeat($whitespace, $currentIndent) . '},' . $linefeed;
    }

    if (substr($json, 0, 1) === '{') {
      $prefix = '{';
      $suffix = '}';
    } else {
      $prefix = '[';
      $suffix = ']';
    }

    $buffer = rtrim($buffer);

    if (strlen($buffer)) {
      $buffer = $prefix . $linefeed . $buffer . $linefeed . $suffix;
    } else {
      $buffer = $prefix . $suffix;
    }

    return $buffer;
  }
}
