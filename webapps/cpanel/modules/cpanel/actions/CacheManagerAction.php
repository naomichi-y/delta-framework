<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class CacheManagerAction extends Delta_Action
{
  public function execute()
  {
    $view = $this->getView();
    $fileCacheDirectory = APP_ROOT_DIR . '/cache/file';

    if (is_dir($fileCacheDirectory)) {
      $fileCacheSize = Delta_FileUtils::sizeOfDirectory($fileCacheDirectory);
      $fileCacheSize = round($fileCacheSize / 1024, 2);
    } else {
      $fileCacheSize = 0;
    }

    $view->setAttribute('fileCacheSize', $fileCacheSize);

    $templatesCacheDirectory = APP_ROOT_DIR . '/cache/templates';

    if (is_dir($templatesCacheDirectory)) {
      $templateCacheSize = Delta_FileUtils::sizeOfDirectory($templatesCacheDirectory);
      $templateCacheSize = round($templateCacheSize / 1024, 2);
    } else {
      $templateCacheSize = 0;
    }

    $view->setAttribute('templatesCacheSize', $templateCacheSize);

    $yamlCacheDirectory = APP_ROOT_DIR . '/cache/yaml';

    if (is_dir($yamlCacheDirectory)) {
      $yamlCacheSize = Delta_FileUtils::sizeOfDirectory($yamlCacheDirectory);
      $yamlCacheSize = round($yamlCacheSize / 1024, 2);
    } else {
      $yamlCacheSize = 0;
    }

    $view->setAttribute('yamlCacheSize', $yamlCacheSize);

    return Delta_View::SUCCESS;
  }
}
