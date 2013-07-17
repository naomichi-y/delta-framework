<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('DELTA_ROOT_DIR', dirname(__DIR__));
define('DELTA_LIBS_DIR', sprintf('%s/libs', DELTA_ROOT_DIR));
define('DELTA_SKELETON_DIR', sprintf('%s/skeleton', DELTA_ROOT_DIR));
define('DELTA_BLANK_APP_DIR', sprintf('%s/blank_application', DELTA_SKELETON_DIR));

require_once DELTA_ROOT_DIR . '/vendors/spyc/spyc.php';
require_once DELTA_LIBS_DIR . '/kernel/loader/Delta_BootLoader.php';
require_once DELTA_LIBS_DIR . '/console/Delta_ANSIGraphic.php';
require_once DELTA_LIBS_DIR . '/console/Delta_ConsoleDialog.php';
require_once DELTA_LIBS_DIR . '/console/Delta_ConsoleInput.php';
require_once DELTA_LIBS_DIR . '/console/Delta_ConsoleInputConfigure.php';
require_once DELTA_LIBS_DIR . '/console/Delta_ConsoleOutput.php';

$command = new Delta_CommandExecutor();
$command->parse();
$command->execute();

class Delta_CommandExecutor
{
  private $_input;
  private $_output;
  private $_dialog;
  private $_currentPath;

  public function parse()
  {
    $input = new Delta_ConsoleInput();
    $input->parse();

    $dialog = $input->getDialog();
    $dialog->setSendFormat('> ', ': ');

    $configure = new Delta_ConsoleInputConfigure();
    $configure->addArgument('command', Delta_ConsoleInputConfigure::INPUT_OPTIONAL);
    $configure->addArgument('argument1', Delta_ConsoleInputConfigure::INPUT_OPTIONAL);

    $input->validate($configure);

    $output = new Delta_ConsoleOutput();
    $output->setErrorFormat('ERROR: ');

    $this->_input = $input;
    $this->_output = $output;
    $this->_currentPath = getcwd();
  }

  private function declareAppRootDir($command)
  {
    $result = FALSE;

    switch ($command) {
      case 'create-project':
        $result = TRUE;
        break;

      case 'add-action':
      case 'add-command':
      case 'add-module':
      case 'add-theme':
      case 'clear-cache':
      case 'cc':
      case 'install-database-cache':
      case 'install-database-session':
      case 'install-sample':
        $appRootDir = $this->findAppRootDir($this->_currentPath);

        if ($appRootDir) {
          $result = TRUE;
          define('APP_ROOT_DIR', $appRootDir);
        }

        break;

      case 'compress':
      case 'deploy':
      case 'generate-api':
      case 'help':
      case 'install-path':
      case 'version':
        $result = TRUE;
        define('APP_ROOT_DIR', NULL);

        break;
    }

    return $result;
  }

  public function execute()
  {
    $command = $this->_input->getArgument('command');
    $result = $this->declareAppRootDir($command);

    if ($result) {
      Delta_BootLoader::startDeltaCommand();

      switch ($command) {
        case 'add-action':
          $this->executeAddAction();
          break;

        case 'add-command':
          $this->executeAddCommand();
          break;

        case 'add-module':
          $this->executeAddModule();
          break;

        case 'add-theme':
          $this->executeAddTheme();
          break;

        case 'clear-cache':
        case 'cc':
          $this->executeClearCache();
          break;

        case 'create-project':
          $this->executeCreateProject();
          break;

        case 'compress':
          $this->executeCompress();
          break;

        // フレームワーク開発者用
        case 'deploy':
          $this->executeDeploy();
          break;

        case 'generate-api':
          $this->executeGenerateAPI();
          break;

        case 'help':
          $this->executeHelp();
          break;

        case 'install-database-cache':
          $this->executeInstallDatabaseCache();
          break;

        case 'install-database-session':
          $this->executeInstallDatabaseSession();
          break;

        case 'install-path':
          $this->executeInstallPath();
          break;

        case 'install-sample':
          $this->executeInstallSample();
          break;

        case 'version':
          $this->executeVersion();
          break;
      }

    } else if (strlen($command)) {
      $message = sprintf('Unknown command. [%s]', $command);
      $this->_output->errorLine($message);
      $this->_output->writeBlankLines(1);

      $this->executeHelp();

    } else {
      $this->executeHelp();
    }
  }

