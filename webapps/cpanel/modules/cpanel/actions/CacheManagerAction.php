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
    $fileCachePath = APP_ROOT_DIR . '/cache/file';

    if (is_dir($fileCachePath)) {
      $fileCacheSize = Delta_FileUtils::sizeOfDirectory($fileCachePath);
      $fileCacheSize = round($fileCacheSize / 1024, 2);
    } else {
      $fileCacheSize = 0;
    }

    $view->setAttribute('fileCacheSize', $fileCacheSize);

    $viewsCachePath = APP_ROOT_DIR . '/cache/views';

    if (is_dir($viewsCachePath)) {
      $viewCacheSize = Delta_FileUtils::sizeOfDirectory($viewsCachePath);
      $viewCacheSize = round($viewCacheSize / 1024, 2);
    } else {
      $viewCacheSize = 0;
    }

    $view->setAttribute('viewsCacheSize', $viewCacheSize);

    $yamlCachePath = APP_ROOT_DIR . '/cache/yaml';

    if (is_dir($yamlCachePath)) {
      $yamlCacheSize = Delta_FileUtils::sizeOfDirectory($yamlCachePath);
      $yamlCacheSize = round($yamlCacheSize / 1024, 2);
    } else {
      $yamlCacheSize = 0;
    }

    $view->setAttribute('yamlCacheSize', $yamlCacheSize);

    return Delta_View::SUCCESS;
  }
}
