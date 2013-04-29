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
 * {@link Delta_DatabaseTransactionController データベーストランザクション} を制御するリスナです。
 * リスナをアプリケーションに適用することで、アプリケーションロジック側でのコミット制御 (BEGIN、COMMIT、ROLLBACK) が不要となり、プログラムが正常終了する時点で自動コミットが行われるようになります。
 * 尚、プログラム内で例外やエラーが発生 (またはプログラム内で exit を実行) した場合は、全てのトランザクションがロールバックされます。
 *
 * application.yml の設定例:
 * <code>
 * observer:
 *   listeners:
 *     - class: Delta_DatabaseTransactionListener
 * </code>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer.listener
 */
class Delta_DatabaseTransactionListener extends Delta_ApplicationEventListener
{
  /**
   * @var Delta_DatabaseManager
   */
  private $_database;

  /**
   * @see Delta_ApplicationEventListener::getListenEvents()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getListenEvents()
  {
    return array('preProcess', 'postProcess');
  }

  /**
   * @see Delta_ApplicationEventListener::getBootMode()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getBootMode()
  {
    return Delta_BootLoader::BOOT_MODE_WEB|Delta_BootLoader::BOOT_MODE_CONSOLE;
  }

  /**
   * @see Delta_ApplicationEventListener::preProcess()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function preProcess()
  {
    $database = Delta_DIContainerFactory::getContainer()->getComponent('database');
    $database->setTransactionController(new Delta_DatabaseTransactionController());

    $this->_database = $database;
  }

  /**
   * @see Delta_ApplicationEventListener::postProcess()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function postProcess()
  {
    $connections = $this->_database->getConnections();

    foreach ($connections as $connection) {
      $controller = $connection->getTransactionController();

      if ($controller->isActiveTransaction()) {
        $controller->commit();
      }
    }
  }
}
