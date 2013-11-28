<?php
/**
 * @package controllers
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class CacheController extends Delta_ActionController
{
  public function statusAction()
  {
    $view = $this->getView();

    // ファイルキャッシュサイズの取得
    $fileCachePath = APP_ROOT_DIR . '/cache/file';

    if (is_dir($fileCachePath)) {
      $fileCacheSize = Delta_FileUtils::sizeOfDirectory($fileCachePath);
      $fileCacheSize = round($fileCacheSize / 1024, 2);

    } else {
      $fileCacheSize = 0;
    }

    $view->setAttribute('fileCacheSize', $fileCacheSize);

    // ビューキャッシュサイズの取得
    $viewCachePath = APP_ROOT_DIR . '/cache/templates';

    if (is_dir($viewCachePath)) {
      $viewCacheSize = Delta_FileUtils::sizeOfDirectory($viewCachePath);
      $viewCacheSize = round($viewCacheSize / 1024, 2);

    } else {
      $viewCacheSize = 0;
    }

    $view->setAttribute('viewCacheSize', $viewCacheSize);

    // YAML キャッシュサイズの取得
    $configCachePath = APP_ROOT_DIR . '/cache/yaml';

    if (is_dir($configCachePath)) {
      $yamlCacheSize = Delta_FileUtils::sizeOfDirectory($configCachePath);
      $yamlCacheSize = round($yamlCacheSize / 1024, 2);

    } else {
      $yamlCacheSize = 0;
    }

    $view->setAttribute('yamlCacheSize', $yamlCacheSize);

    $view->setForm('form', $this->createForm());
  }

  public function clearFileCacheAction()
  {
    $targetPath = APP_ROOT_DIR . '/cache/file';
    Delta_FileUtils::deleteDirectory($targetPath, FALSE);

    $this->forward('status');
  }

  public function clearViewCacheAction()
  {
    $targetPath = APP_ROOT_DIR . '/cache/file';
    Delta_FileUtils::deleteDirectory($targetPath, FALSE);

    $this->forward('status');
  }

  public function clearConfigCacheAction()
  {
    $targetPath = APP_ROOT_DIR . '/cache/yaml';
    Delta_FileUtils::deleteDirectory($targetPath, FALSE);

    $this->forward('status');
  }

  public function clearCachesAction()
  {
    $targetPath = APP_ROOT_DIR . '/cache';
    Delta_FileUtils::deleteDirectory($targetPath, FALSE);

    $this->forward('status');
  }
}
