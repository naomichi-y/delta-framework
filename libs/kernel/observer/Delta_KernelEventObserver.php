<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * イベントリスナを制御します。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package kernel.observer
 */

class Delta_KernelEventObserver extends Delta_Object
{
  /**
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * @var string
   */
  private $_listener;

  /**
   * @var array
   */
  private $_listeners = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct()
  {
    $this->_config = Delta_Config::getApplication();

    if (Delta_BootLoader::isBootTypeWeb()) {
      $this->_listener = new Delta_WebApplicationEventListener();
    } else {
      $this->_listener = new Delta_ConsoleApplicationEventListener();
    }

    register_shutdown_function(array($this, 'dispatchEvent'), 'preShutdown');
  }

  public function Initialize()
  {
    $listeners = Delta_Config::getApplication()->get('observer.listeners');

    if ($listeners) {
      foreach ($listeners as $listenerId => $attributes) {
        $this->addEventListener($listenerId, $attributes);
      }
    }
  }

  /**
   * オブザーバにイベントリスナを追加します。
   *
   * @param string $listenerId イベントリスナ ID。
   * @param Delta_ParameterHolder $holder イベントリスナ属性。
   * @return イベントリスナの登録に成功した場合は TRUE、失敗 (現在の起動モードと {@link Delta_ApplicationEventListener::getBootMode() リスナの起動モード} が異なる場合に FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addEventListener($listenerId, Delta_ParameterHolder $holder)
  {
    $result = FALSE;

    $className = $holder->get('class');
    $instance = new $className($holder);

    $arrowBootMode = $instance->getBootMode();
    $currentBootMode = Delta_BootLoader::getBootMode();

    if ($arrowBootMode & $currentBootMode) {
      $this->_listeners[$listenerId] = $instance;
      $this->dispatchEvent('preProcess');

      $result = TRUE;
    }

    return $result;
  }

  /**
   * 指定したイベントリスナがオブザーバに登録されているかチェックします。
   *
   * @param string $listenerId チェック対象のイベントリスナ ID。
   * @return bool イベントリスナが登録されている場合は TRUE、登録されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasEventListener($listenerId)
  {
    return isset($this->_listeners[$listenerId]);
  }

  /**
   * オブザーバに登録されている全てのイベントリスナを取得します。
   *
   * @return array オブザーバに登録されている全てのイベントリスナを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEventListeners()
  {
    return $this->_listeners;
  }

  /**
   * オブザーバに登録されているイベントリスナを削除します。
   *
   * @param string $listenerId 削除対象のイベントリスナ ID。
   * @return bool イベントリスナの削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeEventListener($listenerId)
  {
    if (isset($this->_listeners[$listenerId])) {
      unset($this->_listeners[$listenerId]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * オブザーバに登録されているイベントを実行します。
   *
   * @param string $event イベントリスナに登録されているイベント (メソッド) 名。
   * @param array &$arguments イベントリスナに渡す引数のリスト。
   * @throws RuntimeException リスナーにメソッドが定義されていない、またはイベントリスナが {@link Delta_ApplicationEventListener} を継承していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatchEvent($event, array &$arguments = array())
  {
    $catchEvent = FALSE;

    foreach ($this->_listeners as $listenerId => $instance) {
      if ($instance instanceof Delta_ApplicationEventListener) {
        $events = (array) $instance->getListenEvents();

        if (in_array($event, $events)) {
          if (method_exists($instance, $event)) {
            $catchEvent = TRUE;
            call_user_func_array(array($instance, $event), $arguments);

          } else {
            $message = sprintf('Method is not defined. [%s::%s()]', get_class($instance), $event);
            throw new RuntimeException($message);
          }
        }

      } else {
        $message = sprintf('Doesn\'t inherit Delta_ApplicationEventListener. [%s]', get_class($instance));
        throw new RuntimeException($message);
      }
    }

    if (!$catchEvent && method_exists($this->_listener, $event)) {
      call_user_func_array(array($this->_listener, $event), $arguments);
    }
  }
}
