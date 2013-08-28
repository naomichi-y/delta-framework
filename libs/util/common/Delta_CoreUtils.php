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
 * フレームワークの汎用的なユーティリティメソッドを提供します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package util.common
 */
class Delta_CoreUtils
{
  /**
   * 正規表現パターン定数。(モジュール名)
   */
  const REGEXP_MODULE = '/^[a-z]+([\w\-]+)?$/';

  /**
   * 正規表現パターン定数。(パッケージ名)
   */
  const REGEXP_PACKAGE = '/^\/([\w\/]+)?$/';

  /**
   * 正規表現パターン定数。(コマンド名)
   */
  const REGEXP_COMMAND = '/^[a-zA-Z]+(\w+)?$/';

  /**
   * 正規表現パターン定数。(コントローラ名)
   */
  const REGEXP_CONTROLLER = '/^[a-zA-Z]+(\w+)?$/';

  /**
   * 正規表現パターン定数。(テーマ名)
   */
  const REGEXP_THEME = '/^[a-z]+(\w+)?$/';

 /**
   * フレームワークが生成した全てのキャッシュを破棄します。
   * キャッシュ削除対象ディレクトリは次の通りです。
   *   - cache/file
   *   - cache/templates
   *   - cache/yaml
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function clearCache()
  {
    $cachePath = 'cache';
    Delta_FileUtils::deleteDirectory($cachePath, FALSE);

    if (!Delta_FileUtils::isReadable($cachePath)) {
      Delta_FileUtils::createDirectory($cachePath, 0775);
    }

    $autoloadPath = $cachePath . '/file';
    Delta_FileUtils::createDirectory($autoloadPath, 0775);

    $templatesPath = $cachePath . '/templates';
    Delta_FileUtils::createDirectory($templatesPath, 0775);

    $yamlPath = $cachePath . '/yaml';
    Delta_FileUtils::createDirectory($yamlPath, 0775);
  }

  /**
   * @param string $type
   * @param string $name
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function validateName($type, $name)
  {
    if (!preg_match($type, $name)) {
      $message = sprintf('This name can not be used. [%s]', $name);
      throw new Delta_ParseException($message);
    }

    return TRUE;
  }

  /**
   * コマンドを追加します。
   *
   * @param string $commandName コマンド名。
   * @param string $packageNmae コマンドを配置するパッケージ。
   *   '/foo/bar' をパッケージとした場合、コマンドは console/commands/foo/bar 下に作成される。
   * @return string 作成したコマンドのファイルパスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addCommand($commandName, $packageName = '/')
  {
    self::validateName(self::REGEXP_COMMAND, $commandName);

    // 出力ディレクトリの取得
    if ($packageName === '/') {
      $writeDirectory = sprintf('console%scommands', DIRECTORY_SEPARATOR);

    } else {
      $writeDirectory = sprintf('console%scommands%s%s',
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        ltrim($packageName, '/'));
    }

    if (!Delta_Utils::isReadable($writeDirectory)) {
      Delta_FileUtils::createDirectory($writeDirectory);
    }

    // クラスパスの生成
    $commandClassName = Delta_StringUtils::convertPascalCase($commandName);
    $writePath = sprintf('%s%s%sCommand.php',
      $writeDirectory,
      DIRECTORY_SEPARATOR,
      $commandClassName);

    if (is_file($writePath)) {
      $message = sprintf('Command class of same name exists. [%s]', $writePath);
      throw new Delta_IOException($message);
    }

    // スケルトンファイルの取得
    $skeletonPath = sprintf('%s%sskeleton%sblank_command%sBlankCommand.php.tpl',
      DELTA_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);

    copy($skeletonPath, $writePath);

    if ($packageName === '/') {
      $packageTag = 'console.commands';
    } else {
      $packageTag = sprintf('console.commands%s', str_replace('/', '.', $packageName));
    }

    // ファイルの配置
    $contents = file_get_contents($writePath);
    $contents = str_replace('{%PACKAGE_TAG%}', $packageTag, $contents);
    $contents = str_replace('{%COMMAND_NAME%}', $commandClassName, $contents);

    file_put_contents($writePath, $contents);

    return $writePath;
  }

  /**
   * アプリケーションにモジュールを追加します。
   *
   * @param string $moduleName 追加するモジュールの名前。
   *   使用可能な文字は英数字、及び '_' (アンダースコア)。名前は英字から始める必要がある。
   * @return string モジュールのパスを返します。
   * @throws Delta_ParseException 使用できない文字が含まれる場合に発生。
   * @throws Delta_IOException モジュールの作成に失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addModule($moduleName)
  {
    self::validateName(self::REGEXP_MODULE, $moduleName);

    $modulePath = sprintf('modules%s%s', DIRECTORY_SEPARATOR, $moduleName);

    if (is_file($modulePath)) {
      $message = sprintf('Module of same name exists. [%s]', $modulePath);
      throw new Delta_IOException($message);
    }

    Delta_FileUtils::createDirectory($modulePath);

    // モジュールディレクトリ下の定型ディレクトリを作成
    $skeletonPath = DELTA_SKELETON_DIR . '/blank_module';

    $options = array('recursive' => TRUE);
    Delta_FileUtils::copy($skeletonPath, $modulePath, $options);

    $options = array('directory' => TRUE, 'basePath' => APP_ROOT_DIR . DIRECTORY_SEPARATOR);
    $createdFiles = Delta_FileUtils::search($modulePath, '/.*/', $options);

