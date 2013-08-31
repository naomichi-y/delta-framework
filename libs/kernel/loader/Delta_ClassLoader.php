<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.loader
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * require files
 */

require DELTA_LIBS_DIR . '/cache/Delta_Cache.php';
require DELTA_LIBS_DIR . '/cache/Delta_CacheManager.php';
require DELTA_LIBS_DIR . '/cache/Delta_YAMLCache.php';

require DELTA_LIBS_DIR . '/kernel/container/Delta_DIContainer.php';
require DELTA_LIBS_DIR . '/kernel/container/Delta_DIContainerFactory.php';

require DELTA_LIBS_DIR . '/util/Delta_ParameterHolder.php';
require DELTA_LIBS_DIR . '/util/common/Delta_ArrayUtils.php';
require DELTA_LIBS_DIR . '/util/common/Delta_CommonUtils.php';
require DELTA_LIBS_DIR . '/util/config/Delta_Config.php';
require DELTA_LIBS_DIR . '/util/common/Delta_FileUtils.php';
require DELTA_LIBS_DIR . '/util/common/Delta_StringUtils.php';

/**
 * アプリケーション空間にクラスファイルを読み込む機能を提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.loader
 */

class Delta_ClassLoader
{
  /**
   * クラスローダのインスタンス。
   * @var Delta_ClassLoader
   */
  private static $_instance;

  /**
   * ブートローダがコマンドモードで起動している場合に TRUE を格納。
   * @var bool
   */
  private static $_isBootTypeCommand = FALSE;

  /**
   * {@link Delta_Cache} オブジェクト。
   * @var Delta_Cache
   */
  private static $_cache;

  /**
   * クラスローダが参照するクラスパスのリスト。
   * @var array
   */
  private static $_searchPaths = array(DELTA_LIBS_DIR);

  /**
   * アプリケーション空間に読み込まれたクラス名のリスト。
   * @var array
   */
  private static $_loadedClasses = array();

  /**
   * アプリケーションから新しく参照されたクラスのリスト。
   * @var array
   */
  private static $_preWriteClasses = array();

  /**
   * アプリケーションから新しく参照されたベンダークラスのリスト。
   * @var array
   */
  private static $_preWriteAutoloaders = array();

  /**
   * クラスパスを保存するキャッシュ有効時間。(30 日)
   * @var int
   */
  private static $_cacheExpire = 2592000;

  /**
   * キャッシュから読み込まれたクラスのリスト。
   * @var array
   */
  private static $_loadedCacheClasses = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * クラスローダを初期化します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function initialize()
  {
    // デストラクタを起動するためにインスタンスを生成
    self::$_instance = new Delta_ClassLoader();

    // オートローダの登録
    spl_autoload_register(array('Delta_ClassLoader', 'loadByName'));

    if (Delta_BootLoader::isBootTypeCommand()) {
      self::$_isBootTypeCommand = TRUE;
      self::$_cache = Delta_CacheManager::getInstance(Delta_CacheManager::CACHE_TYPE_NULL);

    } else {
      self::$_searchPaths[] = APP_ROOT_DIR . '/libs';
      self::$_cache = Delta_CacheManager::getInstance(Delta_CacheManager::CACHE_TYPE_FILE);
    }
  }

