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
 * ファイルを操作する汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */

class Delta_FileUtils
{
  /**
   * 指定されたファイルパスが相対パスであるかどうかチェックします。
   * 実際にパスが存在するかどうかのチェックは行いません
   *
   * @param string $path 対象パス。
   * @return bool 指定されたパスが相対パスの場合に TRUE を返します。
   * @throws Delta_IOException 解析できないパス文字列が渡された場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isAbsolutePath($path)
  {
    $result = FALSE;

    if (Delta_StringUtils::nullOrEmpty($path)) {
      throw new Delta_IOException('Specified path is NULL or empty.');
    }

    // Linux
    if (DIRECTORY_SEPARATOR == '/' && substr($path, 0, 1) == '/') {
      $result = TRUE;

    // Windows
    } else if (preg_match('/^[a-zA-Z]{1,2}\:|^\\\\/', $path)) {
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 指定されたパスを絶対パスに変換します。(基底パスは APP_ROOT_DIR)
   *
   * @param string $path 対象パス。
   * @return string 変換後の絶対パスを返します。
   * @throws Delta_IOException 指定されたパスが存在しない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildAbsolutePath($path)
  {
    if (self::isAbsolutePath($path)) {
      if (is_readable($path)) {
        return $path;
      }

    } else {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
      $realPath = realpath($path);

      if ($realPath !== FALSE) {
        return $realPath;
      }
    }

    $message = sprintf('Specified path does not exist. [%s]', $path);
    throw new Delta_IOException($message);
  }

  /**
   * 指定したディレクトリからファイルを再帰的に検索します。
   * <i>この関数は実行に時間がかかる可能性があります。必要に応じて PHP オプション の 'max_execution_time' 値を変更して下さい。</i>
   *
   * @param string $searchPath 検索対象ディレクトリ。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param string $searchFile 検索するファイル名。'/.../' 形式で正規表現パターンも指定可能。
   * @param array $options 検索オプション。
   *   - excludes: 検索対象外とするパスのリストを配列形式で指定。
   *   - basePath: 検索結果で返すパスリストの基底パスを指定。searchPath と同じ値を指定した場合、検索結果は searchPath からの相対パスとなる。
   *   - separator: ファイルリストを返す際のパスセパレータ。規定値は DIRECTORY_SEPARATOR。
   *   - hidden: ドットから始まる隠しディレクトリを検索対象とする場合は TRUE、対象外とする場合は FALSE を指定。規定値は TRUE。
   *   - pattern: マッチしたファイル名から文字列を抽出するための正規表現パターン。例えば '/[0-9]+/' を指定した場合、マッチしたファイル名に含まれる数値文字列のみを返す。
   *   - directory: ディレクトリも検索対象とする場合は TRUE を指定。既定値は FALSE。
   * @return array ファイルが存在するパスのリストを返します。
   * @throws Delta_IOException 対象ディレクトリが存在しない場合に発生。
   * @see 1.60
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function search($searchPath, $searchFile, array $options = array())
  {
    $array = array();
    $array['excludes'] = Delta_ArrayUtils::find($options, 'excludes', array());
    $array['basePath'] = Delta_ArrayUtils::find($options, 'basePath');
    $array['separator'] = Delta_ArrayUtils::find($options, 'separator', DIRECTORY_SEPARATOR);
    $array['searchFileIsRegexp'] = FALSE;
    $array['index'] = 0;
    $array['hidden'] = Delta_ArrayUtils::find($options, 'hidden', TRUE);
    $array['pattern'] = Delta_ArrayUtils::find($options, 'pattern');
    $array['directory'] = Delta_ArrayUtils::find($options, 'directory', FALSE);

    $searchPath = self::buildAbsolutePath($searchPath);
    $searchPath = rtrim($searchPath, $array['separator']);

    if (!is_dir($searchPath)) {
      $message = sprintf('Directory is not exist. [%s]', $searchPath);
      throw new Delta_IOException($message);
    }

    if (substr($searchFile, 0, 1) === '/') {
      $array['searchFileIsRegexp'] = TRUE;
    }

    if ($array['basePath'] !== NULL && strpos($searchPath, $array['basePath']) == 0) {
      $array['index'] = strlen($array['basePath']);
    }

    $searchPath = str_replace(array('/', '\\'),
      array($array['separator'],
      $array['separator']),
      $searchPath);

    $results = array();
    self::searchCallback($searchPath, $searchFile, $array, $results);

    return $results;
  }

  /**
   * ディレクトリを再帰的にチェックして、searchFile にマッチしたファイルを配列 results に追加します。
   *
   * @param string $searchPath Delta_FileUtils::search() 関数を参照。
   * @param string $searchFile Delta_FileUtils::search() 関数を参照。
   * @param array $options Delta_FileUtils::search() 関数を参照。
   * @param array $results searchFile が存在するパスのリストを格納。
   * @access private
   * @see 1.60
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function searchCallback($searchPath,
    $searchFile,
    array $options,
    array &$results)
  {
    $addFile = function(&$results, $realPath) use ($options) {
      $file = substr($realPath, $options['index']);

      if ($options['pattern'] === NULL) {
        $results[] = $file;
      } else if (preg_match($options['pattern'], $file, $matches)) {
        $results[] = $matches[0];
      }
    };

    if (!in_array($searchPath, $options['excludes'])) {
      if (!$options['searchFileIsRegexp']) {
        $realPath = $searchPath . $options['separator'] . $searchFile;

        if (is_file($realPath)) {
          $addFile($results, $realPath);
        } else if ($options['directory'] && is_dir($realPath)) {
          $results[] = $realPath;
        }
      }

      $files = scandir($searchPath);

      foreach ($files as $file) {
        if ($file == '.' || $file == '..' || (!$options['hidden'] && substr($file, 0, 1) == '.')) {
          continue;
        }

        $realPath = $searchPath . $options['separator'] . $file;

        if (is_dir($realPath)) {
          if ($options['directory'] && $options['searchFileIsRegexp'] && preg_match($searchFile, $realPath, $matches)) {
            $addFile($results, $realPath);
          }

          self::searchCallback($realPath, $searchFile, $options, $results);

        } else if ($options['searchFileIsRegexp'] && preg_match($searchFile, $file, $matches)) {
          $addFile($results, $realPath, $options['index']);
        }
      }
    }
  }

  /**
   * ディレクトリを再帰的に作成します。
   *
   * @param string $path 作成するディレクトリパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param mixed $faculty ディレクトリに付与するパーミッションを指定。配列形式でオーナーやグループ名を付与することも可能。
   *     - 第 1 パラメータ: 付与するパーミッション。現在設定されている umask の影響は受けません。
   *     - 第 2 パラメータ: ディレクトリを所有するユーザ名、あるいはユーザ番号。未指定の場合は現在の実行ユーザとなる。
   *     - 第 3 パラメータ: グループ名、あるいはグループ番号。未指定の場合は実行ユーザが所属するグループとなる。
   *   <i>Windows では無視されます。</i>
   * @param bool $remove 既にディレクトリが存在する場合に内容を削除する場合は TRUE、上書きする場合は FALSE を指定。
   * @return bool ディレクトリの作成が成功した場合に TRUE を返します。
   * @throws Delta_IOException ディレクトリが既に存在する (かつ、remove が FALSE) の場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createDirectoryRecursive($path, $faculty = 0775, $remove = FALSE)
  {
    if (is_array($faculty)) {
      $mode = $faculty[0];

    } else {
      $mode = $faculty;
    }

    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    $isDirectory = is_dir($path);

    if ($isDirectory) {
      if ($remove) {
        self::deleteDirectory($path);
        $isDirectory = FALSE;

      } else {
        $message = sprintf('Directory already exists. [%s]', $path);
        throw new Delta_IOException($message);
      }
    }

    if (!$isDirectory) {
      if (is_file($path)) {
        $message = sprintf('Exists already same name.', $path);
        throw new Delta_IOException($message);

      } else {
        // umask の影響を受けないよう変更
        $old = umask(0);

        $result = mkdir($path, $mode, TRUE);
        $argc = sizeof($faculty);

        if ($argc > 1 && $result) {
          if ($argc == 2) {
            $result = self::chownRecursive($path, $faculty[1]);
          } else {
            $result = self::chownRecursive($path, $faculty[1], $faculty[2]);
          }
        }

        // umask を元の値に戻しておく
        umask($old);
      }

      return $result;
    }

    return FALSE;
  }

  /**
   * 指定したディレクトリ下のファイル及びディレクトリのパーミッションを再帰的に変更します。
   *
   * @param string $path 対象とするパス。
   * @param int $mode 変更後のパーミッション。umask の影響は受けません。
   * @return bool パーミッションの変更が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function chmodRecursive($path, $mode = 0755)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    $old = umask(0);

    if (is_readable($path) && chmod($path, $mode)) {
      if (is_dir($path)) {
        $result = self::chmodRecursiveCallback($path, $mode);
      } else {
        $result = TRUE;
      }

    } else {
      $result = FALSE;
    }

    umask($old);

    return $result;
  }

  /**
   * @access private
   * @see Delta_FileUtils::chmodRecursive()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function chmodRecursiveCallback($path, $mode)
  {
    $files = scandir($path);

    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $filePath = $path . DIRECTORY_SEPARATOR . $file;

      if (is_dir($filePath)) {
        if (!self::chmodRecursive($filePath, $mode)) {
          return FALSE;
        }

      } else if (!chmod($filePath, $mode)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * 指定したディレクトリ下のファイル及びディレクトリのオーナー、グループを再帰的に変更します。
   *
   * @param string $path 対象とするパス。
   * @param string $owner 変更後のユーザ名あるいはユーザ番号。NULL 指定時は変更を加えません。
   * @param string $owner 変更後のグループ名あるいはグループ番号。NULL 指定時は変更を加えません。
   * @return bool 権限の変更が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function chownRecursive($path, $owner = NULL, $group = NULL)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    if (is_readable($path)) {
      if ($owner !== NULL && !chown($path, $owner)) {
        return FALSE;
      }

      if ($group !== NULL && !chgrp($path, $group)) {
        return FALSE;
      }

      if (is_dir($path)) {
        $result = self::chownRecursiveCallback($path, $owner, $group);

      } else {
        $result = TRUE;
      }

      return $result;
    }

    return FALSE;
  }

  /**
   * @access private
   * @see Delta_FileUtils::chownRecursive()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function chownRecursiveCallback($path, $owner, $group)
  {
    $files = scandir($path);

    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $filePath = $path . DIRECTORY_SEPARATOR . $file;

      if (is_dir($filePath)) {
        if (!self::chownRecursive($filePath, $owner, $group)) {
          return FALSE;
        }

      } else {
        if ($owner !== NULL && !chown($filePath, $owner)) {
          return FALSE;
        }

        if ($group !== NULL && !chgrp($filePath, $group)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * 指定したファイルを削除します。
   *
   * @param string $path 削除対象のファイル。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @return bool ファイルの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function deleteFile($path)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    if (is_file($path)) {
      unlink($path);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * 指定したディレクトリ下の全てのディレクトリ、ファイルを削除します。
   *
   * @param string $path 削除対象のディレクトリパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param bool $hidden ドットから始まる隠しファイルを削除する場合は TRUE、削除しない場合は FALSE を指定。
   * @return bool ファイルの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function deleteDirectory($path, $hidden = TRUE)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    if (!is_dir($path)) {
      return FALSE;
    }

    $files = scandir($path);

    foreach ($files as $file) {
      if ($file == '.' || $file == '..' || (!$hidden && substr($file, 0, 1) === '.')) {
        continue;
      }

      $realPath = $path . DIRECTORY_SEPARATOR . $file;

      if (is_dir($realPath)) {
        if (!self::deleteDirectory($realPath)) {
          return FALSE;
        }

      } else {
        if (!unlink($realPath)) {
          return FALSE;
        }
      }
    }

    // 読み取り専用ファイルや隠しファイルが残る場合があるのでエラー出力を制御しておく
    return @rmdir($path);
  }

  /**
   * 指定したディレクトリ下にあるファイルの総容量を取得します。
   *
   * @param string $searchPath チェック対象のディレクトリ。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param bool $includeDirectorySize ディレクトリ自体のサイズを総容量に含める場合は TRUE を指定。(Windows では無効)
   * @return int ディレクトリの総容量を返します。(単位はバイト数)
   * @throws Delta_IOException 対象ディレクトリが存在しない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function sizeOfDirectory($searchPath, $includeDirectorySize = FALSE)
  {
    $searchPath = self::buildAbsolutePath($searchPath);

    $size = 0;
    self::sizeOfDirectoryCallback($searchPath, $includeDirectorySize, $size);

    return $size;
  }

  /**
   * ディレクトリを再帰的にチェックして、ファイルサイズの総容量を求めます。
   *
   * @param string $searchPath チェック対象のディレクトリ。
   * @param bool $includeDirectorySize ディレクトリ自体のサイズを総容量に含める場合は TRUE を指定。(Windows では無効)
   * @param int $size ディレクトリの総容量を保持します。
   * @access private
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function sizeOfDirectoryCallback($searchPath, $includeDirectorySize, &$size)
  {
    $files = scandir($searchPath);

    foreach ($files as $file) {
      $path = $searchPath . DIRECTORY_SEPARATOR . $file;

      // ディレクトリ自体のサイズを取得
      if ($file === '.') {
        if ($includeDirectorySize) {
          $size += filesize($path);
        }

        continue;

      } else if ($file === '..') {
        continue;

      } else if (is_dir($path)) {
        self::sizeOfDirectoryCallback($path, $includeDirectorySize, $size);

      } else {
        $size += filesize($path);
      }
    }
  }

  /**
   * 指定したディレクトリ内のファイルを新しい場所へコピーします。
   *
   * @param string $from コピー元のディレクトリ、またはファイル。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param string $to コピー先のディレクトリ、またはファイル。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   *   from にファイル名を指定した場合は、to のパスにもファイル名を含める必要があります。
   *   尚、to のディレクトリが存在しない場合は自動的に生成されます。
   * @param array $options 検索オプション。
   *   - recursive: ディレクトリを再帰的にコピーする場合は TRUE、ファイルのみコピーする場合は FALSE。
   *   - hidden: ドットから始まる隠しファイルをコピーする場合は TRUE、コピーしない場合は FALSE。
   *   - pattern: コピー対象のファイル名正規表現パターン。'/.../' 形式で指定可能。
   * @return bool ファイルのコピーが成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws Delta_IOException コピー元、またはコピー先のディレクトリパスが不正な場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function copyRecursive($from, $to, array $options = array())
  {
    $array = array();
    $array['recursive'] = Delta_ArrayUtils::find($options, 'recursive', FALSE);
    $array['hidden'] = Delta_ArrayUtils::find($options, 'hidden', FALSE);
    $array['pattern'] = Delta_ArrayUtils::find($options, 'pattern');

    $from = self::buildAbsolutePath($from);

    if (!self::isAbsolutePath($to)) {
      $to = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $to;
    }

    // ファイルのコピー
    if (is_file($from)) {
      // 同一ディレクトリ上で同じ名前のファイルとディレクトリは作成できない
      if (is_dir($to)) {
        return FALSE;

      } else {
        $directory = dirname($to);

        if (!is_dir($directory) && !self::createDirectoryRecursive($directory)) {
          return FALSE;
        }

        return copy($from, $to);
      }
    }

    if (!is_dir($to) && !self::createDirectoryRecursive($to)) {
      return FALSE;
    }

    return self::copyRecursiveCallback($from, $to, $array);
  }

  /**
   * @access private
   * @see Delta_FileUtils::copyRecursive()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function copyRecursiveCallback($from, $to, array $options)
  {
    $files = scandir($from);

    foreach ($files as $file) {
       if ($file == '.' || $file == '..') {
        continue;
      }

      if (!$options['hidden'] && substr($file, 0, 1) == '.') {
        continue;
      }

      $fromPath = $from . DIRECTORY_SEPARATOR . $file;

      if ($options['recursive'] && is_dir($fromPath)) {
        $toPath = $to . DIRECTORY_SEPARATOR . $file;

        if (!is_dir($toPath) && !self::createDirectoryRecursive($toPath)) {
          return FALSE;
        }

        if (!self::copyRecursiveCallback($fromPath, $toPath, $options)) {
          return FALSE;
        }

      } else if (!is_dir($fromPath)) {
        if ($options['pattern'] !== NULL && !preg_match($options['pattern'], $file)) {
          continue;
        }

        if (!is_dir($to) && !self::createDirectoryRecursive($to)) {
          return FALSE;
        }

        $toPath = $to . DIRECTORY_SEPARATOR . $file;

        if (is_readable($fromPath)) {
          copy($fromPath, $toPath);
        }
      }
    }

    return TRUE;
  }

  /**
   * 指定されたパスの配下にある全てのファイルを配列形式に変換します。
   *
   * @param string $path 対象パス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param bool $recursive TRUE を指定した場合はディレクトリを再帰的に検索。
   * @param mixed $callback 対象ファイルが見つかった場合に実行するコールバック関数。
   *   コールバック関数は引数に対象パスを持ちます。
   *   パスを結果配列に含める場合は TRUE、含めない場合は FALSE を返して下さい。
   * @return array 対象パスに含まれるファイルリストを配列形式で返します。
   * @throws IOException 指定されたパスが存在しない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildFileMappingArray($path, $recursive = TRUE, $callback = NULL)
  {
    $path = self::buildAbsolutePath($path);
    $array = array();

    self::buildFileMappingArrayCallback($path, $array, $recursive, $callback, 0);

    return $array;
  }

  /**
   * @access private
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildFileMappingArrayCallback($path, array &$array, $recursive, $callback, $deep)
  {
    $files = scandir($path);

    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $targetPath = $path . DIRECTORY_SEPARATOR . $file;

      if ($callback !== NULL && !call_user_func($callback, $targetPath)) {
        continue;
      }

      if (is_file($targetPath)) {
        if ($recursive || $deep == 0) {
          $array[] = $file;
        }

      } else {
        $directory = array();
        $deep++;

        self::buildFileMappingArrayCallback($targetPath, $directory, $recursive, $callback, $deep);

        if ($recursive || $deep == 1) {
          $array[$file] = $directory;
        }

        $deep--;
      }
    }
  }

  /**
   * 指定したファイルを読み込みます。
   *
   * @param string $path 対象パス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @throws Delta_IOException 対象ファイルが存在しない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function readFile($path)
  {
    return file_get_contents(self::buildAbsolutePath($path));
  }

  /**
   * 指定したファイルにデータを書きこみます。
   *
   * @param string $path 対象パス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   *   パスに含まれるディレクトリが存在しない場合は自動的に生成されます。
   * @param string $data 書き込むデータ。
   * @param int $flags {@link file_put_contents()} 関数の flags オプションを参照。
   * @return int ファイルに書き込まれたバイト数を返します。失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function writeFile($path, $data, $flags = NULL)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    $directory = dirname($path);
    $result = TRUE;

    if (!is_dir($directory)) {
      $result = self::createDirectoryRecursive($directory);
    }

    if ($result) {
      if ($flags === NULL) {
        $result = file_put_contents($path, $data);
      } else {
        $result = file_put_contents($path, $data, $flags);
      }
    }

    return $result;
  }

  /**
   * ファイルのパーミッションを 8 進数形式で取得します。
   * <i>Windows 環境では常に '0777' を返します。</i>
   *
   * @param string $path 対象パス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @return string ファイルのパーミッションを 8 進数形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getMode($path)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    return substr(sprintf('%o', fileperms($path)), -4);
  }

  /**
   * 指定したファイルの名前を変更します。
   *
   * @param string $from 変更元のファイル名。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param string $to 変更後のファイル名。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   *   パスに含まれるディレクトリが存在しない場合は自動的に生成されます。
   * @return string ファイル名の変更が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function move($from, $to)
  {
    if (!self::isAbsolutePath($from)) {
      $from = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $from;
    }

    if (!self::isAbsolutePath($to)) {
      $to = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $to;
    }

    $directory = dirname($to);

    if (!is_dir($directory) && !self::createDirectoryRecursive($directory)) {
      return FALSE;
    }

    return rename($from, $to);
  }

  /**
   * ファイル (ディレクトリ) が書き込み可能かチェックします。
   *
   * @param string $path 対象となるパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @return bool ファイル (ディレクトリ) が書き込み可能な場合は TRUE、そうでない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isWritable($path)
  {
    if (!self::isAbsolutePath($path)) {
      $path = self::buildAbsolutePath($path);
    }

    return is_writable($path);
  }

  /**
   * ファイル (ディレクトリ) が読み込み可能かチェックします。
   *
   * @param string $path 対象となるパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @return bool ファイル (ディレクトリ) が読み込み可能な場合は TRUE、そうでない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isReadable($path)
  {
    if (!self::isAbsolutePath($path)) {
      $path = self::buildAbsolutePath($path);
    }

    return is_readable($path);
  }

  /**
   * ファイル (ディレクトリ) が実行可能かチェックします。
   *
   * @param string $path 対象となるパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @return bool ファイル (ディレクトリ) が実行可能な場合は TRUE、そうでない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function isExecutable($path)
  {
    if (!self::isAbsolutePath($path)) {
      $path = self::buildAbsolutePath($path);
    }

    return is_executable($path);
  }

  /**
   * ファイルの最終更新日、及び最終アクセス時刻をセットします。
   *
   * @param string $path 対象となるパス。絶対パス、あるいは APP_ROOT_DIR からの相対パスが有効。
   * @param int $updateTime 最終更新時刻。未指定の場合は現在の時刻がセットされる。
   * @param int $accessTime 最終アクセス時刻。未指定の場合は updateTime と同じ値がセットされる。
   * @return bool 更新が成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function touch($path, $updateTime = NULL, $accessTime = NULL)
  {
    if (!self::isAbsolutePath($path)) {
      $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $path;
    }

    if ($updateTime === NULL) {
      $updateTime = time();
    }

    if ($accessTime === NULL) {
      $accessTime = $updateTime;
    }

    return touch($path, $updateTime, $accessTime);
  }
}