  private function findAppRootDir($currentPath)
  {
    $result = FALSE;

    $searchPath = sprintf('%s%sconfig%sdelta_env.php',
      $currentPath,
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR);

    if (is_file($searchPath)) {
      $result = $currentPath;

    } else {
      if (($pos = strrpos($currentPath, DIRECTORY_SEPARATOR)) !== FALSE) {
        $currentPath = substr($currentPath, 0, $pos);
        $result = $this->findAppRootDir($currentPath);

      } else {
        $this->_output->errorLine('Can\'t find the application root directory.');
      }
    }

    return $result;
  }

  private function findModuleDirectory($currentPath)
  {
    $result = FALSE;
    $actionPath = $currentPath . DIRECTORY_SEPARATOR . 'actions';

    if (is_dir($actionPath)) {
      $result = $currentPath;

    } else {
      if (($pos = strrpos($currentPath, DIRECTORY_SEPARATOR)) !== FALSE) {
        $currentPath = substr($currentPath, 0, $pos);
        $result = $this->findModuleDirectory($currentPath);

      } else {
        $this->_output->errorLine('Can\'t find the module directory.');
      }
    }

    return $result;
  }

  private function executeCreateProject()
  {
    $dialog = $this->_input->getDialog();

    // インストールパスの指定
    do {
      $message = sprintf('Install path [%s]: ', $this->_currentPath);
      $this->_output->write($message);
      $installPath = trim(fgets(STDIN));

      if (dirname($installPath) === '.') {
        $this->_output->errorLine('Directory name is invalid.');

      } else {
        break;
      }

    } while (TRUE);

    if (strlen($installPath) == 0) {
      $installPath = $this->_currentPath;

    } else {
      $installPath = realpath(rtrim($installPath, '\'"\\/'));
    }

    $installPath = str_replace('\\', '/', $installPath);
    $currentPath = substr($installPath, strrpos($installPath, '/') + 1);

    // プロジェクト名の指定
    $message = sprintf('Project name [%s]', $currentPath);
    $projectName = $dialog->send($message);

    if (strlen($projectName) == 0) {
      $projectPath = $installPath;

    } else {
      if (substr($installPath, -1) === '/') {
        $projectPath = $installPath . $projectName;
      } else {
        $projectPath = sprintf('%s/%s', $installPath, $projectName);
      }
    }

    define('APP_ROOT_DIR', $projectPath);
    $result = FALSE;

    if (is_dir(APP_ROOT_DIR)) {
      $message = 'Directory already exists. Are you sure to overwrite it? (Y/N)';
      $result = $dialog->sendChoice($message, array('y', 'n'), FALSE);

    } else {
      if (Delta_FileUtils::createDirectoryRecursive(APP_ROOT_DIR, 0755, TRUE)) {
        $result = TRUE;

      } else {
        $message = sprintf('Failed to create directory. [%s]', APP_ROOT_DIR);
        $this->_output->writeLine($message);
      }
    }

    if ($result) {
      // VCS の設定
      $message = 'Do you want to create a .gitkeep to empty directory? (Y/N)';

      if ($dialog->sendConfirm($message)) {
        $isCreateGitkeep = TRUE;
      } else {
        $isCreateGitkeep = FALSE;
      }

      // スケルトンディレクトリのコピー
      $options = array('recursive' => TRUE, 'hidden' => TRUE);
      Delta_FileUtils::copyRecursive(DELTA_BLANK_APP_DIR, APP_ROOT_DIR, $options);

      // deltac コマンドの実行権限付与
      $path = APP_ROOT_DIR . '/console/deltac';
      chmod($path, 0775);

      // .htaccessのコピー
      $sourcePath = DELTA_BLANK_APP_DIR . '/webroot/.htaccess';
      $destinationPath = APP_ROOT_DIR . '/webroot/.htaccess';
      copy($sourcePath, $destinationPath);

      $sourcePath = DELTA_BLANK_APP_DIR . '/webroot/cpanel/.htaccess';
      $destinationPath = APP_ROOT_DIR . '/webroot/cpanel/.htaccess';
      copy($sourcePath, $destinationPath);

      // delta_env.phpのコピー
      $sourcePath = DELTA_BLANK_APP_DIR . '/config/delta_env.php';
      $destinationPath = APP_ROOT_DIR . '/config/delta_env.php';

      $contents = str_replace('{%DELTA_ROOT_DIR%}', DELTA_ROOT_DIR, file_get_contents($sourcePath));
      file_put_contents($destinationPath, $contents);

      // パーミッションの変更
      $cacheDir = APP_ROOT_DIR . '/cache';
      chmod($cacheDir, 0775);

      $logsDir = APP_ROOT_DIR . '/logs';
      chmod($logsDir, 0775);

      $tmpDir = APP_ROOT_DIR . '/tmp';
      chmod($tmpDir, 0775);

      // デフォルトモジュールの作成
      $moduleName = $this->executeAddModule(TRUE);

      // Spyc::YAMLDump() を使うと可視性が下がるのでここでは使用しない
      $path = APP_ROOT_DIR . '/config/application.yml';

      $secretKey = hash('sha1', uniqid(mt_srand(), TRUE));
      $password = Delta_StringUtils::buildRandomString(8, Delta_StringUtils::STRING_CASE_LOWER|Delta_StringUtils::STRING_CASE_NUMERIC);

      $contents = file_get_contents($path);
      $contents = str_replace('"{%SECRET_KEY%}"', $secretKey, $contents);
      $contents = str_replace('"{%CPANEL.PASSWORD%}"', $password, $contents);
      $contents = str_replace('"{%MODULE.ENTRY%}"', $moduleName, $contents);
      $contents = str_replace('"{%MODULE.UNKNOWN%}"', $moduleName, $contents);
      $contents = str_replace('"{%MODULE.ENTRIES.INIT%}"', $moduleName, $contents);

      if ($isCreateGitkeep) {
        $replaceGitKeep = 'TRUE';
      } else {
        $replaceGitKeep = 'FALSE';
      }

      $contents = str_replace('"{%REPOSITORY.GITKEEP%}"',  $replaceGitKeep, $contents);

      file_put_contents($path, $contents);

      // 設定情報確認アクションのコピー
      $sourcePath = DELTA_SKELETON_DIR . '/setup_info';
      $destinationPath = APP_ROOT_DIR . '/modules/' . $moduleName;

      $options = array('recursive' => TRUE);
      Delta_FileUtils::copyRecursive($sourcePath, $destinationPath, $options);

      $contents = Delta_FileUtils::readFile($sourcePath . '/actions/IndexAction.php');
      $contents = str_replace('{%PACKAGE_NAME%}', $moduleName . '.actions', $contents);
      Delta_FileUtils::writeFile($destinationPath . '/actions/IndexAction.php', $contents);

      $message = sprintf('Project installation is complete. [%s]', APP_ROOT_DIR);
      $this->_output->writeLine($message);

      $message = 'Do you want to install the sample application? (Y/N)';
      $result = $dialog->sendConfirm($message);

      if ($result) {
        $this->executeInstallSample();
      }

      // .gitkeep を作成しない場合は APP_ROOT_DIR から除外する
      if (!$isCreateGitkeep) {
        $files = Delta_FileUtils::search(APP_ROOT_DIR, '.gitkeep');

        foreach ($files as $file) {
          unlink($file);
        }

      } else {
        $file = APP_ROOT_DIR . '/modules/.gitkeep';
        unlink($file);
      }
    }
  }

