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
   * 正規表現パターン定数。(アクション名)
   */
  const REGEXP_ACTION = '/^[a-zA-Z]+(\w+)?$/';

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
    $cacheDir = sprintf('%s%scache', APP_ROOT_DIR, DIRECTORY_SEPARATOR);
    Delta_FileUtils::deleteDirectory($cacheDir, FALSE);

    if (!is_dir($cacheDir)) {
      Delta_FileUtils::createDirectoryRecursive($cacheDir, 0775);
    }

    $autoloadDir = $cacheDir . '/file';
    Delta_FileUtils::createDirectoryRecursive($autoloadDir, 0775);

    $templatesDir = $cacheDir . '/templates';
    Delta_FileUtils::createDirectoryRecursive($templatesDir, 0775);

    $yamlDir = $cacheDir . '/yaml';
    Delta_FileUtils::createDirectoryRecursive($yamlDir, 0775);
  }

  /**
   * @param string $type
   * @param string $name
   * @return bool
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private static function checkValidateName($type, $name)
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
    self::checkValidateName(self::REGEXP_COMMAND, $commandName);

    // 出力ディレクトリの取得
    if ($packageName === '/') {
      $writeDirectory = sprintf('%s%sconsole%scommands',
        APP_ROOT_DIR,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR);

    } else {
      $writeDirectory = sprintf('%s%sconsole%scommands%s%s',
        APP_ROOT_DIR,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR,
        ltrim($packageName, '/'));
    }

    if (!is_dir($writeDirectory)) {
      Delta_FileUtils::createDirectoryRecursive($writeDirectory);
    }

    // クラスパスの生成
    $commandClassName = Delta_StringUtils::convertPascalCase($commandName);
    $writePath = sprintf('%s%s%sCommand.php',
      $writeDirectory,
      DIRECTORY_SEPARATOR,
      $commandClassName);

    if (is_file($writePath)) {
      $message = sprintf('Command file of same name exists. [%s]', $writePath);
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
    self::checkValidateName(self::REGEXP_MODULE, $moduleName);

    $modulePath = sprintf('%s/modules/%s', APP_ROOT_DIR, $moduleName);

    Delta_FileUtils::createDirectoryRecursive($modulePath);

    $skeletonPath = DELTA_SKELETON_DIR . '/blank_module';
    $options = array('recursive' => TRUE);

    Delta_FileUtils::copyRecursive($skeletonPath, $modulePath, $options);
    $deployFiles = Delta_FileUtils::search($modulePath, '/.*/', array('directory' => TRUE));

    $fromPath = sprintf('%s%stemplates%shtml%sskeleton.php',
      APP_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);
    $toPath = sprintf('%s%smodules%s%s%stemplates%sindex%s',
      APP_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      $moduleName,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      Delta_Config::getApplication()->getString('view.extension'));

    copy($fromPath, $toPath);
    $deployFiles[] = $toPath;

    $actionFiles = self::addAction($moduleName, 'Index');

    foreach ($actionFiles as $actionFile) {
      $deployFiles[] = $actionFile;
    }

    sort($deployFiles);

    return $deployFiles;
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
    self::checkValidateName(self::REGEXP_THEME, $themeName);

    $themePath = sprintf('%s%s%s',
      $basePath,
      DIRECTORY_SEPARATOR,
      $themeName);

    Delta_FileUtils::createDirectoryRecursive($themePath, 0775, TRUE);

    foreach ($moduleNames as $moduleName) {
      self::checkValidateName(self::REGEXP_MODULE, $moduleName);

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
        Delta_FileUtils::createDirectoryRecursive($path, 0755, TRUE);

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
      Delta_FileUtils::copyRecursive($from, $to, array('recursive' => TRUE));
    }

    $deployFiles = Delta_FileUtils::search($themePath, '/.*/', array('directory' => TRUE));

    return $deployFiles;
  }

  /**
   * モジュールにアクションクラス、ビヘイビア、テンプレートファイルを追加します。
   * テンプレートのスケルトンは APP_ROOT_DIR/templates/html/skeleton.php ファイルが使用されます。
   *
   * @param string $moduleName 対象のモジュール名。
   * @param string $moduleName 追加するアクションの名前。
   *   使用可能な文字は英数字、及び '_' (アンダースコア)。名前は英字から始める必要がある。
   * @param string $packageNmae アクションを配置するパッケージ。
   *   '/foo/bar' をパッケージとした場合、アクションは modules/{module_name}/actions/foo/bar 下に作成される。
   *   パッケージ名は同時に作成れるビヘイビア、テンプレートのパスにも影響する。
   * @return array 作成したファイルのパス情報を返します。
   *   - action: アクションのクラスパス。
   *   - behavior: ビヘイビアファイルパス。
   *   - template: テンプレートのファイルパス。
   * @throws Delta_ParseException 使用できない文字が含まれる場合に発生。
   * @throws Delta_IOException ファイルの作成が失敗した場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public static function addAction($moduleName, $actionName, $packageName = '/')
  {
    $moduleDirectory = sprintf('%s%smodules%s%s',
      APP_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      $moduleName);
    $packageTag = sprintf('modules.%s.actions%s', $moduleName, rtrim(str_replace('/', '.', $packageName), '.'));
    $actionName = Delta_StringUtils::convertPascalCase($actionName);
    $actionFile = $actionName . 'Action.php';

    $actions = Delta_FileUtils::search($moduleDirectory, $actionFile);

    // 同じアクションファイルが存在しないかチェック
    if (sizeof($actions)) {
      $message = sprintf('Action file of same name exists. [%s]', $actions[0]);
      throw new Delta_IOException($message);
    }

    // パッケージ名、アクション名の妥当性をチェック
    self::checkValidateName(self::REGEXP_PACKAGE, $packageName);
    self::checkValidateName(self::REGEXP_ACTION, $actionName);

    // スケルトンテンプレートの取得
    $skeletonDirectory = sprintf('%s%sskeleton',
      DELTA_ROOT_DIR,
      DIRECTORY_SEPARATOR);

    $actionSkeletonPath = sprintf('%s%sblank_action%sBlankAction.php.tpl',
      $skeletonDirectory,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);
    $behaviorSkeletonPath = sprintf('%s%sblank_action%sBlank.yml',
      $skeletonDirectory,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);
    $templateSkeletonPath = sprintf('%s%stemplates%shtml%sskeleton.php',
      APP_ROOT_DIR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);

    // アクションのコピー
    $toDirectory = $moduleDirectory . '/actions';

    if ($packageName !== '/') {
      $toDirectory .= $packageName;
    }

    $toPath = $toDirectory . '/' . $actionFile;

    if (!is_dir($toDirectory)) {
      Delta_FileUtils::createDirectoryRecursive($toDirectory);
    }

    copy($actionSkeletonPath, $toPath);

    $deployFiles = array();
    $deployFiles[] = $toPath;

    $contents = file_get_contents($toPath);
    $contents = str_replace('{%PACKAGE_TAG%}', $packageTag, $contents);
    $contents = str_replace('{%ACTION_NAME%}', $actionName, $contents);

    file_put_contents($toPath, $contents);

    // ビヘイビアのコピー
    $toDirectory = $moduleDirectory . '/behaviors';

    if ($packageName !== '/') {
      $toDirectory .= $packageName;
    }

    if (!is_dir($toDirectory)) {
      Delta_FileUtils::createDirectoryRecursive($toDirectory);
    }

    $toPath = sprintf('%s/%s.yml', $toDirectory, $actionName);
    copy($behaviorSkeletonPath, $toPath);
    $deployFiles[] = $toPath;

    $templateName = Delta_StringUtils::convertSnakeCase($actionName);

    if ($packageName === '/') {
      $packageName = '';
    } else {
      $packageName = ltrim($packageName, '/') . '/';
    }

    $contents = file_get_contents($toPath);
    $contents = str_replace('"{%PACKAGE_NAME%}"', $packageName, $contents);
    $contents = str_replace('index', $templateName, $contents);

    file_put_contents($toPath, $contents);

    // テンプレートのコピー
    if ($packageName === '/') {
      $toDirectory = sprintf('%s%stemplates',
        $moduleDirectory,
        DIRECTORY_SEPARATOR);

    } else {
      $toDirectory = sprintf('%s%stemplates%s',
        $moduleDirectory,
        DIRECTORY_SEPARATOR,
        $packageName);

      if (!is_dir($toDirectory)) {
        Delta_FileUtils::createDirectoryRecursive($toDirectory);
      }
    }

    $toPath = sprintf('%s%s%s%s',
      $toDirectory,
      DIRECTORY_SEPARATOR,
      $templateName,
      Delta_Config::getApplication()->getString('view.extension'));

    copy($templateSkeletonPath, $toPath);
    $deployFiles[] = $toPath;
    sort($deployFiles);

    return $deployFiles;
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
    $modules = Delta_Config::getApplication()->get('module.entries');

    if ($modules) {
      $modules->remove('cpanel');
      $moduleNames = array_keys($modules->toArray());

    } else {
      $moduleNames = array();
    }

    return $moduleNames;
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
