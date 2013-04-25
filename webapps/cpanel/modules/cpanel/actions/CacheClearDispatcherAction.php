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
    $clearDirectory = APP_ROOT_DIR . '/cache/file';
    $this->getRequest()->setAttribute('clearDirectory', $clearDirectory);

    return 'CacheClear';
  }

  public function dispatchClearTemplatesCache()
  {
    $clearDirectory = APP_ROOT_DIR . '/cache/templates';
    $this->getRequest()->setAttribute('clearDirectory', $clearDirectory);

    return 'CacheClear';
  }

  public function dispatchClearYamlTemplatesCache()
  {
    $clearDirectory = APP_ROOT_DIR . '/cache/yaml';
    $this->getRequest()->setAttribute('clearDirectory', $clearDirectory);

    return 'CacheClear';
  }

  public function dispatchClearAllCache()
  {
    $clearDirectory = APP_ROOT_DIR . '/cache';
    $this->getRequest()->setAttribute('clearDirectory', $clearDirectory);

    return 'CacheClear';
  }
}
