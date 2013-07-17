<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session,user
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * ユーザセッションを管理するための高レベル API を提供します。
 *
 * Delta_AuthorityUser が管理するセッション情報 (ユーザデータ、ロール、ログイン状態) はモジュール単位で有効となります。
 * このクラスは 'user' コンポーネントとして DI コンテナに登録されているため、{@link Delta_DIContainer::getComponent()}、あるいは {@link Delta_DIController::getUser()} からインスタンスを取得することができます。
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package http.session.user
 */

class Delta_AuthorityUser extends Delta_Object
{
  /**
   * 全てのロールを満たす必要がある。
   */
  const REQUIRED_ALL_ROLES = 1;

  /**
   * 1 つ以上のロールを満たす必要がある。
   */
  const REQUIRED_ONE_ROLE = 2;

  /**
   * トランザクショントークンが正常な状態を表す定数。
   */
  const TOKEN_VALID = 1;

  /**
   * トランザクショントークンが異常な状態 (二重コミット、不正なアクセス等) を表す定数。
   */
  const TOKEN_INVALID = -1;

  /**
   * トランザクショントークンが存在しない状態を表す定数。
   */
  const TOKEN_WRONG = -2;

  /**
   * モジュールの名前空間。
   * @var string
   */
  private $_namespace;

  /**
   * セッションコンテキスト。
   * @var array
   */
  private $_context;

  /**
   * {@link Delta_BlowfishCipher} オブジェクト。
   * @var Delta_BlowfishCipher
   */
  private $_cipher;

  /**
   * 破棄予定のフラッシュリスト。
   * @var array
   */
  private $_removeFlashList = array();

