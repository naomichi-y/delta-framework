<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * require files
 */
require DELTA_LIBS_DIR . '/kernel/path/Delta_Theme.php';

/**
 * アプリケーションのパス情報を管理します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.path
 */

class Delta_AppPathManager
{
  /**
   * @var Delta_Theme
   */
  private $_theme;

  /**
   * @var array
   */
  private $_extendModulePaths = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {}

  /**
   * Delta_AppPathManager のインスタンスを取得します。
   *
   * @return Delta_AppPathManager Delta_AppPathManager のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getInstance()
  {
    static $instance = NULL;

    if ($instance === NULL) {
      $instance = new Delta_AppPathManager();
    }

    return $instance;
  }

  /**
   * パスマネージャを初期化します。
   *
   * @param Delta_ParameterHolder $themeConfig テーマ設定属性。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize($themeConfig = NULL)
  {
    if ($themeConfig !== NULL) {
      $this->_theme = new Delta_Theme($themeConfig);
    }
  }

  /**
   * Delta_Theme オブジェクトを取得します。
   *
   * @return Delta_Theme Delta_Theme のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTheme()
  {
    return $this->_theme;
  }

  /**
   * パスマネージャにモジュール情報を追加します。
   *
   * @param string $moduleName 対象モジュール名。
   * @param string $modulePath モジュールのパス。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addModulePath($moduleName, $modulePath)
  {
    $this->_extendModulePaths[$moduleName] = $modulePath;
  }

  /**
   * アプリケーションの内部パスを構築します。
   *
   * @param string $targetPath APP_ROOT_DIR からの相対パス。
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string 構築した絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInternalPath($targetPath, $appendPath)
  {
    $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $targetPath;

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * コンソールディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng コンソールディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConsolePath($appendPath = NULL)
  {
    return $this->buildInternalPath('console', $appendPath);
  }

  /**
   * キャッシュディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng キャッシュディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getCachePath($appendPath = NULL)
  {
    return $this->buildInternalPath('cache', $appendPath);
  }

 /**
   * コンフィグディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng コンフィグディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getConfigPath($appendPath = NULL)
  {
    return $this->buildInternalPath('config', $appendPath);
  }

  /**
   * アプリケーションの内部パスを構築します。
   * このメソッドはテーマに対応したパスを返します。
   *
   * $param string $target 対象パス。
   * @return string テーマに応じた絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildInternalThemePath($targetPath)
  {
    if ($this->_theme->isActive()) {
      $path = sprintf('%s%s%s',
        $this->_theme->getThemePath(),
        DIRECTORY_SEPARATOR,
        $targetPath);

    } else {
      $path = sprintf('%s%s%s',
        APP_ROOT_DIR,
        DIRECTORY_SEPARATOR,
        $targetPath);
    }

    return $path;
  }

  /**
   * データディレクトリの絶対パスを取得します。
   * このメソッドはテーマ拡張ディレクトリに対応したパスを返します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string データディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getDataPath($appendPath = NULL)
  {
    if ($this->_theme->isActiveExtensionPath('data')) {
      $path = sprintf('%s%sdata', $this->_theme->getThemePath(), DIRECTORY_SEPARATOR);
    } else {
      $path = sprintf('%s%sdata', APP_ROOT_DIR, DIRECTORY_SEPARATOR);
    }

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * ライブラリディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng ライブラリディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLibraryPath($appendPath = NULL)
  {
    return $this->buildInternalPath('libs', $appendPath);
  }

  /**
   * ログディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng ログディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getLogsPath($appendPath = NULL)
  {
    return $this->buildInternalPath('logs', $appendPath);
  }

  /**
   * モジュールディレクトリの絶対パスを取得します。
   *
   * @param string $moduleName 検索するモジュール名。モジュールが実際に存在するかどうかのチェックは行いません。
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string モジュールディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getModulePath($moduleName, $appendPath = NULL)
  {
    if (isset($this->_extendModulePaths[$moduleName])) {
      $path = $this->_extendModulePaths[$moduleName];

    } else {
      $path = sprintf('%s%smodules%s%s',
        APP_ROOT_DIR,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        $moduleName);
    }

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * モジュール内部 (modules/{module_name}) の name に対応するテーマパスを取得します。
   *
   * @param string $moduleName 対象モジュール名。
   * @param string $targetPath テーマに対応した対象パス。
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return テーマに応じた絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function buildModuleThemePath($moduleName, $targetPath, $appendPath)
  {
    if ($this->_theme->hasModuleName($moduleName)) {
      $path = sprintf('%s%smodules%s%s%s%s',
        $this->_theme->getThemePath(),
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        $moduleName,
        DIRECTORY_SEPARATOR,
        $targetPath);

    } else {
      $path = sprintf('%s%s%s',
        $this->getModulePath($moduleName),
        DIRECTORY_SEPARATOR,
        $targetPath);
    }

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * モジュールビヘイビアディレクトリの絶対パスを取得します。
   * このメソッドはテーマ拡張ディレクトリに対応したパスを返します。
   *
   * @param string $moduleName 検索するモジュール名。モジュールが実際に存在するかどうかのチェックは行いません。
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string モジュールビヘイビアディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getModuleBehaviorsPath($moduleName, $appendPath = NULL)
  {
    $key = sprintf('modules.%s.behaviors', $moduleName);

    if ($this->_theme->isActiveExtensionPath($key)) {
      $path = $this->buildModuleThemePath($moduleName, 'behaviors', $appendPath);

    } else {
      $appendPath = 'behaviors' . DIRECTORY_SEPARATOR . $appendPath;
      $path = $this->getModulePath($moduleName, $appendPath);
    }

    return $path;
  }

  /**
   * モジュールビューディレクトリの絶対パスを取得します。
   * このメソッドはテーマに対応したパスを返します。
   *
   * @param string $moduleName 検索するモジュール名。モジュールが実際に存在するかどうかのチェックは行いません。
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string モジュールビューディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getModuleViewsPath($moduleName, $appendPath = NULL)
  {
    return $this->buildModuleThemePath($moduleName, 'views', $appendPath);
  }

  /**
   * ビューディレクトリの絶対パスを取得します。
   * このメソッドはテーマに対応したパスを返します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return ビューディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getViewsPath($appendPath = NULL)
  {
    $path = $this->buildInternalThemePath('views', $appendPath);

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * テーマディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng テーマディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getThemePath($appendPath = NULL)
  {
    return $this->buildInternalPath('theme', $appendPath);
  }

  /**
   * テンポラリディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng テンポラリディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTemporaryPath($appendPath = NULL)
  {
    return $this->buildInternalPath('tmp', $appendPath);
  }

 /**
   * ベンダーディレクトリの絶対パスを取得します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return stirng ベンダーディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getVendorsPath($appendPath = NULL)
  {
    return $this->buildInternalPath('vendors', $appendPath);
  }

  /**
   * Web 公開ディレクトリの絶対パスを取得します。
   * このメソッドはテーマに対応したパスを返します。
   *
   * @param string $appendPath 戻り値に追加する追記パス。
   * @return string Web 公開ディレクトリの絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getWebrootPath($appendPath = NULL)
  {
    $path = $this->buildInternalThemePath('webroot', $appendPath);

    if ($appendPath !== NULL) {
      $path .= DIRECTORY_SEPARATOR . $appendPath;
    }

    return $path;
  }

  /**
   * 指定されたパスを元に絶対パスを構築します。
   * <code>
   * // '{APP_ROOT_DIR}/config/custom.yml'
   * $manager->buildAbsolutePath('config', 'custom', 'yml');
   * </code>
   *
   * @param string $basePath APP_ROOT_DIR から始まる相対パス。
   * @param string $targetPath 生成対象となるパス。
   *   '@' から始まるパスは APP_ROOT_DIR からの相対パスと見なされます。
   * @param string $extension targetPath に付加する拡張子。
   *   - targetPath に同じ拡張子が含まれる場合、extension は付加されません。
   *   - targetPath が絶対パスと見なされる場合は変換を行いません
   * @return string 構築した絶対パスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function buildAbsolutePath($basePath, $targetPath, $extension)
  {
    $prefix = substr($targetPath, 0, 1);

    // 絶対パスが含まれる場合は変換を行わない
    if (Delta_FileUtils::isAbsolutePath($targetPath)) {
      $path = $targetPath;

    } else {
      if ($prefix === '@') {
        $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR;
        $targetPath = substr($targetPath, 1);
        $path .= $targetPath;

      } else {
        $count = substr_count($targetPath, '../');

        // 上位ディレクトリを参照している場合、絶対パスに変換する
        if ($count) {
          for ($i = 0; $i < $count; $i++) {
            $basePath = substr($basePath, 0, strrpos($basePath, DIRECTORY_SEPARATOR));
          }

          $targetPath = str_replace('../', '', $targetPath);
        }

        // 基底パスに対象パスを結合する
        $prefix = substr($basePath, 0, 1);

        if ($prefix === '/' || (DIRECTORY_SEPARATOR === '\\' && strpos($basePath, ':') !== FALSE)) {
          $path = $basePath . DIRECTORY_SEPARATOR . $targetPath;

        } else {
          $path = APP_ROOT_DIR . DIRECTORY_SEPARATOR
            .$basePath . DIRECTORY_SEPARATOR
            .$targetPath;
        }
      }

      // targetPath に拡張子が含まれない場合は付加する
      $info = pathinfo($targetPath);

      if (!isset($info['extension'])) {
        $path .= '.' . ltrim($extension, '.');
      }
    }

    return $path;
  }
}
