<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * コンソールアプリケーションのためのイベントリスナです。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 */

class Delta_ConsoleApplicationEventListener extends Delta_ApplicationEventListener
{
  /**
   * @see Delta_ApplicationEventListener::getListenEvents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getListenEvents()
  {
    return array();
  }

  /**
   * @see Delta_ApplicationEventListener::getBootMode()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBootMode()
  {
    return Delta_BootLoader::BOOT_MODE_CONSOLE;
  }
}