    foreach (self::addController($moduleName, 'Index') as $createFile) {
      $createdFiles[] = $createFile;
    }

    sort($createdFiles);

    return $createdFiles;
  }

  /**
   * @since 2.0
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addController($moduleName, $controllerName)
  {
    $pos = strrpos($controllerName, '/');

    if ($pos === FALSE) {
      $packageTag = sprintf('modules.%s.controllers', $moduleName);
      $deepPath = NULL;
      $controllerName = Delta_StringUtils::convertPascalCase($controllerName);

    } else {
      $packageId = substr($controllerName, 0, $pos);

      $packageTag = sprintf('modules.%s.controllers.%s', $moduleName, str_replace('/', '.', $packageId));
      $deepPath = DIRECTORY_SEPARATOR . $packageId;
      $controllerName = Delta_StringUtils::convertPascalCase(substr($controllerName, $pos + 1));
    }

    self::validateName(self::REGEXP_MODULE, $moduleName);
    self::validateName(self::REGEXP_CONTROLLER, $controllerName);

    // コントローラクラスの作成
    $controllerPath = sprintf('modules%s%s%scontrollers%s%s%sController.php',
      DIRECTORY_SEPARATOR,
      $moduleName,
      DIRECTORY_SEPARATOR,
      $deepPath,
      DIRECTORY_SEPARATOR,
      $controllerName);

    if (Delta_FileUtils::isReadable($controllerPath)) {
      $message = sprintf('Controller class of same name exists. [%s]', $controllerPath);
      throw new Delta_IOException($message);
    }

    $controllerBaseDirectory = dirname($controllerPath);

    if (!Delta_FileUtils::isReadable($controllerBaseDirectory)) {
      Delta_FileUtils::createDirectory($controllerBaseDirectory, 0775, TRUE);
    }

    $skeletonPath = sprintf('%s%sskeleton%sblank_controller%sBlankController.php.tpl',
      DELTA_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);
    Delta_FileUtils::copy($skeletonPath, $controllerPath);

    $contents = Delta_FileUtils::readFile($controllerPath);
    $contents = str_replace('{%PACKAGE_TAG%}', $packageTag, $contents);
    $contents = str_replace('{%CONTROLLER_NAME%}', $controllerName, $contents);
    Delta_FileUtils::writeFile($controllerPath, $contents);

    return array($controllerPath);
  }

  /**
   * 新しいテーマのひな形ファイルを作成します。
   *
   * @param string $basePath テーマの基底パス。
   * @param string $themeName 作成するテーマ名。
   * @param string $moduleName テーマに含めるモジュールリスト。
   * @return array 作成したファイルの一覧を返します。
   * @throws Delta_ParseException モジュールやテーマに指定不可能な文字が含まれる場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addTheme($basePath, $themeName, array $moduleNames)
  {
    self::validateName(self::REGEXP_THEME, $themeName);

    $themePath = sprintf('%s%s%s',
      $basePath,
      DIRECTORY_SEPARATOR,
      $themeName);

    Delta_FileUtils::createDirectory($themePath, 0775, TRUE);

    foreach ($moduleNames as $moduleName) {
      self::validateName(self::REGEXP_MODULE, $moduleName);

      $createList = array(
        sprintf('%s%sdata', $themePath, DIRECTORY_SEPARATOR),
        sprintf('%s%smodules%s%s%stemplates',
          $themePath,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          $moduleName,
          DIRECTORY_SEPARATOR),
        sprintf('%s%swebroot%sassets%scss',
          $themePath,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR),
        sprintf('%s%swebroot%sassets%sjs',
          $themePath,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR),
        sprintf('%s%swebroot%sassets%simages',
          $themePath,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR)
      );

      $config = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);
      $gitkeep = $config->get('project.gitkeep');

      foreach ($createList as $path) {
        Delta_FileUtils::createDirectory($path, 0755, TRUE);

        if ($gitkeep) {
          touch($path . DIRECTORY_SEPARATOR . '.gitkeep');
        }
      }
    }

    $copyFiles = array(
      DELTA_ROOT_DIR . '/skeleton/blank_application/templates' =>
        sprintf('%s%stemplates', $themePath, DIRECTORY_SEPARATOR)
    );

    foreach ($copyFiles as $from => $to) {
      Delta_FileUtils::copy($from, $to, array('recursive' => TRUE));
    }

    $createdFiles = Delta_FileUtils::search($themePath, '/.*/', array('directory' => TRUE));

    return $createdFiles;
  }

  /**
   * フレームワークのバージョンを取得します。
   *
   * @param bool $major TRUE を指定した場合、メジャーなバージョン情報のみを返す。
   * @return string フレームワークのバージョン情報を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getVersion($major = FALSE)
  {
    $string = file_get_contents(DELTA_ROOT_DIR . '/VERSION');
    preg_match('/([0-9]+\.[0-9]+)[0-9\.]*/', $string, $matches);

    if ($major) {
      $value = $matches[1];
    } else {
      $value = $matches[0];
    }

    return $value;
  }

  /**
   * 登録されているモジュールの一覧を取得します。
   *
   * @return array 登録されているモジュールの一覧を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getModuleNames()
  {
    $modulePath = APP_ROOT_DIR . '/modules';
    $modules = array();

    if (is_dir($modulePath)) {
      $paths = scandir($modulePath);

      foreach ($paths as $path) {
        if ($path === '.' || $path === '..') {
          continue;
        }

        if (preg_match(Delta_CoreUtils::REGEXP_MODULE, $path)) {
          $modules[] = $path;
        }
      }
    }

    return $modules;
  }

  /**
   * フレームワークのライブラリ (DELTA_LIBS_DIR) に含まれる全てのファイルパスを取得します。
   *
   * @return array ファイルパスを連想配列で返します。配列のキーに拡張子は含まれません。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function getCoreClasses()
  {
    $options = array('hidden' => FALSE);
    $files = Delta_FileUtils::search(DELTA_LIBS_DIR, '/^[\w]+\.php$/', $options);

    foreach ($files as $file) {
      $info = pathinfo($file);
      $fileName = $info['filename'];

      if (($pos = strpos($fileName, '.')) !== FALSE) {
        $fileName = substr($fileName, 0, $pos);
      }

      $result[$fileName] = $file;
    }

    return $result;
  }
}