  private function executeInstallSample()
  {
    $sampleDirectory = DELTA_ROOT_DIR . '/webapps/sample_application';
    $options = array('recursive' => TRUE);
    Delta_FileUtils::copyRecursive($sampleDirectory, APP_ROOT_DIR, $options);

    // site.yml のマージ
    $array1 = Spyc::YAMLLoad(APP_ROOT_DIR . '/config/site.yml');
    $array2 = Spyc::YAMLLoad($sampleDirectory . '/config/site_merge.yml');

    $config = Delta_ArrayUtils::mergeRecursive($array1, $array2);

    $custom = Delta_Config::createCustomFile('site');
    $custom->setArray($config);
    $custom->update();

    Delta_FileUtils::deleteFile('config/site_merge.yml');

    // global_helpers.yml のマージ
    $array1 = Spyc::YAMLLoad(APP_ROOT_DIR . '/config/global_helpers.yml');
    $array2 = Spyc::YAMLLoad($sampleDirectory . '/config/global_helpers_merge.yml');

    $config = Delta_ArrayUtils::mergeRecursive($array1, $array2);

    $custom = Delta_Config::createCustomFile('global_helpers');
    $custom->setArray($config);
    $custom->update();

    Delta_FileUtils::deleteFile('config/global_helpers_merge.yml');

    $message = sprintf("Sample application install completed. [%s]\n\n"
      ."Please add settings to the file.\n"
      ."%s"
      ."{config/application.yml}\n"
      ."module:\n"
      ."  entries:\n"
      ."    front:\n"
      ."      default: Start\n"
      ."      unknown: Start\n"
      ."    admin:\n"
      ."      default: LoginForm\n"
      ."      unknown: LoginForm\n"
      ."%s",
    APP_ROOT_DIR,
    $this->_output->getSeparator(),
    $this->_output->getSeparator());

    $this->_output->write($message);
  }

