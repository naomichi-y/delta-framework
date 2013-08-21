<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * YAML データをファイルキャッシュします。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package cache
 */

class Delta_YAMLCache extends Delta_Object
{
  /**
   * 読み込まれたデータ配列のリストを格納する。
   * @var array
   */
  private static $_loadedYAML = array();

  /**
   * キャッシュ基底ディレクトリ。
   * @var string
   */
  private $_basePath;

  /**
   * YAML 拡張ファイルのサフィックス。
   * @var string
   */
  private $_extensionSuffix;

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_basePath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'yaml';
    $this->_extensionSuffix = '_' . php_uname('n') . '.yml';
  }

  /**
   * YAML データをキャッシュします。
   *
   * @param string $yamlPath キャッシュ対象の YAML パス。
   * @param array $data キャッシュに保存するデータ配列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function set($yamlPath, $data)
  {
    $cachePath = $this->buildCachePath($yamlPath);
    $cacheDirectory = dirname($cachePath);

    if (!is_dir($cacheDirectory)) {
      Delta_FileUtils::createDirectoryRecursive($cacheDirectory, 0777);

    } else if (is_file($cachePath)) {
      unlink($cachePath);
    }

    $code = sprintf("<?php\n"
      ."// This script was auto-generated from Delta_YAMLCache::set().\n"
      ."// date: %s\n\n"
      ."Delta_YAMLCache::load('%s', %s);\n",
      date('m/d/Y H:i:s'),
      $cachePath,
      var_export($data, TRUE));

    file_put_contents($cachePath, $code, LOCK_EX);
    chmod($cachePath, 0777);
  }

  /**
   * YAML のファイルパスからキャッシュ配置パスを構築します。
   *
   * @param string $yamlPath YAML のファイルパス。
   * @return string キャッシュ配置パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildCachePath($yamlPath)
  {
    if (strpos($yamlPath, APP_ROOT_DIR) !== FALSE) {
      $cachePath = substr($yamlPath, strlen(APP_ROOT_DIR) + 1);
    } else {
      $cachePath = 'default' . DIRECTORY_SEPARATOR . md5($yamlPath);
    }

    $cachePath = $this->_basePath . DIRECTORY_SEPARATOR . $cachePath . '.php';

    return $cachePath;
  }

  /**
   * YAML 拡張ファイルのパスを取得します。
   *
   * @param string $yamlPath YAML のファイルパス。
   * @return string YAML 拡張ファイルのパスを返します。このメソッドは実際にパスが存在するかどうかのチェックは行いません。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getExtensionPath($yamlPath)
  {
    return substr($yamlPath, 0, strlen($yamlPath) - 4) . $this->_extensionSuffix;
  }

  /**
   * YAML のキャッシュデータを取得します。
   *
   * @param string $yamlPath YAML のファイルパス。
   * @param bool $hasExtensionFile ホスト名による拡張ファイルが定義されている場合に TRUE を指定。
   * @return array YAML キャッシュデータを返します。キャッシュが見つからない、またはデータが古い場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getFromCacheData($yamlPath, $hasExtensionFile)
  {
    $result = FALSE;
    $cachePath = $this->buildCachePath($yamlPath);

    // キャッシュが存在する
    if (is_file($cachePath)) {
      $rawTime = filemtime($yamlPath);
      $cacheTime = filemtime($cachePath);

      // 拡張ファイルを持つ YAML
      if ($hasExtensionFile) {
        $extensionPath = $this->getExtensionPath($yamlPath);

        if ($rawTime < $cacheTime) {
          // 拡張ファイルが実在するか
          if (is_file($extensionPath)) {
            if (filemtime($extensionPath) < $cacheTime) {
              $result  = TRUE;
            }

          } else {
            $result = TRUE;
          }
        }

      } else {
        if ($rawTime < $cacheTime) {
          $result = TRUE;
        }
      }

    // キャッシュが生成されていない
    } else {
      if ($hasExtensionFile) {
        $extensionPath = $this->getExtensionPath($yamlPath);
      }
    }

    if ($result) {
      require $cachePath;

      if (isset(self::$_loadedYAML[$cachePath])) {
        $result = self::$_loadedYAML[$cachePath];
      } else {
        $result = FALSE;
      }
    }

    return $result;
  }

  /**
   * YAML データを取得します。
   *
   * @param string $yamlPath YAML のファイルパス。
   * @param mixed $callback データを読み込む際に適用するコールバック関数。
   * @return array YAML データを返します。
   * @throws Delta_IOException ファイルが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function getFromRawData($yamlPath, $callback, $hasExtensionFile)
  {
    static $compiler;

    if ($compiler === NULL) {
      $compiler = new Delta_ConfigCompiler();
    }

    Delta_CommonUtils::loadVendorLibrary('spyc/spyc.php');

    if (!is_file($yamlPath)) {
      $message = sprintf('File does not exist. [%s]', $yamlPath);
      throw new Delta_IOException($message);
    }

    // YAML を解析してデータ配列を取得する
    if ($hasExtensionFile) {
      $data1 = Spyc::YAMLLoad($yamlPath);
      $extensionPath = $this->getExtensionPath($yamlPath);

      if (is_file($extensionPath)) {
        $data2 = Spyc::YAMLLoad($extensionPath);
        $data = Delta_ArrayUtils::mergeRecursive($data1, $data2);

      } else {
        $data = $data1;
      }

    } else {
      $data = Spyc::YAMLLoad($yamlPath);
    }

    // YAML キャッシュ生成前にデータのコールバック関数を適用
    if ($callback) {
      $arguments = array();
      $arguments[] = $yamlPath;
      $arguments[] = $data;

      if (isset($callback[1])) {
        $arguments[] = $callback[1];
      }

      $data = call_user_func_array(array($compiler, $callback[0]), $arguments);

    } else {
      call_user_func_array(array($compiler, 'build'), array(&$data));
    }

    return $data;
  }

  /**
   * 指定した YAML ファイルを配列型で取得します。
   * get() メソッドは対象ファイルの初回読み込み時に YAML キャッシュを生成します。
   *
   * @param string $yamlPath YAML のファイルパス。
   * @param string $callback キャッシュ生成時に適用するコールバック関数。
   *   {@link Delta_ConfigCompiler} のコールバックメソッドを指定可能。
   * @param bool $hasExtensionFile ホスト名による拡張ファイルが定義されている場合に TRUE を指定。
   * @param bool &$readFromCache ファイルがキャッシュから読み込まれた場合は TRUE を返します。
   * @return array データ配列を返します。
   * @throws Delta_IOException yamlPath が見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($yamlPath, $callback = NULL, $hasExtensionFile = FALSE, &$readFromCache = FALSE)
  {
    $data = $this->getFromCacheData($yamlPath, $hasExtensionFile);

    if ($data === FALSE) {
      $data = $this->getFromRawData($yamlPath, $callback, $hasExtensionFile);

      // コマンドモード起動時は YAML キャッシュを生成しない
      if (!Delta_BootLoader::isBootTypeCommand()) {
        $this->set($yamlPath, $data);
      }

    } else {
      $readFromCache = TRUE;
    }

    return $data;
  }

  /**
   * YAML のキャッシュファイルを削除します。
   *
   * @param string $yamlPath 削除対象のオリジナル YAML パス。
   * @return bool ファイルの削除に成功した場合は TRUE、キャッシュが存在しなかった場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function delete($yamlPath)
  {
    $result = FALSE;
    $cachePath = $this->buildCachePath($yamlPath);

    if (is_file($cachePath)) {
      unlink($cachePath);
      $result = TRUE;
    }

    return $result;
  }

  /**
   * YAML キャッシュから配列データを読み込みます。
   *
   * @param string $name YAML キャッシュパス。
   * @param array $config キャッシュに含まれるデータ配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function load($name, $config)
  {
    self::$_loadedYAML[$name] = $config;
  }
}
