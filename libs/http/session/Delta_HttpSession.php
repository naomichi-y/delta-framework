<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ユーザセッションを管理するための低レベル API を提供します。
 *
 * このクラスは 'session' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_DIController::getSession()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session
 */

class Delta_HttpSession extends Delta_Object
{
  /**
   * セッション属性。
   * @var Delta_ParameterHolder
   */
  private $_config;

  /**
   * セッションが開始されているかどうか。
   * @var bool
   */
  private $_isActive = FALSE;

  /**
   * セッションオブジェクトを初期化します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {
    $this->_config = Delta_Config::getApplication()->get('session');

    if ($this->_config->getBoolean('autoStart')) {
      $this->activate();
    }

    register_shutdown_function(array($this, 'finalize'));
  }

  /**
   * セッションを開始します。
   * このメソッドはフレームワークによってセッションが開始されたタイミングで内部的にコールされます。
   * <code>
   * // セッションが有効な状態にあるかどうかチェック
   * $session->isActive();
   *
   * // セッションを閉じる (通常はコールする必要はない)
   * $session->close();
   *
   * // 新しいセッションを開始する
   * $session->active();
   * </code>
   *
   * @return bool セッションの開始に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function activate()
  {
    if ($this->_isActive) {
      return FALSE;
    }

    $config = $this->_config;

    // セッション維持方法の取得
    switch ($config->get('store')) {
      case 'transparent':
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);

        ini_set('arg_separator.output', '&amp;');
        break;

      case 'cookie':
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
        ini_set('arg_separator.output', '&');
        break;
    }

    // 有効期限の設定
    $timeout = $config->getInt('timeout');

    if ($timeout > 0) {
      $lifetime = ini_get('session.gc_maxlifetime');

      if (Delta_StringUtils::nullOrEmpty($lifetime) || $lifetime < $timeout) {
        ini_set('session.gc_maxlifetime', $timeout);
      }
    }

    // セッション ID のランダム性を強化
    if (PHP_OS === 'Linux') {
      // '/dev/random' はロックする可能性があるため使用しない
      $file = '/proc/net/dev';
      $openDir = ini_get('open_basedir');

      if (empty($openDir) || preg_match('/^\/proc/', $openDir) || preg_match('/:\/proc/', $openDir)) {
        ini_set('session.entropy_file', $file);
        ini_set('session.entropy_length', 32);
      }
    }

    ini_set('session.entropy_length', 32);
    ini_set('session.hash_function', 'sha-256');

    // セッション名の変更
    session_name($config->get('name'));

    // Cookie の制御
    $lifetime = $config->get('cookieLifetime');
    $path = $config->get('cookiePath');
    $domain = $config->get('cookieDomain');
    $secure = $config->getBoolean('cookieSecure');
    $httpOnly = $config->getBoolean('cookieHttpOnly');

    session_set_cookie_params($lifetime, $path, $domain, $secure, $httpOnly);

    // セッションハンドラの起動
    $handlerConfig = $config->get('handler');

    if ($handlerConfig) {
      $handlerClass = $handlerConfig->get('class');

      ini_set('session.save_handler', 'user');
      call_user_func_array(array($handlerClass, 'handler'), array($handlerConfig));
    }

    session_cache_limiter('none');

    // セッションの開始
    if (session_start()) {
      $this->_isActive = TRUE;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * セッションが開始されているかチェックし、開始されていなければ例外をスローします。
   *
   * @return bool セッションが開始されている場合に TRUE を返します。
   * @throws RuntimeException セッションが開始されていない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  private function checkActived()
  {
    if (!$this->_isActive) {
      $message = 'Session has not been started.';
      throw new RuntimeException($message);
    }

    return TRUE;
  }

  /**
   * 現在有効なセッション ID を取得します。
   *
   * @return string 現在有効なセッション ID を返します。
   * @throws RuntimeException セッションが開始していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getId()
  {
    if ($this->checkActived()) {
      return session_id();
    }
  }

  /**
   * 現在有効なセッション名を取得します。
   *
   * @return string 現在有効なセッション名を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getName()
  {
    return session_name();
  }

  /**
   * セッション ID を作り直します。
   * ユーザが既に保持しているデータは新しいセッションに引き継がれます。
   *
   * @param string $updateId 新しいセッション ID 。未指定の場合は自動生成される。
   * @return bool 更新に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @throws RuntimeException セッションが開始していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function updateId($updateId = NULL)
  {
    $result = FALSE;

    if ($this->checkActived()) {
      if ($updateId === NULL) {
        $result = session_regenerate_id(TRUE);

      } else {
        $data = $_SESSION;

        $this->clear();
        $this->close();

        session_id($updateId);
        $this->activate();

        $_SESSION = $data;
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * セッションが開始されているかどうかチェックします。
   *
   * @return bool セッションが開始している場合は TRUE、開始していない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isActive()
  {
    return $this->_isActive;
  }

  /**
   * 現在のセッションに関連付けられている全てのデータを破棄します。
   *
   * @return bool セッションの破棄に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    if ($this->_isActive) {
      $_SESSION = array();
      $sessionName = session_name();

      $container = Delta_DIContainerFactory::getContainer();
      $container->getComponent('user')->initialize();

      if (isset($_COOKIE[$sessionName])) {
        setcookie($sessionName, '', $_SERVER['REQUEST_TIME'] - 42000, '/');
      }

      return session_destroy();
    }

    return TRUE;
  }

  /**
   * セッションコンテキストに格納されているデータを取得します。
   *
   * @param string $namespace コンテキストの名前空間。
   * @return array セッションコンテキストに格納されているデータを返します。
   * @throws RuntimeException セッションが開始していない場合に発生。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function &getContext($namespace = NULL)
  {
    if ($this->checkActived()) {
      if ($namespace === NULL) {
        return $_SESSION;

      } else if (isset($_SESSION[$namespace])) {
        return $_SESSION[$namespace];
      }

      $_SESSION[$namespace] = array();

      return $_SESSION[$namespace];
    }
  }

  /**
   * セッションデータをストレージに書き込んで早期にセッションを終了します。
   * セッションデータは同時書き込み防止のためロックされますが、セッションの変更を加えた最後の時点で本メソッドをコールすることで、AJAX やフレームセットを使用する場合のパフォーマンス改善が見込めます。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function close()
  {
    if ($this->_isActive) {
      $container = Delta_DIContainerFactory::getContainer();

      if ($container->hasComponent('user')) {
        $container->getComponent('user')->finalize();
      }

      $this->finalize();
    }
  }

  /**
   * セッションデータのファイナライズ処理を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function finalize()
  {
    try {
      session_write_close();
      $this->_isActive = FALSE;

    } catch (Exception $e) {
      Delta_ExceptionStackTraceDelegate::invoker($e);
      die();
    }
  }
}