  /**
   * クラスローダが参照するクラスパスを追加します。
   * 指定されたパスは {@link loadByName()} や {@link findPath()} メソッドをコールした際に参照されます。
   *
   * @param string $path 追加するクラスパス。
   *   APP_ROOD_DIR からの相対パス、あるいは絶対パスが有効。
   * @return bool クラスパスの追加が成功した場合は TRUE、失敗した (パスが見つからない、あるいは既に同じクラスパスが登録されている場合) は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addSearchPath($path)
  {
    $result = FALSE;

    try {
      $path = Delta_FileUtils::buildAbsolutePath($path);

      if (!in_array($path, self::$_searchPaths)) {
        self::$_searchPaths[] = $path;
        $result = TRUE;
      }

    } catch (Delta_IOException $e) {}

    return $result;
  }

  /**
   * クラスローダが参照するクラスパスの一覧を取得します。
   *
   * @return array クラスパスの一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getSearchPaths()
  {
    return self::$_searchPaths;
  }

  /**
   * クラスパスを指定してファイルを読み込みます。
   * 読み込み可能なクラスの形式には制限があります。詳しくは {@link loadByName()} メソッドを参照して下さい。
   * <i>このメソッドは主にフレームワーク内部で使用されます。
   * アプリケーションコードからクラスを読み込む場合は、{@link loadByName()} メソッドを使用するべきです。</i>
   *
   * @param string $classPath 参照するクラスパス。(絶対パス形式のみ)
   * @param string $className 読み込むクラスの名前。未指定時はクラスパスから名前を取得します。
   * @param bool $throw ファイルが見つからない場合に例外を発生させる場合は TRUE を指定。FALSE 指定時は Compile error が発生する。
   * @throws Delta_IOException クラスパスが見つからない場合に発生。(throw が TRUE の場合のみ)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function loadByPath($classPath, $className = NULL, $throw = FALSE)
  {
    if ($className === NULL) {
      $className = basename($classPath);
      $className = substr($className, 0, strpos($className, '.php'));
    }

    if (!in_array($className, self::$_loadedClasses)) {
      if ($throw && !is_file($classPath)) {
        $message = sprintf('File does not exist. [%s]', $className);
        throw new Delta_IOException($message);
      }

      require $classPath;

      self::$_loadedClasses[] = $className;
    }
  }

  /**
   * delta が提供するオートローダメソッドです。
   * 読み込むクラスは次の形式に準拠する必要があります。
   *   o クラス名、インタフェース名は PascalCase 形式
   *   o ファイル拡張子は '.php' 形式
   *   o 1 ファイルにつき 1 クラスを定義
   *
   * @param string $className 読み込むクラスの名前。
   * @throws Delta_IOException クラスが見つからない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function loadByName($className)
  {
    static $autoloaders;

    $hasLoadVendorClass = FALSE;

    // 対象クラスが既に読み込まれていないかチェック
    if (!in_array($className, self::$_loadedClasses)) {
      $callbackAutoloader = FALSE;

      // 対象クラスがベンダークラスとしてキャッシュに登録されているかチェック
      if ($autoloaders === NULL) {
        $autoloaders = self::$_cache->get('autoload', 'autoload_vendors');

        if (!$autoloaders) {
          $autoloaders = array();
        }
      }

      foreach ($autoloaders as $autoloader) {
        if (in_array($className, $autoloader['classes'])) {
          $callbackAutoloader = $autoloader['callback'];
          break;
        }
      }

      // ベンダーのオートローダを実行
      if ($callbackAutoloader) {
        call_user_func($callbackAutoloader, $className);
        $hasLoadVendorClass = TRUE;

      // フレームワーク内部のオートローダを起動
      } else {
        $findPath = self::findPath($className);

        // クラスパスが見つからない場合はベンダーのクラスローダを実行する
        if ($findPath === FALSE) {
          $functions = spl_autoload_functions();
          $j = sizeof($functions);

          for ($i = 1; $i < $j; $i++) {
            // ベンダーライブラリが提供するオートローダ関数の実行
            call_user_func($functions[$i], $className);

            if (is_array($functions[$i])) {
              $hash = md5($functions[$i][0] . $functions[$i][1]);
            } else {
              $hash = md5($functions[$i]);
            }

            if (class_exists($className) || interface_exists($className)) {
              self::$_preWriteAutoloaders[$hash]['callback'] = $functions[$i];
              self::$_preWriteAutoloaders[$hash]['classes'][] = $className;

              $hasLoadVendorClass = TRUE;
              break;
            }
          }
        }
      }

      if (!$hasLoadVendorClass) {
        if (!$findPath) {
          $message = sprintf('Class does not exist. [%s]', $className);
          throw new Delta_IOException($message);

        // コアクラスは必ずファイルが存在するものとする
        } else if (substr($className, 0, 6) !== 'Delta_' && !is_file($findPath)) {
          $message = sprintf('File does not exist. [%s]', $findPath);
          throw new Delta_IOException($message);

        } else {
          self::$_loadedClasses[] = $className;
          require $findPath;
        }
      }
    }
  }

  /**
   * クラスパスを検索します。
   * このメソッドは一度取得したパスをキャッシュするため、2 回目以降の読み込みは高速な動作となります。
   *
   * @param string $className 検索対象のクラス名。
   * @param string $searchPath 検索するクラスパス。未指定時は {@link addSearchPath()} で指定したパスが有効となる。
   * @param bool $throw クラスパスが見つからない場合に例外を発生させる場合は TRUE を指定。
   * @return string クラスパスを返します。パスが見つからない場合は FALSE を返します。
   * @throws Delta_IOException クラスパスが見つからない場合に発生。(throw が TRUE の場合のみ)
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function findPath($className, $searchPath = NULL, $throw = FALSE)
  {
    static $loadedClassPaths = array();
    static $latestClassPaths = array();

    $findPath = FALSE;

    if ($searchPath === NULL) {
      $searchPaths = self::$_searchPaths;
    } else {
      $searchPaths = array($searchPath);
    }

    foreach ($searchPaths as $currentPath) {
      $hash = md5($currentPath);
      $isCacheData = FALSE;

      // アプリケーション空間にクラスパスリストが読み込まれているかチェック
      if (empty($loadedClassPaths[$hash])) {
        // キャッシュからクラスパスリストを取得
        $currentClasses = self::$_cache->get($hash, 'autoload');

        if ($currentClasses) {
          $isCacheData = TRUE;
          self::$_loadedCacheClasses[$hash] = $currentClasses;

        // キャッシュがない場合はディレクトリを検索してクラスリストを取得
        } else {
          $currentClasses = self::searchFile($currentPath);
          $latestClassPaths[] = $currentPath;
        }

        $loadedClassPaths[$hash] = $currentClasses;

      } else {
        $currentClasses = $loadedClassPaths[$hash];
      }

      // クラスパスを検出
      if (isset($currentClasses[$className])) {
        $findPath = $currentPath . $currentClasses[$className];

        // 見つかったクラスパスがキャッシュに含まれない場合はキャッシュに追記する
        if (!$isCacheData) {
          self::$_preWriteClasses[$hash][$className] = $currentClasses[$className];
        }

        break;
      }
    }

    // クラスパスが見つからない場合、キャッシュが古い可能性があるため再度検索を行う
    if (!$findPath) {
      foreach ($searchPaths as $searchPath) {
        if (!in_array($searchPath, $latestClassPaths)) {
          $hash = md5($searchPath);
          $currentClasses = self::searchFile($searchPath);
          $loadedClassPaths[$hash] = $currentClasses;

          if (isset($currentClasses[$className])) {
            $findPath = $searchPath . $currentClasses[$className];
            self::$_preWriteClasses[$hash][$className] = $currentClasses[$className];
          }

          $latestClassPaths[] = $searchPath;
        }
      }
    }

    if ($throw && (!$findPath || !is_file($findPath))) {
      $message = sprintf('Class does not exist. [%s]', $className);
      throw new Delta_IOException($message);
    }

    return $findPath;
  }

  /**
   * 指定したディレクトリに含まれる全ての PHP ファイル (拡張子は .php) を取得します。
   * このメソッドはコールする度にディレクトリを再帰的に走査するため非常に低速です。
   *
   * @param string $searchPath 検索対象のディレクトリ。
   * @return array 見つかったファイルのリストを返します。
   * @throws RuntimeException 既に同名のクラスが読み込まれている場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function searchFile($searchPath)
  {
    $options = array('hidden' => FALSE, 'basePath' => $searchPath);
    $files = Delta_FileUtils::search($searchPath, '/\.php/', $options);
    $classes = array();

    foreach ($files as $filePath) {
      $fileName = basename($filePath);

      if (ctype_upper(substr($fileName, 0, 1))) {
        $className = substr($fileName, 0, strpos($fileName, '.'));

        if (isset($classes[$className])) {
          $message = sprintf('Cache creation failed. The class name is a duplicate. [%s%s, %s%s]',
            $searchPath,
            $classes[$className],
            $searchPath,
            $filePath);
          throw new RuntimeException($message);

        } else {
          $classes[$className] = $filePath;
        }
      }
    }

    return $classes;
  }

  /**
   * デストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __destruct()
  {
    // 新しく読み込まれたクラスをキャッシュに追加する
    foreach (self::$_preWriteClasses as $hash => $classes) {
      if (isset(self::$_loadedCacheClasses[$hash])) {
        $classes = array_merge(self::$_loadedCacheClasses[$hash], $classes);
      }

      self::$_cache->set($hash, $classes, 'autoload', self::$_cacheExpire);
    }

    // 新しく読み込まれたベンダークラスをキャッシュに追加する
    $classes = self::$_cache->get('autoload', 'autoload_vendors');

    if ($classes) {
      $autoloaders = Delta_ArrayUtils::merge($classes, self::$_preWriteAutoloaders);
    } else {
      $autoloaders = self::$_preWriteAutoloaders;
    }

    self::$_cache->set('autoload', $autoloaders, 'autoload_vendors');
  }
}
