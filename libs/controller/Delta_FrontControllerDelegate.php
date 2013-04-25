<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * {@link Delta_FrontController} から委譲されたイベントプロセスを実行します。
 * Delta_FrontController から委譲されるイベントポイントは次の通りです。
 *   - {@link startup()}
 *   - {@link outputBuffer()}
 *   - {@link dispatchResponse()}
 *   - {@link terminate()}
 *
 * イベントポイントは {@link Delta_ControllerListener コントローラリスナーインタフェース} を実装したクラスを作成することで、イベントの処理を拡張することができます。
 *
 * application.yml の設定例:
 * <code>
 * controller:
 *   # デリゲートクラス (デフォルト)
 *   delegate: Delta_FrontControllerDelegate
 *
 *   # リスナーはカンマ区切りで複数登録することが可能
 *   listener: EmojiListener, TranslationListener
 * </code>
 *
 * イベントリスナークラスの作成:
 * <code>
 * class EmojiListener extends Delta_Object implements Delta_ControllerListener
 * {
 *   // Delta_ControllerListener::getListenerPoints() の実装
 *   public function getListenerPoints()
 *   {
 *     // 拡張したいイベントポイントを返す
 *     return array('outputBuffer')
 *   }
 *
 *   // イベントポイントの定義 (引数と型は Delta_FrontControllerDelegate に合わせる)
 *   public function outputBuffer(&$contnets)
 *   {
 *     // $contents の変換
 *   }
 * }
 * </code>
 * 上記のように {@link Delta_FrontControllerDelegate::outputBuffer()} をイベントポイントとしてリスナーにメソッドを作成した場合、コントローラは outputBuffer() を起動する際にリスナーの EmojiListener::outputBuffer() をコールします。
 * この時、TranslationListener にも outputBuffer() が定義されていると、デリゲートは EmojiListener::outputBuffer、TranslationListener::outputBuffer() の順 ('controller.listener' に定義した順) にプロセスを実行します。
 * イベントプロセスの実行については {@link Delta_FrontControllerDelegate::dispatchEvent()} の解説も合わせて参照して下さい。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package controller
 */

class Delta_FrontControllerDelegate extends Delta_Object
{
  /**
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * @var array
   */
  private $_listeners = array();

  /**
   * コンストラクタ。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function __construct()
  {
    $this->_config = Delta_Config::getApplication();
  }

  /**
   * デリゲートのインスタンスを取得します。
   *
   * @return Delta_FrontControllerDelegate コントローラのデリゲートインスタンスを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  final public static function getInstance()
  {
    static $instance;

    if ($instance === NULL) {
      $instance = new Delta_FrontControllerDelegate();
    }

    return $instance;
  }

  /**
   * コントローラの初期化を行います。
   * このメソッドはルータによってエントリモジュールが確定する前にコールされます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {
    $listeners = $this->getEventListeners();

    foreach ($listeners as $class) {
      $this->addEventListener($class);
    }

    register_shutdown_function(array($this, 'dispatchEvent'), 'terminate');
  }

  /**
   * 登録されている全てのリスナーを取得します。
   *
   * @return array リスナーの配列を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getEventListeners()
  {
    return $this->_config->getArray('controller.listener');
  }

  /**
   * デリゲートオブジェクトにイベントリスナーを追加します。
   *
   * @param string $listener {@link Delta_ControllerListener} を実装したリスナークラスの名前。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addEventListener($listener)
  {
    $instance = new $listener;
    $listenerPoints = $instance->getListenerPoints();

    if (!is_array($listenerPoints)) {
      $listenerPoints = array();
    }

    $this->_listeners[$listener]['callback'] = $listenerPoints;
    $this->_listeners[$listener]['instance'] = $instance;
  }

  /**
   * デリゲートオブジェクトに登録されているイベントリスナーを削除します。
   *
   * @param string $listener 削除対象のリスナークラス名。
   * @return bool 削除に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeEventListener($listener)
  {
    if (isset($this->_listeners[$listener])) {
      unset($this->_listeners[$listener]);
    }
  }

  /**
   * イベントプロセスを開始します。
   * イベントポイントにリスナーが登録されてる場合は処理をリスナーに委譲します。
   * <i>処理の委譲が発生した場合は、本クラスに定義されたイベントポイントのプロセスがスルーされる点に注意して下さい。
   * 例えば 'startup' に対するリスナーが登録されてる場合、リスナー側の startup() は実行されますが、{@link startup()} はコールされません。
   * Delta_FrontControllerDelegate::startup() をコールしたい場合は、リスナー側で Delta_FrontController::getDelegate()->startup() を実行する必要があります。</i>
   *
   * @param string $method イベントポイントとなるメソッドの名前。
   * @param array &$arguments メソッドに渡す引数。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatchEvent($method, array &$arguments = array())
  {
    $catchEvent = FALSE;

    foreach ($this->_listeners as $class => $listener) {
      if (in_array($method, $listener['callback'])) {
        $catchEvent = TRUE;
        call_user_func_array(array($listener['instance'], $method), $arguments);
      }
    }

    if (!$catchEvent) {
      call_user_func_array(array($this, $method), $arguments);
    }
  }

  /**
   * コントローラが対象となるアクションにフォワードする準備が完了した時点でコールされます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function startup()
  {}

  /**
   * クライアントにコンテンツの出力を行う直前にコールされます。
   * 文字列を出力する際は内部的に {@link outputDataDifferentEncoding()} メソッドが実行されます。
   *
   * @param string &$contents 出力対象のコンテンツ。
   * @see Delta_FrontControllerDelegate::outputDataDifferentEncoding()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function outputBuffer(&$contents)
  {
    $array = array(&$contents);
    $response = Delta_DIContainerFactory::getContainer()->getComponent('response');

    if ($response->hasBinary()) {
      $contents = $response->getWriteBuffer();

    } else {
      $internalEncoding = Delta_Config::getApplication()->get('charset.default');
      $outputEncoding = $response->getOutputEncoding();

      if ($internalEncoding !== $outputEncoding) {
        $contents = mb_convert_encoding($contents, $outputEncoding, $internalEncoding);
      }
    }
  }

  /**
   * クライアントにレスポンスを返します。
   * このメソッドはバッファリングされたデータを出力すると同時に HTTP ヘッダを送信します。
   *
   * @param string $buffer 出力バッファ。
   * @see Delta_FrontControllerDelegate::outputBuffer()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function dispatchResponse($buffer)
  {
    $response = Delta_DIContainerFactory::getContainer()->getComponent('response');

    if (!$response->isCommitted()) {
      $arguments = array(&$buffer);
      $this->dispatchEvent('outputBuffer', $arguments);

      $response->write($buffer);
      $response->flush();
    }
  }

  /**
   * フレームワークの処理が完了する直前に実行されます。
   * このメソッドはプログラムを途中で {@link http://php.net/manual/function.exit.php exit} した場合でも実行されます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function terminate()
  {}
}