  private function executeAddModule($isDefaultModule = FALSE)
  {
    $moduleName = NULL;
    $dialog = $this->_input->getDialog();

    do {
      if ($isDefaultModule) {
        $moduleName = $dialog->send('Create default module name [entry]');
      } else {
        $moduleName = $dialog->send('Create module name', TRUE);
      }

      if ($isDefaultModule && strlen($moduleName) == 0) {
        $moduleName = 'entry';
      }

      try {
        $deployFiles = Delta_CoreUtils::addModule($moduleName);
        $deployFiles = implode("\n  - ", $deployFiles);

        $message = sprintf("Create module is complete.\n  - %s", $deployFiles);
        $this->_output->writeLine($message);

        if (!$isDefaultModule) {
          $message = sprintf("\nPlease add settings to the file.\n"
            ."%s"
            ."{config/application.yml}\n"
            ."module:\n"
            ."  entries:\n"
            ."    %s:\n"
            ."      default: Index\n"
            ."      unknown: Index\n"
            ."%s",
          $this->_output->getSeparator(),
          $moduleName,
          $this->_output->getSeparator());

          $this->_output->write($message);
          $this->executeClearCache(FALSE);
        }

        break;

      } catch (Exception $e) {
        $this->_output->errorLine($e->getMessage());
      }

    } while (TRUE);

    return $moduleName;
  }

  private function executeAddAction()
  {
    $moduleDirectory = $this->findModuleDirectory($this->_currentPath);

    if ($moduleDirectory) {
      $moduleName = basename($moduleDirectory);
      $dialog = $this->_input->getDialog();

      do {
        $message = 'Add action name (e.g. \'{package_name}/HelloWorld\')';
        $response = $dialog->send($message, TRUE);
        $parser = $this->parseActionAndCommandArgument($response);

        try {
          $deployFiles = Delta_CoreUtils::addAction($moduleName, $parser->actionName, $parser->packageName);
          $deployFiles = implode("\n  - ", $deployFiles);

          $message = sprintf("Create action is complete.\n  - %s", $deployFiles);
          $this->_output->writeLine($message);
          break;

        } catch (Exception $e) {
          $this->_output->errorLine($e->getMessage());
        }

      } while (TRUE);
    } // end if
  }

  private function parseActionAndCommandArgument($argument)
  {
    $packageName = '/';
    $actionName = NULL;

    if (($pos = strrpos($argument, '/')) === FALSE) {
      $actionName = $argument;

    } else {
      $packageName = substr($argument, 0, $pos);

      if (substr($packageName, 0, 1) !== '/') {
        $packageName = '/' . $packageName;
      }

      $actionName = substr($argument, $pos + 1);
    }

    $parser = new stdClass();
    $parser->packageName = $packageName;
    $parser->actionName = $actionName;

    return $parser;
  }