  /**
   * ユーザオブジェクトを初期化します。
   *
   * @see Delta_AuthorityUser::Initialize()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function initialize()
  {
    $container = Delta_DIContainerFactory::getContainer();

    $sessionConfig = Delta_Config::getApplication()->get('session');
    $encrypt = $sessionConfig->getBoolean('encrypt');
    $session = $container->getComponent('session');

    if ($encrypt) {
      $this->_cipher = new Delta_BlowfishCipher();
      $this->_cipher->setInitializationVector('user');
    }

    $this->_namespace = $container->getComponent('request')->getRoute()->getModuleName();
    $this->_context = &$session->getContext($this->_namespace);

    $currentTime = $_SERVER['REQUEST_TIME'];

    if (sizeof($this->_context)) {
      $accessTime = $this->_context['access'];
      $timeout = $sessionConfig->getInt('timeout');

      if ($timeout > 0 && $currentTime - $accessTime >= $timeout) {
        $this->clear();

      } else {
        // URI にセッション ID が含まれていない場合
        if (Delta_StringUtils::nullOrEmpty(SID)) {
          $updateSpan = $sessionConfig->getInt('updateSpan');

          // セッション ID の自動更新
          if ($updateSpan > 0 && $currentTime - $accessTime > $updateSpan) {
            $session()->updateId();
          }
        }

        if (isset($this->_context['flash'])) {
          $this->_removeFlashList = $this->_context['flash'];
        }
      }

    } else {
      $this->clear();
    }

    $this->_context['access'] = $currentTime;

    register_shutdown_function(array($this, 'finalize'));
  }

  /**
   * ユーザオブジェクトに属性を設定します。
   *
   * @param string $name 属性名。
   * @param mixed $value 属性値。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttribute($name, $value)
  {
    if ($this->_cipher) {
      $value = $this->_cipher->encrypt(serialize($value));
    }

    $this->_context['attributes'][$name] = $value;
  }

  /**
   * ユーザオブジェクトに属性のリストを設定します。
   *
   * @param array $attributes 属性名と属性値から構成される連想配列。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setAttributes(array $attributes)
  {
    foreach ($attributes as $name => $value) {
      $this->setAttribute($name, $value);
    }
  }

  /**
   * 指定した属性がユーザオブジェクトに設定されているかチェックします。
   *
   * @param string $name チェック対象の属性名。
   * @return bool 属性が設定されている場合は TRUE、設定されていない場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttribute($name)
  {
    return isset($this->_context['attributes'][$name]);
  }

  /**
   * ユーザオブジェクトに設定されている属性を取得します。
   *
   * @param string $name 取得対象の属性名。。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @return mixed name に対応する属性値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttribute($name, $alternative = NULL)
  {
    if (isset($this->_context['attributes'][$name])) {
      if ($this->_cipher) {
        $value = unserialize($this->_cipher->decrypt($this->_context['attributes'][$name]));
      } else {
        $value = $this->_context['attributes'][$name];
      }

      return $value;
    }

    return $alternative;
  }

  /**
   * ユーザオブジェクトに設定されている属性のリストを取得します。
   *
   * @return array ユーザオブジェクトに設定されている属性のリストを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getAttributes()
  {
    $attributes = array();

    foreach ($this->_context['attributes'] as $name => $value) {
      $attributes[$name] = $this->getAttribute($name);
    }

    return $attributes;
  }

  /**
   * ユーザオブジェクトに設定されている属性を破棄します。
   *
   * @param string $name 破棄対象の属性名。
   * @return bool 属性の破棄に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function removeAttribute($name)
  {
    if (isset($this->_context['attributes'][$name])) {
      unset($this->_context['attributes'][$name]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * ユーザオブジェクトに属性を設定します。
   * このメソッドは setAttribute() と異なり、persist で指定したタイミングで自動的に内容が破棄されます。
   *
   * @param string $name 属性名。
   * @param mixed $value 属性の値。
   * @param bool $persist 属性を保持する期間の指定。
   *   - TRUE: {@link getFlash()} がコールされるタイミングで破棄。
   *   - FALSE: 次のリクエストが完了したタイミングで破棄。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function setFlash($name, $value, $persist = FALSE)
  {
    unset($this->_removeFlashList[$name]);

    if ($this->_cipher) {
      $value = $this->_cipher->encrypt(serialize($value));
    }

    $this->_context['flash'][$name] = array('data' => $value, 'persist' => $persist);
  }

  /**
   * setFlash() で設定した属性を取得します。
   *
   * @param string $name 取得対象の属性名。
   * @param mixed $alternative 値が存在しない場合に返す代替値。
   * @return mixed name に対応する属性値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getFlash($name, $alternative = NULL)
  {
    if (isset($this->_context['flash'][$name])) {
      $flash = $this->_context['flash'][$name];
      $value = $flash['data'];

      if ($this->_cipher) {
        $value = unserialize($this->_cipher->decrypt($value));
      }

      if ($flash['persist']) {
        unset($this->_context['flash'][$name]);
        unset($this->_removeFlashList[$name]);
      }

      return $value;
    }

    return $alternative;
  }

  /**
   * ユーザオブジェクトにロールを追加します。
   *
   * @param string $role ロール名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addRole($role)
  {
    if (!in_array($role, $this->_context['roles'])) {
      $this->_context['roles'][] = $role;
    }
  }

  /**
   * ユーザオブジェクトに配列形式でロールを追加します。
   *
   * @param array $roles ロールリスト。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function addRoles(array $roles)
  {
    foreach ($roles as $role) {
      $this->addRole($role);
    }
  }

  /**
   * 指定したロールがユーザオブジェクトに登録されているかチェックします。
   *
   * @param mixed $role チェック対象のロール名。文字列、または配列形式での指定が可能。
   *   配列形式の場合、role 配列に含まれるロールが 1 つでも含まれていない場合は FALSE を返します。
   *   role が未指定の場合は、1 つ以上のロールが登録されているかどうかをチェックします。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasRole($role = NULL)
  {
    $userRoles = $this->_context['roles'];

    if (Delta_StringUtils::nullOrEmpty($userRoles)) {
      if (sizeof($userRoles)) {
        return TRUE;
      }

    } else {
      if (is_array($role)) {
        foreach ($role as $role) {
          if (!in_array($role, $userRoles)) {
            return FALSE;
          }
        }

        return TRUE;

      } else {
        if (in_array($role, $userRoles)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * ユーザオブジェクトに登録されている全てのロールを取得します。
   *
   * @return array ユーザオブジェクトに登録されている全てのロールを返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getRoles()
  {
    return $this->_context['roles'];
  }

  /**
   * ユーザオブジェクトに登録されているロールを破棄します。
   *
   * @param mixed $roleName 削除するロール名。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function revokeRole($roleName = NULL)
  {
    $roles = &$this->_context['roles'];

    if (($index = array_search($roleName, $roles)) !== FALSE) {
      unset($roles[$index]);
    }
  }

  /**
   * ユーザオブジェクトに登録されている複数のロールを破棄します。
   *
   * @param array $roleNames 削除するロール名。
   *   roleNames が NULL の場合は設定されている全てのロールを破棄。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function revokeRoles(array $roleNames = NULL)
  {
    $roles = &$this->_context['roles'];

    if ($roleNames === NULL) {
      $roles = array();

    } else {
      foreach ($roleNames as $roleName) {
        if (($index = array_search($roleName, $roles)) !== FALSE) {
          unset($roles[$index]);
        }
      }
    }
  }

  /**
   * アクション実行ロールをユーザが満たしているかチェックします。
   * アクションごとのロールはビヘイビアファイルの 'roles' 属性で設定することができます。
   * ユーザにロールを追加する場合は {@link addRole()} メソッドを使用して下さい。
   *
   * <code>
   * {Greeting.yml}
   *
   * # GreetingAction に紐付くロール
   * roles:
   *   - view
   *   - execute
   * </code>
   * <code>
   * {GreetingAction.php}
   *
   * // ユーザが 'view'、'execute' ロールを満たしている場合は TRUE を返す
   * $user->isCurrentActionAuthenticated(Delta_AuthorityUser::REQUIRED_ALL_ROLES)
   * </code>
   *
   * @param bool $requiredRole ロールを所有していると見なす条件。Delta_AuthorityUser::REQUIRED_* 定数を指定。
   * @return bool ユーザがロールを所有している場合は TRUE、所有していない (または不足している) 場合は FALSE を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isCurrentActionAuthenticated($requiredRole = self::REQUIRED_ALL_ROLES)
  {
    $actionRoles = Delta_ActionStack::getInstance()->getLastEntry()->getRoles();
    $userRoles = $this->_context['roles'];

    if ($actionRoles) {
      if (sizeof($userRoles) == 0 && sizeof($actionRoles)) {
        return FALSE;
      }

      if ($requiredRole == self::REQUIRED_ALL_ROLES) {
        foreach ($actionRoles as $actionRole) {
          if (!in_array($actionRole, $userRoles)) {
            return FALSE;
          }
        }

        return TRUE;

      } else {
        foreach ($actionRoles as $actionRole) {
          if (in_array($actionRole, $userRoles)) {
            return TRUE;
          }
        }

        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * フォームにおいて二重送信を防止するためのトランザクショントークン ID を発行します。
   * 発行されたトークン ID は {@link Delta_FormHelper::close()} メソッドをコールすることにより、自動的に hidden タグとしてフォーム内に埋め込まれます。
   *
   * @param string $tokenId 任意のトークン ID。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function saveToken($tokenId = NULL)
  {
    if ($tokenId === NULL) {
      $secretKey = Delta_Config::getApplication()->getString('secretKey');
      $seed = $secretKey . microtime(TRUE);
      $tokenId = md5($seed);
    }

    $this->setAttribute('tokenId', $tokenId);

    $container = Delta_DIContainerFactory::getContainer();
    $container->getComponent('form')->set('tokenId', $tokenId);
  }

  /**
   * トランザクショントークンの状態を取得します。
   *
   * @param bool $tokenState TRUE を指定した場合、状態を取得した後にトークンを破棄します。
   * @return int Delta_AuthorityUser::TOKEN_* 定数を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTokenState($resetToken = FALSE)
  {
    $storeTokenId = $this->getAttribute('tokenId');
    $request = Delta_DIContainerFactory::getContainer()->getComponent('request');
    $formTokenId = $request->getParameter('tokenId');

    if ($formTokenId === NULL && $storeTokenId === NULL) {
      $tokenState = self::TOKEN_WRONG;

    } else if (strcmp($formTokenId, $storeTokenId) == 0) {
      $tokenState = self::TOKEN_VALID;

    } else {
      $tokenState = self::TOKEN_INVALID;
    }

    if ($resetToken) {
      $this->resetToken();
    }

    return $tokenState;
  }

  /**
   * ユーザが所有しているトークン ID を破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function resetToken()
  {
    $this->removeAttribute('tokenId');
  }

  /**
   * 現在エントリしているモジュールにログインします。
   *
   * @return bool ログインが成功した場合は TRUE、失敗した (既にログイン済み) 場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function login()
  {
    $result = FALSE;

    if (!$this->isLogin()) {
      // Session fixation 対策
      $session = Delta_DIContainerFactory::getContainer()->getComponent('session');
      $session->updateId();

      $this->_context['login'] = TRUE;
      $result = TRUE;
    }

    return $result;
  }

  /**
   * 現在エントリしているモジュールにログインしているかどうかチェックします。
   *
   * @return bool ログイン済みの場合は TRUE、未ログインの場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isLogin()
  {
    return $this->_context['login'];
  }

  /**
   * 現在エントリしているモジュールからログアウトします。
   *
   * @return bool ログアウトが成功した場合は TRUE、失敗した (ログアウト済みの) 場合は FALSE を返します。
   * @since 1.1
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function logout()
  {
    $result = FALSE;

    if ($this->isLogin()) {
      $this->_context['login'] = FALSE;
      $result = TRUE;
    }

    return $result;
  }

  /**
   * ユーザオブジェクトに設定されている全てデータを破棄します。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function clear()
  {
    $this->_context['roles'] = array();
    $this->_context['attributes'] = array();
    $this->_context['flash'] = array();
    $this->_context['login'] = FALSE;
  }

  /**
   * ユーザデータのファイナライズ処理を行います。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function finalize()
  {
    foreach ($this->_removeFlashList as $name => $attributes) {
      if (!$attributes['persist']) {
        unset($this->_context['flash'][$name]);
      }
    }

    $this->_removeFlashList = array();
  }
}
