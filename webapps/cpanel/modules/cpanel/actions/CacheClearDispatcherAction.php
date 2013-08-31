<?php
/**
 * @package actions
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 */
class CacheClearDispatcherAction extends Delta_DispatchAction
{
  public function defaultForward()
  {
    return 'CacheManager';
  }

  public function dispatchClearFileCache()
  {
    $deleteCachePath = APP_ROOT_DIR . '/cache/file';
    $this->getRequest()->setAttribute('deleteCachePath', $deleteCachePath);

    return 'CacheClear';
  }

  public function dispatchClearViewsCache()
  {
    $deleteCachePath = APP_ROOT_DIR . '/cache/views';
    $this->getRequest()->setAttribute('deleteCachePath', $deleteCachePath);

    return 'CacheClear';
  }

  public function dispatchClearYamlViewsCache()
  {
    $deleteCachePath = APP_ROOT_DIR . '/cache/yaml';
    $this->getRequest()->setAttribute('deleteCachePath', $deleteCachePath);

    return 'CacheClear';
  }

  public function dispatchClearAllCache()
  {
    $deleteCachePath = APP_ROOT_DIR . '/cache';
    $this->getRequest()->setAttribute('deleteCachePath', $deleteCachePath);

    return 'CacheClear';
  }
}