  private function executeAddCommand()
  {
    $message = 'Add command name (e.g. \'{package_name}/HelloWorld\')';
    $dialog = $this->_input->getDialog();

    do {
      $response = $dialog->send($message, TRUE);
      $parser = $this->parseActionAndCommandArgument($response);

      try {
        $deployFile = Delta_CoreUtils::addCommand($parser->actionName, $parser->packageName);
        $message = sprintf("Create command is complete.\n  - %s", $deployFile);

        $this->_output->writeLine($message);

        break;

      } catch (Exception $e) {
        $this->_output->errorLine($e->getMessage());
      }

    } while (TRUE);
  }

  private function executeAddTheme()
  {
    $themeConfig = Delta_Config::getApplication()->get('theme');
    $dialog = $this->_input->getDialog();

    if (isset($themeConfig['basePath'])) {
      if (Delta_FileUtils::isAbsolutePath($themeConfig['basePath'])) {
        $basePath = $themeConfig['basePath'];
      } else {
        $basePath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $themeConfig['basePath'];
      }

      $basePathConfig = $basePath;

    } else {
      $basePath = sprintf('%s%stheme', APP_ROOT_DIR, DIRECTORY_SEPARATOR);
      $basePathConfig = 'theme';
    }

    $isCustomPath = FALSE;

    // テーマディレクトリの作成
    if (!is_dir($basePath)) {
      do {
        $message = sprintf('Create theme directory. [%s]', $basePath);
        $response = $dialog->send($message);

        try {
          if (strlen($response)) {
            if (Delta_FileUtils::isAbsolutePath($response)) {
              $createPath = $response;
            } else {
              $createPath = APP_ROOT_DIR . DIRECTORY_SEPARATOR . $response;
            }

          } else {
            $createPath = $basePath;
          }

          Delta_FileUtils::createDirectoryRecursive($createPath);
          $message = sprintf('  - %s', $createPath);
          $this->_output->writeLine($message);

          $basePath = $createPath;

          if (strpos($basePath, APP_ROOT_DIR) === 0) {
            $basePathConfig = substr($basePath, strlen(APP_ROOT_DIR) + 1);
          } else {
            $basePathConfig = $basePath;
          }

          break;

        } catch (Exception $e) {
          $this->_output->errorLine($e->getMessage());
        }

      } while (TRUE);
    }

    $basePath = Delta_FileUtils::buildAbsolutePath($basePath);
    $modules = array();

    do {
      $themeName = $dialog->send('Create theme name', TRUE);
      $themePath = sprintf('%s%s%s', $basePath, DIRECTORY_SEPARATOR, $themeName);

      if (is_dir($themePath)) {
        $this->_output->writeLine('WARNING: This theme is already exists.');
      }

      if ($themeName) {
        $message = 'Assign modules (e.g. front,backend,... or \'*\')';
        $response = $dialog->send($message, TRUE);

        if ($response === '*') {
          $appConfig = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);
          $moduleNames = $appConfig->getArray('module.entries');

          unset($moduleNames['cpanel']);
          $moduleNames = array_keys($moduleNames);

        } else {
          $moduleNames = explode(',', $response);
        }

        if (sizeof($moduleNames)) {
          $modules = array_unique($moduleNames);
          $files = NULL;

          try {
            $files = Delta_CoreUtils::addTheme($basePath, $themeName, $modules);
            $files = implode("\n  - ", $files);

            $message = sprintf("Create files:\n  - %s", $files);
            $this->_output->writeLine($message);

            break;

          } catch (Exception $e) {
            $this->_output->errorLine($e->getMessage());
          }

        } // end if

      } // end if

    } while (TRUE);

    $message = sprintf("Create theme is complete.\n"
      ."Please add settings to the file.\n"
      ."%s"
      ."{config/application.yml}\n"
      ."theme:\n"
      ."  name: %s\n"
      ."  basePath: %s\n"
      ."  modules: \n    - %s\n"
      ."%s",
    $this->_output->getSeparator(),
    $themeName,
    $basePathConfig,
    implode("\n    - ", $modules),
    $this->_output->getSeparator());

