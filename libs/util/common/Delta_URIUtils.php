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
 * URI に関する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_URIUtils
{
  /**
   * 指定された URI が絶対パスで構成されているかチェックします。
   * URI の書式が正しいかどうかのチェックは行いません。
   *
   * @param string $uri チェック対象の URI。
   * @return bool URI が絶対パスで構成されている場合に TRUE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isAbsoluteURI($uri)
  {
    if (preg_match('/^[a-z]+:\/\//i', $uri)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * 相対形式のパス を baseURI を基底として絶対パスに変換します。
   *
   * @param string $baseURI 基底 URI。
   * @param string $path 相対パス。
   * @return string 絶対パス形式の URI を返します。変換に失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildAbsoluteURI($baseURI, $path)
  {
    $parse = parse_url($baseURI);
    $array = explode('/', $path);
    $j = sizeof($array);

    // 追加パスの構築
    $appendBuffer = NULL;
    $deep = 0;

    for ($i = 0; $i < $j; $i++) {
      if ($array[$i] === '.') {
        continue;
      } else if ($array[$i] === '..') {
        $deep++;
        continue;
      }

      $appendBuffer .= $array[$i] . '/';
    }

    $appendBuffer = rtrim($appendBuffer, '/');

    // 基底パスの構築
    $array = explode('/', $parse['path']);
    $j = sizeof($array) - $deep - 1;
    $baseBuffer = NULL;

    if ($j > 0) {
      for ($i = 0; $i < $j; $i++) {
        $baseBuffer .= $array[$i] . '/';
      }

    } else {
      return FALSE;
    }

    $relativeBuffer = $baseBuffer . $appendBuffer;

    $uri = sprintf('%s://%s%s',
                   $parse['scheme'],
                   $parse['host'],
                   $relativeBuffer);

    return $uri;
  }

  /**
   * HTML に含まれる全てのパスを取得します。
   *
   * @param string $string 対象となる文字列。
   * @param string $base 相対パスを絶対パスに変換する場合の基底パス。未指定時は変換を行わない。
   * @param array $includes base 指定時に取得対象とするスキーム。'http' や 'mailto' を配列形式で複数指定可能。
   *   未指定時は全てのアンカーを取得。
   * @param string $target 検索対象のタグ。
   *   - a: a タグの href 属性を取得
   *   - img: img タグの src 属性を取得
   * @return array 解析された全てのアンカーを返します。
   *   アンカーには次の情報が配列として含まれます。
   *   <code>
   *   uri: リンク文字列。
   *   label: リンクのラベル。(target が 'a' の場合のみ)
   *   type: リンクのタイプ。(base 指定時のみ)
   *     local: 内部リンク。
   *     external: 外部リンク。
   *     extra: その他。(アンカーが JavaScript コードなど)
   *   </code>
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function parsePathFromString($string, $base = NULL, $includes = NULL, $target = 'a')
  {
    if ($target === 'img') {
      $pattern = "/<img[^>]+src\s*=\s*[\'\"]?\s*([^\'\">\s]+)\s*[\'\"]?[^>]*>/i";
    } else {
      $pattern = "/<a[^>]+href\s*=\s*[\'\"]?\s*([^\'\">\s]+)\s*[\'\"]?[^>]*>(\r\n)?((?!<\/\s*a\s*>).*?)<\/\s*a\s*>/i";
    }

    if ($base !== NULL) {
      $parse = parse_url($base);
      $baseHost = $parse['host'];
    }

    preg_match_all($pattern, $string, $matches);
    $j = sizeof($matches[0]);
    $k = 0;
    $array = array();

    for ($i = 0; $i < $j; $i++) {
      $uri = $matches[1][$i];

      if ($base !== NULL) {
        if (!Delta_URIUtils::isAbsoluteURI($uri)) {
          $uri = Delta_URIUtils::buildAbsoluteURI($base, $uri);
        }

        $parse = parse_url($uri);

        if (empty($parse['host'])) {
          $array[$k]['type'] = 'extra';
        } else if (strcasecmp($baseHost, $parse['host']) === 0) {
          $array[$k]['type'] = 'local';
        } else {
          $array[$k]['type'] = 'external';
        }

        if (is_array($includes)) {
          $parse = parse_url($uri);

          if (!in_array($parse['scheme'], $includes)) {
            continue;
          }
        }
      }

      $array[$k]['uri'] = $uri;

      if (isset($matches[3][$i])) {
        $array[$k]['label'] = $matches[3][$i];
      }

      $k++;
    }

    return $array;
  }

  /**
   * 指定した Web ページに含まれる全てのパスを取得します。
   * (リンクに含まれる文字列は自動的に正しいエンコードへの変換を試みます)
   *
   * @param string $uri 解析対象となる Web ページ。
   * @param array $includes 取得対象とするスキーム。'http' や 'mailto' を配列形式で複数指定可能。
   *   未指定時は全てのアンカーを取得。
   * @param string $target {@link Delta_URIUtils::parsePathFromString()} 関数を参照。
   * @return array 解析された全てのアンカーを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function parsePathFromURI($uri, array $includes = NULL, $target = 'a')
  {
    $sender = new Delta_HttpRequestSender($uri);
    $parser = $sender->send();

    $fromEncoding = $parser->getEncoding();
    $toEncoding = Delta_Config::getApplication()->get('charset.default');

    $contents = $parser->getContents();

    if (strcasecmp($fromEncoding, $toEncoding) !== 0) {
      $contents = mb_convert_encoding($contents, $toEncoding, $fromEncoding);
    }

    $array = Delta_URIUtils::parsePathFromString($contents, $uri, $includes, $target);

    return $array;
  }
  /**
   * クエリ文字列を連想配列に変換します。
   * 同じ名前のパラメータが複数含まれる場合、パラメータ値が配列として展開されます。
   *
   * @param string $query 対象となるクエリ文字列。
   * @return array キーと値で構成される連想配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildQueryAssoc($query)
  {
    $split = array();

    if (Delta_StringUtils::nullOrEmpty($query)) {
      return $split;
    }

    $parts = explode('&', $query);

    foreach ($parts as $part) {
      $array = explode('=', $part);

      if (isset($split[$array[0]])) {
        if (is_array($split[$array[0]])) {
          array_push($split[$array[0]], $array[1]);
        } else {
          $split[$array[0]] = array($split[$array[0]], $array[1]);
        }

      } else {
        $split[$array[0]] = $array[1];
      }
    }

    return $split;
  }

  /**
   * 実行環境のドメイン名と uri のドメイン部が合致するかどうかチェックします。
   *
   * @param string $uri チェック対象の URI。
   * @return string 実行環境のドメイン名と uri のホスト部が合致する場合に TRUE を返します。
   *   CLI 環境では実行環境のドメイン名を取得することができないため、必ず FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isSameDomain($uri)
  {
    if (!Delta_BootLoader::isBootTypeWeb()) {
      return FALSE;
    }

    $info = parse_url($uri);
    $container = Delta_DIContainerFactory::getContainer();
    $hostName = $container->getComponent('request')->getEnvironment('SERVER_NAME');

    if (isset($info['host']) && $info['host'] === $hostName) {
      return TRUE;
    }

    return FALSE;
  }
}
