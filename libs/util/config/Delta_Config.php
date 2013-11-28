<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * 各種設定ファイル (YAML) の参照、及び編集機能を提供します。
 * 参照された YAML は Spyc によりパースされ、{@link Delta_YAMLCache} を介して内部キャッシュされます。
 * <i>キャッシュファイルは {APP_ROOT_DIR}/config/yaml ディレクトリ下に作成されます。
 * 設定ファイルが書き換えられた (最終更新時間が更新された) 場合は自動的にキャッシュを再構築するため、基本的に手動でキャッシュを削除する必要はありません。 </i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.config
 */
class Delta_Config extends Delta_Object
{
  /**
   * YAML 定数。(config/application.yml、config/application_{hostname}.yml)
   */
  const TYPE_DEFAULT_APPLICATION = 1;

  /**
   * YAML 定数。(config/base_dicon.yml)
   */
  const TYPE_DEFAULT_BASE_DICON = 2;

  /**
   * YAML 定数。(config/routes.yml)
   */
  const TYPE_DEFAULT_ROUTES = 3;

  /**
   * YAML 定数。(config/global_filters.yml)
   */
  const TYPE_DEFAULT_GLOBAL_FILTERS = 4;

  /**
   * YAML 定数。(modules/{module}/config/filters.yml)
   */
  const TYPE_DEFAULT_MODULE_FILTERS = 5;

  /**
   * YAML 定数。(config/global_behavior.yml)
   */
  const TYPE_DEFAULT_GLOBAL_BEHAVIOR = 6;

  /**
   * YAML 定数。(config/global_helpers.yml)
   */
  const TYPE_DEFAULT_GLOBAL_HELPERS = 9;

  /**
   * YAML 定数。(modules/{module}/config/helpers.yml)
   */
  const TYPE_DEFAULT_MODULE_HELPERS = 10;

  /**
   * YAML 定数。(config/site.yml、config/site_{hostname}.yml)
   */
  const TYPE_DEFAULT_SITE = 11;

  /**
   * YAML 定数。(カスタムパス)
   */
  const TYPE_DEFAULT_CUSTOM = 12;

  /**
   * YAML 定数。({DELTA_ROOT_DIR}/skeleton/policy_config/application.yml)
   */
  const TYPE_POLICY_APPLICATION = 13;

  /**
   * YAML 定数。({DELTA_ROOT_DIR}/skeleton/policy_config/base_dicon.yml)
   */
  const TYPE_POLICY_BASE_DICON = 14;

  /**
   * YAML 定数。({DELTA_ROOT_DIR}/skeleton/policy_config/routes.yml)
   */
  const TYPE_POLICY_ROUTES = 15;

  /**
   * YAML 定数。({DELTA_ROOT_DIR}/skeleton/policy_config/filters.yml)
   */
  const TYPE_POLICY_GLOBAL_FILTERS = 16;

  /**
   * YAML 定数。({DELTA_ROOT_DIR}/skeleton/policy_config/helpers.yml)
   */
  const TYPE_POLICY_GLOBAL_HELPERS = 17;

  /**
   * @var array
   */
  protected static $_gets = array();