    $this->_output->write($message);
  }

  private function executeClearCache($output = TRUE)
  {
    Delta_CoreUtils::clearCache();

    if ($output) {
      $this->_output->writeLine('Clear cache completed.');
    }
  }

  private function executeCompress()
  {
    require DELTA_ROOT_DIR . '/command/classes/Delta_CodeCompressor.php';

    $compressor = new Delta_CodeCompressor();
    $compressor->execute();
  }

  private function executeInstallDatabaseCache()
  {
    $dataSourceId = $this->getInstallDataSourceId();

    if ($this->createTable('cache/ddl.yml', $dataSourceId)) {
      $separator = $this->_output->getSeparator();

      $message = sprintf("Create database cache is complete.\n\n"
        ."Please add settings to the file.\n"
        ."%s"
        ."{config/application.yml}\n"
        ."cache:\n"
        ."  database:\n"
        ."    dataSource: %s\n"
        ."%s\n"
        ."Use:\n"
        ."%s"
        ."\$cache = Delta_CacheManager::getInstance(Delta_CacheManager::CACHE_TYPE_DATABASE);\n"
        ."\$cache->set('foo', \$data);\n"
        ."echo \$cache->get('foo');\n"
        ."%s",
        $separator,
        $dataSourceId,
        $separator,
        $separator,
        $separator);
      $this->_output->write($message);
    }
  }

  private function executeInstallDatabaseSession()
  {
    $dataSourceId = $this->getInstallDataSourceId();

    if ($this->createTable('session/ddl.yml', $dataSourceId)) {
      $separator = $this->_output->getSeparator();

      $message = sprintf("Create database session is complete.\n\n"
        ."Please add settings to the file.\n"
        ."%s"
        ."{config/application.yml}\n"
        ."session:\n"
        ."  handler:\n"
        ."    class: Delta_DatabaseSessionHandler\n"
        ."    dataSource: %s\n"
        ."%s",
      $separator,
      $dataSourceId,
      $separator);

      $this->_output->write($message);
    }
  }

  private function getInstallDataSourceId()
  {
    $dataSourceId = $this->_input->getDialog()->send('Install data source of database. [default]');

    if (strlen($dataSourceId) == 0) {
      $dataSourceId = 'default';
    }

    return $dataSourceId;
  }

  private function createTable($path, $dataSourceId)
  {
    $result = FALSE;
    $appConfig = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);

    $key = 'database.' . $dataSourceId;
    $connectConfig = $appConfig->get($key);

    if (!$connectConfig) {
      $message = sprintf('Definition of database can\'t be found. [%s]', $dataSourceId);
      $this->_output->errorLine($message);

    } else {
      // テーブルの作成
      $path = sprintf('%s/database/%s', DELTA_SKELETON_DIR, $path);
      $data = Spyc::YAMLLoad($path);

      Delta_DIContainerFactory::create();

      if ($connectConfig) {
        $dsn = $connectConfig->get('dsn');
        $user = $connectConfig->get('user');
        $password = $connectConfig->get('password');

        $database = Delta_DIContainerFactory::getContainer()->getComponent('database');
        $conn = $database->getConnectionWithConfig($dsn, $user, $password);
        $command = $conn->getCommand();

        foreach ($data['tables'] as $table) {
          if (!$command->existsTable($table['name'])) {
            $command->createTable($table);

            $message = sprintf("Create table %s.%s.", $dataSourceId, $table['name']);
            $this->_output->writeLine($message);
            $result = TRUE;

          } else {
            $message = sprintf('Table already exists. [%s.%s]', $dataSourceId, $table['name']);
            $this->_output->errorLine($message);
          }
        }

      } else {
        $this->_output->errorLine('Data source does not exists. [default]');
      }
    }

    return $result;
  }

  private function executeInstallPath()
  {
    $this->_output->writeLine(DELTA_ROOT_DIR);
  }

  private function executeGenerateAPI()
  {
    $sourcePath = $this->_input->getArgument('argument1');
    $outputPath = $this->_input->getOption('output-dir');
    $excludes = $this->_input->getOption('excludes');

    if ($excludes !== NULL) {
      $excludes = explode(',', $excludes);
    } else {
      $excludes = array();
    }

    $title = $this->_input->getOption('title', 'Application API');

    if ($sourcePath === NULL) {
      $buffer = "USAGE: \n"
       ."  delta generate-api [ARGUMENT] [OPTIONS]\n\n"
       ."ARGUMENT:\n"
       ."  source-dir                 Source directory path. (e.g. 'delta generate-api /var/repos/project')\n\n"
       ."OPTIONS:\n"
       ."  --output-dir={output_path} Output directory path. (default: {source-dir}/api)\n"
       ."  --excludes={excludes}      List of exclude directories. (foo,bar,baz...)\n"
       ."  --title={title}            TItle of API.\n";

      $this->_output->write($buffer);

    } else {
      // 入力パスを取得
      $realSourcePath = realpath($sourcePath);

      if ($realSourcePath === FALSE) {
        $message = sprintf("Can't find the source path. [%s]", $sourcePath);
        $this->_output->errorLine($message);

      } else {
        // 出力パスの取得
        if ($outputPath === NULL) {
          $realOutputPath = sprintf('%s%sapi', $this->_currentPath, DIRECTORY_SEPARATOR);
        } else if (!Delta_FileUtils::isAbsolutePath($outputPath)) {
          $realOutputPath = sprintf('%s%s%s', $this->_currentPath, DIRECTORY_SEPARATOR, $outputPath);
        }

        if (!is_dir($realOutputPath)) {
          Delta_FileUtils::createDirectoryRecursive($realOutputPath);
        }

        $this->buildAPI($realSourcePath, $realOutputPath, $title, $excludes);
      }
    }
  }

  private function executeDeploy()
  {
    // cpanel のリソースを最新版に置き換える
    $sourcePath = DELTA_ROOT_DIR . '/docs/manual/assets/css/base.css';
    $destinationPath = DELTA_ROOT_DIR . '/webapps/cpanel/webroot/assets/base/delta/css/base.css';
    copy($sourcePath, $destinationPath);

    $sourcePath = DELTA_ROOT_DIR . '/docs/manual/assets/images/logo.png';
    $destinationPath = DELTA_ROOT_DIR . '/webapps/cpanel/webroot/assets/base/delta/images/logo.png';
    copy($sourcePath, $destinationPath);

    $outputPath = DELTA_ROOT_DIR . '/docs/api';

    $title = sprintf('delta %s API Reference', Delta_CoreUtils::getVersion(TRUE));
    $this->buildAPI(DELTA_LIBS_DIR, $outputPath, $title, array(), TRUE);
  }

  private function buildAPI($sourcePath, $outputPath, $title, $excludes = array(), $buildCoreAPI = FALSE)
  {
    $this->_output->writeLine('Initializing API Generator...');

    Delta_DIContainerFactory::create();

    $this->_output->writeLine('Parsing of source code...');

    $generator = new Delta_APIGenerator($sourcePath);
    $generator->setExcludeDirectories($excludes);
    $generator->setTitle($title);
    $generator->setOutputDirectory($outputPath);
    $generator->make($buildCoreAPI);

    $this->_output->writeLine('Building API...');
    $generator->build();

    $this->_output->writeLine('Writing API...');
    $generator->write();

    $message = sprintf("Process was successful.\n  - %s", $outputPath);
    $this->_output->writeLine($message);
  }

  private function executeVersion()
  {
    $this->_output->writeLine(Delta_CoreUtils::getVersion());
  }

  private function executeHelp()
  {
    $buffer = "USAGE: \n"
     ."  delta [OPTIONS]\n\n"
     ."OPTIONS:\n"
     ."  add-action               Add action to current module.\n"
     ."                           If you want to use a skeleton template,\n"
     ."                           please edit '{APP_ROOT_DIR}/templates/html/skeleton.php'.\n"
     ."  add-command              Add command to current project.\n"
     ."  add-module               Add module to current project.\n"
     ."  add-theme                Add theme to current project.\n"
     ."  clear-cache [cc]         Clear the cache of all.\n"
     ."  create-project           Create new project.\n"
     ."  compress                 Compress source of framework.\n"
     ."  generate-api             Generate API from source code. \n"
     ."  help                     Show how to use the command.\n"
     ."  install-database-cache   Create a database cache table. (see: Delta_DatabaseCache class)\n"
     ."  install-database-session Create a database session table. (see: Delta_DatabaseSessionHandler class)\n"
     ."  install-path             Get directory path of the framework.\n"
     ."  install-sample           Install sample application.\n"
     ."  version                  Get version information.";

    $this->_output->writeLine($buffer);
  }
}

