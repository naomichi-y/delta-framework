<?php
/**
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 * @copyright Copyright (c) delta framework project.
 * @license GNU GPL v3+
 * @link http://delta-framework.org/
 */

/**
 * テンプレート上で {@link Delta_AuthorityUser ユーザ情報} を取得するためのヘルパメソッドを提供します。
 * このヘルパは、$user という変数名であらかじめテンプレートにインスタンスが割り当てられています。
 *
 * <code>
 * <?php echo $user->{method}; ?>
 * </code>
 *
 * global_helpers.yml の設定例:
 * <code>
 * user:
 *   # ヘルパクラス名。
 *   class: Delta_UserHelper
 * </code>
 * <i>その他に指定可能な属性は {@link Delta_Helper} クラスを参照。</i>
 *
 * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
 * @category delta
 * @package view.helper
 */
class Delta_UserHelper extends Delta_Helper
{
  /**
   * @var Delta_AuthorityUser
   */
  private $_user;

  /**
   * @see Delta_Helper::__construct()
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function __construct(Delta_View $currentView, array $config = array())
  {
    $this->_user = $this->getUser();

    parent::__construct($currentView, $config);
  }

  /**
   * {@link Delta_AuthorityUser::hasAttribute()} メソッドのエイリアスです。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasAttribute($name)
  {
    return $this->_user->hasAttribute($name);
  }

  /**
   * {@link Delta_AuthorityUser::getAttribute()} メソッドに {@link Delta_StringUtils::escape() HTML エスケープ} 機能を追加した拡張メソッドです。
   *
   * @param string $name {@link Delta_AuthorityUser::getAttribute()} メソッドを参照。
   * @param bool $escape 値を HTML エスケープした状態で返す場合は TRUE を指定。
   * @return mixed name に対応する属性値を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function get($name, $escape = TRUE)
  {
    $value = $this->_user->getAttribute($name);

    if ($escape) {
      $value = Delta_StringUtils::escape($value);
    }

    return $value;
  }

  /**
   * {@link DeltaAuthorityUser::isLogin()} メソッドのエイリアスです。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function isLogin()
  {
    return $this->_user->isLogin();
  }

  /**
   * {@link Delta_AuthorityUser::hasRole()} メソッドのエイリアスです。
   *
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function hasRole($roles = NULL)
  {
    return $this->_user->hasRole($roles);
  }

  /**
   * トランザクショントークン ID を取得します。
   * <i>通常はテンプレート上でこのメソッドをコールする必要はありません。
   * トークン ID は {@link Delta_FormHelper::close()} メソッドをコールした時点で自動的にテンプレートの hidden フィールドに埋め込まれます。</i>
   *
   * @return string トランザクショントークン ID を返します。
   * @author Naomichi Yamakita <naomichi.y@delta-framework.org>
   */
  public function getTokenId()
  {
    return $this->_user->getAttribute('tokenId');
  }
}