  /**
   * @param int $globalConfigType
   * @param int $moduleConfigType
   * @return array
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function merge($globalConfigType, $moduleConfigType)
  {
    if (Delta_BootLoader::isBootTypeWeb()) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config1 = self::getArray($globalConfigType);
      } else {
        $config1 = self::getPolicy($globalConfigType);
      }

      $config2 = self::getArray($moduleConfigType);

      if ($config2) {
        $merge = Delta_ArrayUtils::merge($config1, $config2);
      } else {
        $merge = $config1;
      }

    } else {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $merge = self::getArray($globalConfigType);
      } else {
        $merge = self::getPolicy($globalConfigType);
      }
    }

    return $merge;
  }

  /**
   * アプリケーション設定ファイルを読み込みます。
   * ファイルは次の順でマージされます。(一番下が最優先)
   *   o config/application.yml
   *   o config/application_{hostname}.yml
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getApplication()
  {
    if (empty(self::$_gets['application'])) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config = self::getArray(self::TYPE_DEFAULT_APPLICATION);
      } else {
        $config = self::getPolicy(self::TYPE_POLICY_APPLICATION);
      }

      self::$_gets['application'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['application'];
  }

  /**
   * DI コンテナ設定ファイルを読み込みます。
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getDIContainer()
  {
    if (empty(self::$_gets['base_dicon'])) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config = self::getArray(self::TYPE_DEFAULT_BASE_DICON);
      } else {
        $config = self::getPolicy(self::TYPE_POLICY_BASE_DICON);
      }

      self::$_gets['base_dicon'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['base_dicon'];
  }

  /**
   * ルータ設定ファイルを読み込みます。
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getRoutes()
  {
    if (empty(self::$_gets['routes'])) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config = self::getArray(self::TYPE_DEFAULT_ROUTES);
      } else {
        $config = self::getPolicy(self::TYPE_POLICY_ROUTES);
      }

      self::$_gets['routes'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['routes'];
  }

  /**
   * フィルタ設定ファイルを読み込みます。
   * ファイルは次の順でマージされます。(一番下が最優先)
   *   o config/global_filters.yml
   *   o modules/{module_name}/config/filters.yml
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getFilters()
  {
    if (empty(self::$_gets['filters'])) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config = self::merge(self::TYPE_DEFAULT_GLOBAL_FILTERS, self::TYPE_DEFAULT_MODULE_FILTERS);
      } else {
        $config = self::merge(self::TYPE_POLICY_GLOBAL_FILTERS, self::TYPE_DEFAULT_MODULE_FILTERS);
      }

      self::$_gets['filters'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['filters'];
  }

  /**
   * ビヘイビア設定ファイルを読み込みます。
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getBehavior()
  {
    if (empty(self::$_gets['behavior'])) {
      self::$_gets['behavior'] = $merge = self::get(self::TYPE_DEFAULT_GLOBAL_BEHAVIOR);
    }

    return self::$_gets['behavior'];
  }

  /**
   * ヘルパ設定ファイルを読み込みます。
   * ファイルは次の順でマージされます。(一番下が最優先)
   *   o config/global_helpers.yml
   *   o modules/{module_name}/config/helpers.yml
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getHelpers()
  {
    if (empty(self::$_gets['helpers'])) {
      if (Delta_BootLoader::isConfigTypeDefault()) {
        $config = self::merge(self::TYPE_DEFAULT_GLOBAL_HELPERS, self::TYPE_DEFAULT_MODULE_HELPERS);
      } else {
        $config = self::merge(self::TYPE_POLICY_GLOBAL_HELPERS, self::TYPE_DEFAULT_MODULE_HELPERS);
      }

      self::$_gets['helpers'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['helpers'];
  }

  /**
   * サイト設定ファイルを読み込みます。
   * ファイルは次の順でマージされます。(一番下が最優先)
   *   o config/site.yml
   *   o config/site_{hostname}.yml
   *
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getSite()
  {
    if (empty(self::$_gets['site'])) {
      $config = self::getArray(self::TYPE_DEFAULT_SITE);
      self::$_gets['site'] = new Delta_ParameterHolder($config, TRUE);
    }

    return self::$_gets['site'];
  }

  /**
   * カスタム設定ファイルを作成します。
   * ファイルは次の順でマージされます。(一番下が最優先)
   *   o config/{custom_name}.yml
   *   o config/{custom_name}_{hostname}.yml
   *
   * @param string $path APP_ROOT_DIR/config、または '@{path}' 形式の APP_ROOT_DIR から始まるパス。
   *   拡張子を付ける必要はありません。
   * @return Delta_ConfigCustomHolder Delta_ConfigCustomHolder のインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function createCustomFile($path)
  {
    $path = Delta_AppPathManager::buildAbsolutePath('config', $path, 'yml');

    $holder = new Delta_ConfigCustomHolder($path);
    $holder->update();

    return $holder;
  }

  /**
   * カスタム設定ファイルを読み込みます。
   *
   * @param string $path APP_ROOT_DIR/config、または '@{path}' 形式の APP_ROOT_DIR から始まるパス。
   *   拡張子を付ける必要はありません。
   * @return Delta_ParameterHolder ファイルに含まれる設定情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getCustomFile($path)
  {
    $path = Delta_AppPathManager::buildAbsolutePath('config', $path, 'yml');

    $config = self::getArray(self::TYPE_DEFAULT_CUSTOM, $path);
    $holder = new Delta_ConfigCustomHolder($path, $config);

    return $holder;
  }

  /**
   * アプリケーションの設定ファイルを取得します。
   *
   * @param int $type {@link getPath()} メソッドを参照。
   * @param string $include {@link getPath()} メソッドを参照。
   * @return Delta_ParameterHolder 設定ファイルに定義されたデータを Delta_ParameterHolder オブジェクト形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function get($type, $include = NULL)
  {
    return new Delta_ParameterHolder(self::getArray($type, $include), TRUE);
  }

  /**
   * 設定ファイルのパスを取得します。
   * このメソッドは実際に対象ファイルが存在するかどうかのチェックは行いません。
   *
   * @param int $type 参照するファイルタイプ。Delta_Config::TYPE_DEFAULT_* 定数を指定。
   * @param string $include 参照するファイルを指定します。
   *   type が {@link TYPE_DEFAULT_CUSTOM} の場合に有効です。
   *   - TYPE_DEFAULT_CUSTOM: 対象ファイルを絶対パス、または {APP_ROOT_DIR} からの相対パスで指定。
   * @return string ファイルパスを返します。パスが取得できない場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getPath($type, $include = NULL)
  {
    $path = FALSE;

    switch ($type) {
      case self::TYPE_DEFAULT_APPLICATION:
        $path = sprintf('%s%sconfig%sapplication.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
        break;

      case self::TYPE_DEFAULT_BASE_DICON:
        $path = sprintf('%s%sconfig%sbase_dicon.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
        break;

      case self::TYPE_DEFAULT_ROUTES:
        $path = sprintf('%s%sconfig%sroutes.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
        break;

      case self::TYPE_DEFAULT_GLOBAL_FILTERS:
        $path = sprintf('%s%sconfig%sglobal_filters.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
        break;

      case self::TYPE_DEFAULT_MODULE_FILTERS:
        if (Delta_BootLoader::isBootTypeWeb()) {
          $route = Delta_FrontController::getInstance()->getRequest()->getRoute();

          if ($route) {
            $moduleName = $route->getForwardStack()->getLast()->getModuleName();
            $modulePath = Delta_AppPathManager::getInstance()->getModulePath($moduleName);

            $path = sprintf('%s%sconfig%sfilters.yml',
              $modulePath,
              DIRECTORY_SEPARATOR,
              DIRECTORY_SEPARATOR);
          }
        }

        break;

      case self::TYPE_DEFAULT_GLOBAL_BEHAVIOR:
        $path = sprintf('%s%sconfig%sglobal_behavior.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);

        break;

      case self::TYPE_DEFAULT_GLOBAL_HELPERS:
        $path = sprintf('%s%sconfig%sglobal_helpers.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);

        break;

      case self::TYPE_DEFAULT_MODULE_HELPERS:
        if (Delta_BootLoader::isBootTypeWeb()) {
          $route = Delta_FrontController::getInstance()->getRequest()->getRoute();

          if ($route) {
            $modulePath = Delta_AppPathManager::getInstance()->getModulePath($route->getModuleName());

            $path = sprintf('%s%sconfig%shelpers.yml',
              $modulePath,
              DIRECTORY_SEPARATOR,
              DIRECTORY_SEPARATOR);
          }
        }

        break;

      case self::TYPE_DEFAULT_SITE:
        $path = sprintf('%s%sconfig%ssite.yml',
          APP_ROOT_DIR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR);
        break;

      case self::TYPE_DEFAULT_CUSTOM:
        $path = Delta_FileUtils::buildAbsolutePath($include);
        break;

      case self::TYPE_POLICY_APPLICATION:
        $path = DELTA_ROOT_DIR . '/skeleton/blank_application/config/application.yml';
        break;

      case self::TYPE_POLICY_BASE_DICON:
        $path = DELTA_ROOT_DIR . '/skeleton/blank_application/config/base_dicon.yml';
        break;

      case self::TYPE_POLICY_ROUTES:
        $path = DELTA_ROOT_DIR . '/skeleton/blank_application/config/routes.yml';
        break;

      case self::TYPE_POLICY_GLOBAL_FILTERS:
        $path = DELTA_ROOT_DIR . '/skeleton/blank_application/config/global_filters.yml';
        break;

      case self::TYPE_POLICY_GLOBAL_HELPERS:
        $path = DELTA_ROOT_DIR . '/skeleton/blank_application/config/global_helpers.yml';
        break;
    }

    return $path;
  }

  /**
   * アプリケーションの設定ファイルを取得します。
   *
   * @param int $type {@link getPath()} メソッドを参照。
   * @param string $include {@link getPath()} メソッドを参照。
   * @return array 設定ファイルに定義されたデータを配列形式で返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getArray($type, $include = NULL)
  {
    static $cache;

    if ($cache === NULL) {
      $cache = new Delta_YAMLCache();
    }

    $path = self::getPath($type, $include);
    $config = array();

    switch ($type) {
      case self::TYPE_DEFAULT_APPLICATION:
        $config = $cache->get($path, array('compileApplication'), TRUE);
        break;

      case self::TYPE_DEFAULT_BASE_DICON:
        $config = $cache->get($path, array('compileBaseDicon'));
        break;

      case self::TYPE_DEFAULT_ROUTES:
        $config = $cache->get($path, array('compileRoutes'));
        break;

      case self::TYPE_DEFAULT_GLOBAL_FILTERS:
      case self::TYPE_DEFAULT_MODULE_FILTERS:
        if ($type == self::TYPE_DEFAULT_GLOBAL_FILTERS) {
          $callback = 'compileGlobalFilters';
        } else {
          $callback = 'compileModuleFilters';
        }

        if ($path && is_file($path)) {
          $readFromCache = FALSE;
          $config = $cache->get($path, array($callback), FALSE, $readFromCache);
        }

        break;

      case self::TYPE_DEFAULT_GLOBAL_BEHAVIOR:
        if ($type == self::TYPE_DEFAULT_GLOBAL_BEHAVIOR) {
          $callback = 'compileGlobalBehavior';

        } else {
          $callback = 'compileActionBehavior';
        }

        if ($path && is_file($path)) {
          $config = $cache->get($path, array($callback));
        }

        break;

      case self::TYPE_DEFAULT_GLOBAL_HELPERS:
      case self::TYPE_DEFAULT_MODULE_HELPERS:
        if ($type == self::TYPE_DEFAULT_GLOBAL_HELPERS) {
          $callback = 'compileGlobalHelpers';

        } else {
          $callback = 'compileModuleHelpers';
        }

        if ($path && is_file($path)) {
          $readFromCache = FALSE;
          $config = $cache->get($path, array($callback), FALSE, $readFromCache);

          // global_helpers.yml が更新された場合は filters.yml も更新する
          // @see Delta_ConfigCompiler::compileModuleHelpers()
          if (!$readFromCache && $type == self::TYPE_DEFAULT_GLOBAL_HELPERS) {
            $cache->delete(self::getPath(self::TYPE_DEFAULT_MODULE_HELPERS));
          }
        }

        break;

      case self::TYPE_DEFAULT_SITE:
        if (is_file($path)) {
          $config = $cache->get($path, NULL, TRUE);
        }

        break;

      case self::TYPE_DEFAULT_CUSTOM:
        if (is_file($path)) {
          $config = $cache->get($path, NULL, TRUE);
        }

        break;
    }

    return $config;
  }

  /**
   * ポリシーデータを取得します。
   *
   * @param int $type 参照するファイルタイプ。Delta_Config::TYPE_POLICY_* 定数を指定。
   * @return array ポリシーデータを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function getPolicy($type)
  {
    static $compiler;

    if ($compiler === NULL) {
      Delta_CommonUtils::loadVendorLibrary('spyc/spyc.php');
      $compiler = new Delta_ConfigCompiler();
    }

    $config = array();
    $callback = NULL;
    $path = self::getPath($type);

    switch ($type) {
      case self::TYPE_POLICY_APPLICATION:
        $callback = 'compileApplication';
        break;

      case self::TYPE_POLICY_BASE_DICON:
        $callback = 'compileBaseDicon';
        break;

      case self::TYPE_POLICY_ROUTES:
        $callback = 'compileRoutes';
        break;

      case self::TYPE_POLICY_GLOBAL_FILTERS:
        $callback = 'compileGlobalFilters';
        break;

      case self::TYPE_POLICY_GLOBAL_HELPERS:
        $callback = 'compileGlobalHelpers';
        break;
    }

    if ($callback) {
      $arguments = array($path, Spyc::YAMLLoad($path));
      $config = call_user_func_array(array($compiler, $callback), $arguments);
    }

    return $config;
  }
}
