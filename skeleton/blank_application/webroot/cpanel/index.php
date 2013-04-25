<?php
  require '../../config/delta_env.php';

  Delta_BootLoader::run(Delta_BootLoader::BOOT_TYPE_WEB, Delta_BootLoader::CONFIG_TYPE_POLICY);
  Delta_ClassLoader::addSearchPath(DELTA_ROOT_DIR . '/webapps/cpanel/libs');

  // 設定ファイルの初期化
  $appConfig = Delta_Config::getApplication();

  $projectAppConfig = Delta_Config::get(Delta_Config::TYPE_DEFAULT_APPLICATION);
  $appConfig->set('database', $projectAppConfig->getArray('database'));
  $appConfig->set('module', $projectAppConfig->getArray('module'), FALSE);
  $appConfig->set('response.callback', 'none');

  // モジュールディレクトリの設定
  $container = Delta_DIContainerFactory::create();
  Delta_Router::getInstance()->entryModuleRegister('cpanel', DELTA_ROOT_DIR . '/webapps/cpanel/modules/cpanel');

  $controller = $container->getComponent('controller');
  $controller->dispatch();
